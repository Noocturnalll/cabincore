<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\DB;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        DB::table('login_histories')->insert([
            'user_id' => $event->user ? $event->user->id : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'failed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
