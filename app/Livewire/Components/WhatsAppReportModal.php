<?php

namespace App\Livewire\Components;

use App\Services\WhatsApp\UniversalWhatsAppReportService;
use App\Services\WhatsAppService;
use Livewire\Attributes\On;
use Livewire\Component;

class WhatsAppReportModal extends Component
{
    public bool $isOpen = false;

    public string $module = 'executive';

    public string $startDate = '';

    public string $endDate = '';

    public string $station = '';

    public string $reportTitle = 'Laporan WhatsApp';

    public string $reportText = '';

    public int $evidenceCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $evidenceSamples = [];

    // WhatsApp Bot state
    public bool $botReady = false;

    public string $botMessage = 'Memeriksa koneksi bot...';

    public ?string $botQr = null;

    public bool $showQrSection = false;

    /** @var array<int, array{id: string, name: string}> */
    public array $waGroups = [];

    /** @var array<int, string> */
    public array $selectedWaGroups = [];

    public string $manualWaTarget = '';

    public bool $isSending = false;

    public ?string $sendSuccessMessage = null;

    public ?string $sendErrorMessage = null;

    #[On('open-wa-report-modal')]
    public function open(
        string $module = 'executive',
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $station = null
    ): void {
        $this->module = $module;
        $this->startDate = $startDate ?: now()->toDateString();
        $this->endDate = $endDate ?: $this->startDate;
        $this->station = $station ?: '';
        $this->sendSuccessMessage = null;
        $this->sendErrorMessage = null;

        $this->generateReportContent();
        $this->refreshBotStatus();

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->sendSuccessMessage = null;
        $this->sendErrorMessage = null;
    }

    public function generateReportContent(): void
    {
        $service = app(UniversalWhatsAppReportService::class);
        $res = $service->generateReport($this->module, [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'station' => $this->station,
        ]);

        $this->reportTitle = $res['title'];
        $this->reportText = $res['report_text'];
        $this->evidenceCount = $res['evidence_count'];
        $this->evidenceSamples = $res['evidence_samples'];
    }

    public function refreshBotStatus(): void
    {
        $waService = app(WhatsAppService::class);
        $status = $waService->getStatus();

        $this->botReady = (bool) ($status['ready'] ?? false);
        $this->botMessage = (string) ($status['message'] ?? 'Status tidak diketahui');
        $this->botQr = $status['qr'] ?? null;

        if ($this->botReady) {
            $this->showQrSection = false;
            // Auto fetch groups if ready and empty
            if (empty($this->waGroups)) {
                $this->loadWhatsAppGroups();
            }
        } elseif ($this->botQr) {
            $this->showQrSection = true;
        }
    }

    public function restartBotClient(): void
    {
        $waService = app(WhatsAppService::class);
        $res = $waService->restart();

        $this->sendSuccessMessage = null;
        $this->sendErrorMessage = null;

        if ($res['success']) {
            $this->botMessage = 'Bot di-restart, memuat QR code baru...';
            $this->botReady = false;
            $this->showQrSection = true;
            $this->dispatch('notify', ['icon' => 'info', 'message' => $res['message']]);
        } else {
            $this->dispatch('notify', ['icon' => 'warning', 'message' => $res['message']]);
        }
    }

    public function toggleQrSection(): void
    {
        $this->showQrSection = ! $this->showQrSection;
        if ($this->showQrSection) {
            $this->refreshBotStatus();
        }
    }

    public function loadWhatsAppGroups(): void
    {
        $waService = app(WhatsAppService::class);
        $groups = $waService->getGroups();

        if (! empty($groups)) {
            $existingIds = collect($this->waGroups)->pluck('id')->all();
            foreach ($groups as $g) {
                if (! in_array($g['id'], $existingIds, true)) {
                    $this->waGroups[] = $g;
                }
            }
            $this->dispatch('notify', ['icon' => 'success', 'message' => 'Memuat '.count($groups).' grup WhatsApp!']);
        } else {
            if (! $this->botReady) {
                $this->refreshBotStatus();
            }
        }
    }

    public function addManualWaTarget(): void
    {
        $target = trim($this->manualWaTarget);
        if (! $target) {
            return;
        }

        $existing = collect($this->waGroups)->firstWhere('id', $target);
        if (! $existing) {
            $this->waGroups[] = [
                'id' => $target,
                'name' => 'Nomor/JID: '.$target,
            ];
        }

        if (! in_array($target, $this->selectedWaGroups, true)) {
            $this->selectedWaGroups[] = $target;
        }

        $this->manualWaTarget = '';
    }

    public function toggleSelectAllGroups(): void
    {
        $allIds = collect($this->waGroups)->pluck('id')->all();
        if (count($this->selectedWaGroups) === count($allIds)) {
            $this->selectedWaGroups = [];
        } else {
            $this->selectedWaGroups = $allIds;
        }
    }

    public function sendReportToSelectedGroups(): void
    {
        $this->sendSuccessMessage = null;
        $this->sendErrorMessage = null;

        if (empty($this->selectedWaGroups)) {
            $this->sendErrorMessage = 'Silakan centang minimal 1 grup WhatsApp tujuan pengiriman!';

            return;
        }

        $this->isSending = true;
        $waService = app(WhatsAppService::class);
        $success = 0;
        $failed = 0;

        foreach ($this->selectedWaGroups as $targetId) {
            $sent = $waService->sendMessage($targetId, $this->reportText);
            if ($sent) {
                $success++;
            } else {
                $failed++;
            }
        }

        $this->isSending = false;

        if ($success > 0 && $failed === 0) {
            $this->sendSuccessMessage = "Laporan berhasil dikirim ke {$success} grup WhatsApp! ✓";
            $this->dispatch('notify', ['icon' => 'success', 'message' => $this->sendSuccessMessage]);
        } elseif ($success > 0 && $failed > 0) {
            $this->sendSuccessMessage = "Berhasil kirim ke {$success} grup, tetapi gagal pada {$failed} target.";
        } else {
            $this->sendErrorMessage = 'Gagal mengirim laporan. Pastikan WhatsApp bot sedang aktif dan nomor tujuan valid.';
            $this->dispatch('notify', ['icon' => 'error', 'message' => $this->sendErrorMessage]);
        }
    }

    public function render()
    {
        return view('livewire.components.whats-app-report-modal');
    }
}
