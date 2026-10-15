<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Silent audit trail of the two-way Google Sheet sync (conflicts, failed writes, auto-heals). Never shown as UI errors. */
class SheetSyncLog extends Model
{
    protected $fillable = ['direction', 'kind', 'doc_no', 'event', 'message', 'payload', 'attempts', 'resolved_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'resolved_at' => 'datetime'];
    }

    public static function note(string $direction, string $event, ?string $kind, ?string $doc, string $message, ?array $payload = null): self
    {
        // one open entry per doc+event: refresh instead of piling up on every poll
        $entry = static::where(compact('direction', 'event', 'kind'))->where('doc_no', $doc)->whereNull('resolved_at')->first();
        if ($entry) {
            $entry->update(['message' => $message, 'payload' => $payload ?? $entry->payload, 'attempts' => $entry->attempts + 1]);

            return $entry;
        }

        return static::create(['direction' => $direction, 'event' => $event, 'kind' => $kind, 'doc_no' => $doc, 'message' => $message, 'payload' => $payload, 'attempts' => 1]);
    }
}
