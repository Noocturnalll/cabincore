<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppEvidenceProcessor;
use Illuminate\Console\Command;

class TestEvidenceUploadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbm:test-evidence-upload
                            {--caption=T/C TYPE: RECTIFICATION, DOC TYPE: NSRDI, NO DOC: NSRDI-TEST-001 : Caption text}
                            {--file= : Path to optional local image file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp evidence extraction and Microsoft Outlook (OneDrive 1TB) upload';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppEvidenceProcessor $processor): int
    {
        $caption = (string) $this->option('caption');
        $filePath = $this->option('file');

        $this->info('🚀 Testing WhatsApp Photo Evidence Workflow...');
        $this->line("📝 Caption: \"{$caption}\"");

        if ($filePath && file_exists($filePath)) {
            $binary = file_get_contents($filePath);
            $mime = mime_content_type($filePath) ?: 'image/jpeg';
        } else {
            // Generate a 1x1 test JPEG binary
            $binary = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
            $mime = 'image/jpeg';
        }

        $payload = [
            'sender' => '628123456789',
            'sender_name' => 'Teknisi Test',
            'group_jid' => '120363029182391238@g.us',
            'caption' => $caption,
            'base64' => base64_encode($binary),
            'mime_type' => $mime,
        ];

        $result = $processor->process($payload);

        if ($result['processed']) {
            $this->newLine();
            $this->info('✅ Evidence Processed Successfully!');
            $this->table(['Key', 'Value'], [
                ['Evidence ID', $result['evidence_id'] ?? '-'],
                ['Doc Type', $result['doc_type'] ?? '-'],
                ['Doc Number', $result['doc_no'] ?? '-'],
                ['T/C Type', $result['tc_type'] ?? '-'],
                ['Storage Destination', $result['storage'] ?? '-'],
                ['Matched DB Log', $result['matched'] ? 'Yes ✅' : 'No (Document not yet in DB)'],
                ['URL / Path', $result['cloud_url'] ?? '-'],
            ]);

            return Command::SUCCESS;
        }

        $this->error('❌ Processing failed: '.($result['reason'] ?? 'Unknown error'));

        return Command::FAILURE;
    }
}
