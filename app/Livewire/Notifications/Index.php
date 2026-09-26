<?php

namespace App\Livewire\Notifications;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Semua notifikasi ditandai sebagai dibaca.']);
    }

    public function render()
    {
        return view('livewire.notifications.index', [
            'notifications' => auth()->user()->notifications()->paginate(10),
        ])->layout('components.layouts.app', ['title' => 'Notification Center']);
    }
}
