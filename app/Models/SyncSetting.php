<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSetting extends Model
{
    public const AcMovement = 'ac_movement';

    public const Dja = 'dja';

    public const Cleaning = 'cleaning';

    protected $fillable = [
        'key',
        'spreadsheet_id',
        'last_synced_at',
        'last_status',
        'last_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public static function for(string $key): self
    {
        return static::firstOrCreate(['key' => $key]);
    }

    public static function spreadsheetIdFor(string $key): ?string
    {
        return static::where('key', $key)->value('spreadsheet_id');
    }

    public static function saveSpreadsheetId(string $key, string $spreadsheetId): self
    {
        $setting = static::for($key);
        $setting->update(['spreadsheet_id' => $spreadsheetId]);

        return $setting;
    }

    public static function recordResult(string $key, bool $isSuccessful, string $message): void
    {
        static::for($key)->update([
            'last_synced_at' => now(),
            'last_status' => $isSuccessful ? 'success' : 'failed',
            'last_message' => mb_substr($message, 0, 1000),
        ]);
    }

    /**
     * Accepts a full Google Sheets URL or a raw spreadsheet ID and returns the ID.
     */
    public static function extractSpreadsheetId(?string $input): ?string
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $input)) {
            return $input;
        }

        return null;
    }

    public function sheetUrl(): ?string
    {
        return $this->spreadsheet_id
            ? "https://docs.google.com/spreadsheets/d/{$this->spreadsheet_id}/edit"
            : null;
    }
}
