<?php

namespace App\Services\WhatsApp;

use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\EvidenceSubmission;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Cloud\MicrosoftGraphStorageService;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppEvidenceProcessor
{
    public function __construct(
        protected WhatsAppEvidenceParser $parser,
        protected MicrosoftGraphStorageService $cloudStorage,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Process an incoming WhatsApp message payload for evidence extraction.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     processed: bool,
     *     reason?: string,
     *     evidence_id?: int,
     *     doc_type?: string,
     *     doc_no?: string,
     *     matched?: bool,
     *     cloud_url?: string
     * }
     */
    public function process(array $payload): array
    {
        // 1. Extract metadata from diverse gateway payload formats
        $extracted = $this->extractPayloadData($payload);

        $caption = $extracted['caption'];
        $groupJid = $extracted['group_jid'];
        $senderPhone = $extracted['sender_phone'];
        $senderName = $extracted['sender_name'];
        $fileContents = $extracted['file_contents'];
        $mimeType = $extracted['mime_type'] ?: 'image/jpeg';
        $extension = $this->resolveExtension($mimeType);

        if (empty($caption)) {
            return ['processed' => false, 'reason' => 'Pesan tidak memiliki teks / caption keterangan'];
        }

        // 2. Parse caption for T/C Type, Doc Type, No Doc
        $parsed = $this->parser->parse($caption);

        if (! $parsed['is_valid']) {
            return [
                'processed' => false,
                'reason' => 'Caption tidak memenuhi format t/c type, doc type, dan no doc',
                'parsed' => $parsed,
            ];
        }

        // If media content was not embedded, try to fetch via media URL or service
        if (empty($fileContents) && ! empty($extracted['media_url'])) {
            $fileContents = $this->downloadMedia($extracted['media_url']);
        }

        if (empty($fileContents)) {
            return [
                'processed' => false,
                'reason' => 'Konten gambar / media tidak ditemukan pada payload',
                'parsed' => $parsed,
            ];
        }

        $docType = $parsed['doc_type'];
        $docNo = $parsed['doc_no'];
        $tcType = $parsed['tc_type'] ?: 'EVIDENCE';

        // 3. Upload file to Microsoft Outlook / OneDrive 1TB (with graceful local fallback)
        $upload = $this->cloudStorage->uploadEvidence(
            $fileContents,
            $docType,
            $docNo,
            $tcType,
            $extension,
            $mimeType
        );

        // 4. Match with operational database records (NSRDI, WO, CML, DMI)
        $matchedModel = $this->findAndLinkOperationalModel($docType, $docNo, $upload['web_url'] ?: $upload['local_path']);

        // 5. Create persistent EvidenceSubmission record
        $submission = EvidenceSubmission::create([
            'sender_phone' => $senderPhone,
            'sender_name' => $senderName,
            'group_jid' => $groupJid,
            'source_channel' => 'whatsapp',
            'tc_type' => $tcType,
            'doc_type' => $docType,
            'doc_no' => $docNo,
            'filename' => $upload['filename'],
            'file_size' => $upload['size'],
            'mime_type' => $mimeType,
            'cloud_storage' => $upload['storage'],
            'cloud_url' => $upload['web_url'],
            'cloud_path' => $upload['cloud_path'],
            'local_path' => $upload['local_path'],
            'raw_caption' => $caption,
            'matched_model_type' => $matchedModel ? get_class($matchedModel) : null,
            'matched_model_id' => $matchedModel?->id,
            'status' => $matchedModel ? 'linked' : 'saved',
            'notes' => $upload['message'],
        ]);

        // 6. Optional: Send acknowledgment reply to WhatsApp Group
        if ($groupJid && config('services.whatsapp.reply_evidence_ack', true)) {
            $this->sendAcknowledgment($groupJid, $docType, $docNo, $tcType, $upload['storage']);
        }

        return [
            'processed' => true,
            'evidence_id' => $submission->id,
            'doc_type' => $docType,
            'doc_no' => $docNo,
            'tc_type' => $tcType,
            'matched' => (bool) $matchedModel,
            'cloud_url' => $upload['web_url'],
            'storage' => $upload['storage'],
        ];
    }

    /**
     * Normalize payload differences from Baileys, Fonnte, W-API, or WhatsApp Webhooks.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     caption: string|null,
     *     group_jid: string|null,
     *     sender_phone: string|null,
     *     sender_name: string|null,
     *     file_contents: string|null,
     *     media_url: string|null,
     *     mime_type: string|null
     * }
     */
    private function extractPayloadData(array $payload): array
    {
        $caption = null;
        $groupJid = null;
        $senderPhone = null;
        $senderName = null;
        $fileContents = null;
        $mediaUrl = null;
        $mimeType = null;

        // 1. Direct top-level fields
        $caption = $payload['caption'] ?? $payload['text'] ?? $payload['message'] ?? null;
        $groupJid = $payload['group_jid'] ?? $payload['from'] ?? $payload['remoteJid'] ?? null;
        $senderPhone = $payload['sender'] ?? $payload['sender_phone'] ?? $payload['participant'] ?? null;
        $senderName = $payload['sender_name'] ?? $payload['pushName'] ?? null;
        $mediaUrl = $payload['media_url'] ?? $payload['url'] ?? $payload['file_url'] ?? null;
        $mimeType = $payload['mime_type'] ?? $payload['mimetype'] ?? null;

        // Base64 media payload
        if (! empty($payload['media_base64'])) {
            $fileContents = base64_decode((string) $payload['media_base64']);
        } elseif (! empty($payload['base64'])) {
            $cleanBase64 = preg_replace('#^data:image/\w+;base64,#i', '', (string) $payload['base64']);
            $fileContents = base64_decode($cleanBase64);
        }

        // 2. Baileys / W-API nested format: payload['data']['message']
        if (isset($payload['data'])) {
            $data = $payload['data'];
            $groupJid = $groupJid ?: ($data['key']['remoteJid'] ?? null);
            $senderPhone = $senderPhone ?: ($data['key']['participant'] ?? ($data['sender'] ?? null));
            $senderName = $senderName ?: ($data['pushName'] ?? null);

            $msg = $data['message'] ?? [];
            if (isset($msg['imageMessage'])) {
                $caption = $caption ?: ($msg['imageMessage']['caption'] ?? null);
                $mediaUrl = $mediaUrl ?: ($msg['imageMessage']['url'] ?? null);
                $mimeType = $mimeType ?: ($msg['imageMessage']['mimetype'] ?? 'image/jpeg');
            } elseif (isset($msg['documentMessage'])) {
                $caption = $caption ?: ($msg['documentMessage']['caption'] ?? null);
                $mediaUrl = $mediaUrl ?: ($msg['documentMessage']['url'] ?? null);
                $mimeType = $mimeType ?: ($msg['documentMessage']['mimetype'] ?? 'application/pdf');
            }
        }

        return [
            'caption' => $caption,
            'group_jid' => $groupJid,
            'sender_phone' => $senderPhone,
            'sender_name' => $senderName,
            'file_contents' => $fileContents,
            'media_url' => $mediaUrl,
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Download binary media from a URL or the internal WhatsApp service gateway.
     */
    private function downloadMedia(string $url): ?string
    {
        try {
            $response = Http::timeout(25)->get($url);
            if ($response->successful()) {
                return $response->body();
            }

            Log::error("Failed to download media from {$url}: HTTP ".$response->status());

            return null;
        } catch (\Exception $e) {
            Log::error("Exception downloading media from {$url}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Find matching operational model and link the evidence path.
     */
    private function findAndLinkOperationalModel(string $docType, string $docNo, string $evidenceUrl): ?Model
    {
        $cleanNo = trim($docNo);

        return match ($docType) {
            'NSRDI' => $this->linkNsrdi($cleanNo, $evidenceUrl),
            'WO' => $this->linkWo($cleanNo, $evidenceUrl),
            'CML' => $this->linkCml($cleanNo, $evidenceUrl),
            'DMI' => $this->linkDmi($cleanNo, $evidenceUrl),
            default => null,
        };
    }

    private function linkNsrdi(string $no, string $url): ?NsrdiLog
    {
        $item = NsrdiLog::where('nsrdi_number', $no)
            ->orWhere('nsrdi_number', 'like', "%{$no}%")
            ->latest()
            ->first();

        if ($item) {
            $item->update([
                'evidence_path' => $url,
                'photo_evidence_path' => $url,
            ]);
        }

        return $item;
    }

    private function linkWo(string $no, string $url): ?WoLog
    {
        $item = WoLog::where('wo_number', $no)
            ->orWhere('wo_number', 'like', "%{$no}%")
            ->latest()
            ->first();

        if ($item) {
            $item->update([
                'evidence_path' => $url,
                'photo_evidence_path' => $url,
            ]);
        }

        return $item;
    }

    private function linkCml(string $no, string $url): ?CmlLog
    {
        $item = CmlLog::where('cml_number', $no)
            ->orWhere('cml_number', 'like', "%{$no}%")
            ->latest()
            ->first();

        if ($item) {
            $item->update([
                'evidence_path' => $url,
            ]);
        }

        return $item;
    }

    private function linkDmi(string $no, string $url): ?DmiLog
    {
        $item = DmiLog::where('dmi_number', $no)
            ->orWhere('dmi_number', 'like', "%{$no}%")
            ->latest()
            ->first();

        if ($item) {
            $item->update([
                'evidence_path' => $url,
                'photo_evidence_path' => $url,
            ]);
        }

        return $item;
    }

    /**
     * Send instant acknowledgment to WhatsApp Group.
     */
    private function sendAcknowledgment(string $groupJid, string $docType, string $docNo, string $tcType, string $storage): void
    {
        $storageLabel = $storage === 'onedrive' ? 'Microsoft Outlook (OneDrive 1 TB)' : 'Server Storage (Backup)';

        $text = "✅ *DOKUMENTASI FOTO DITERIMA*\n\n"
            ."📄 *Dokumen:* {$docType} #{$docNo}\n"
            ."🏷️ *T/C Type:* {$tcType}\n"
            ."☁️ *Penyimpanan:* {$storageLabel}\n"
            .'⏰ *Waktu:* '.now()->format('d M Y H:i:s')." WIB\n\n"
            .'Foto resolusi penuh telah berhasil diarsipkan secara aman.';

        try {
            $this->whatsAppService->sendMessage($groupJid, $text);
        } catch (\Exception $e) {
            Log::warning('Could not send WhatsApp ACK: '.$e->getMessage());
        }
    }

    /**
     * Resolve file extension from MIME type.
     */
    private function resolveExtension(?string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'jpg',
        };
    }
}
