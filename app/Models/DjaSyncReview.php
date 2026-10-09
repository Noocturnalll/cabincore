<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DjaSyncReview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'decided_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Stable identity of a sheet row across syncs. */
    public static function keyFor(string $tab, ?string $taskId, ?string $acReg, ?string $description): string
    {
        $identity = $taskId !== null && $taskId !== ''
            ? $taskId
            : $acReg.'|'.$description;

        return md5(strtoupper(trim($tab)).'|'.mb_strtoupper(trim((string) $identity)));
    }
}
