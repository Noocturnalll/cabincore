<?php

namespace App\Services\Ims;

use App\Models\User;
use App\Notifications\SystemNotification;

/** In-app notifications for the IMS flows (repair intake, stock requests). Never notifies the person who did the action. */
class ImsNotifier
{
    /** Everybody who holds a permission (directly or through a role), e.g. the approvers of a request. */
    public function toPermission(string $permission, string $title, string $message, string $type = 'info'): void
    {
        $data = ['type' => $type, 'title' => $title, 'message' => $message];

        User::permission($permission)->where('status', 'active')->where('id', '!=', auth()->id())->get()
            ->each(fn (User $u) => $u->notify(new SystemNotification($data)));
    }

    /** Everybody holding a role, e.g. all Finishing PICs. */
    public function toRole(string $role, string $title, string $message, string $type = 'info'): void
    {
        $data = ['type' => $type, 'title' => $title, 'message' => $message];

        User::role($role)->where('status', 'active')->where('id', '!=', auth()->id())->get()
            ->each(fn (User $u) => $u->notify(new SystemNotification($data)));
    }

    public function toUser(?int $userId, string $title, string $message, string $type = 'info'): void
    {
        if (! $userId || (int) $userId === (int) auth()->id()) {
            return;
        }
        User::find($userId)?->notify(new SystemNotification(['type' => $type, 'title' => $title, 'message' => $message]));
    }
}
