<?php

namespace App\Livewire\Notifications;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        // Mock data for notifications
        $notifications = [
            [
                'id' => 1,
                'title' => 'Import Data Center Berhasil',
                'message' => 'Laporan perawatan kabin untuk CGK pada 15 Sep 2026 telah berhasil diimpor ke dalam sistem.',
                'type' => 'success', // success, warning, error, info
                'is_read' => false,
                'time' => '10 menit yang lalu',
            ],
            [
                'id' => 2,
                'title' => 'Peringatan: Verifikasi Tertunda',
                'message' => 'Terdapat 5 laporan dari Station DPS yang belum diverifikasi selama lebih dari 48 jam.',
                'type' => 'warning',
                'is_read' => false,
                'time' => '1 jam yang lalu',
            ],
            [
                'id' => 3,
                'title' => 'Sistem Update',
                'message' => 'Pembaruan keamanan sistem CBM selesai dilakukan.',
                'type' => 'info',
                'is_read' => true,
                'time' => '1 hari yang lalu',
            ],
            [
                'id' => 4,
                'title' => 'Laporan Ditolak',
                'message' => 'Laporan aktivitas PIC Painting B737 (PK-LGP) ditolak oleh Quality Assurance.',
                'type' => 'error',
                'is_read' => true,
                'time' => '3 hari yang lalu',
            ],
        ];

        return view('livewire.notifications.index', [
            'notifications' => collect($notifications),
        ])->layout('components.layouts.app', ['title' => 'Notification Center']);
    }
}
