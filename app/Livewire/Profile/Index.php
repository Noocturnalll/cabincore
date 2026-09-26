<?php

namespace App\Livewire\Profile;

use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Index extends Component
{
    public $current_password;

    public $password;

    public $password_confirmation;

    public $activeTab = 'info'; // 'info', 'security', 'audit', 'faq'

    public function changePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Password berhasil diperbarui.']);
        auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => 'Password berhasil diperbarui.']));
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        // Dummy data for Personal Audit Trail
        $auditLogs = [
            ['id' => 1, 'action' => 'Melakukan login ke sistem', 'created_at' => now()->subMinutes(5)],
            ['id' => 2, 'action' => 'Mengubah profil pengguna', 'created_at' => now()->subDays(1)],
            ['id' => 3, 'action' => 'Melakukan login ke sistem', 'created_at' => now()->subDays(2)],
        ];

        return view('livewire.profile.index', [
            'user' => auth()->user(),
            'auditLogs' => $auditLogs,
        ])->layout('components.layouts.app', ['title' => 'Profil Saya']);
    }
}
