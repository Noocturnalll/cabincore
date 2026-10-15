<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row of a tab of the REF MANUAL sheet (AMM chapters, CMM, part numbers ...). Read only in the app. */
class ManualReference extends Model
{
    protected $fillable = ['tab', 'row_no', 'cells', 'links', 'search', 'synced_at'];

    protected function casts(): array
    {
        return ['cells' => 'array', 'links' => 'array', 'synced_at' => 'datetime'];
    }

    /** Google Drive "view" link -> the embeddable preview link; null for anything else. */
    public static function previewUrl(?string $url): ?string
    {
        if ($url && preg_match('~^https://drive\.google\.com/file/d/([A-Za-z0-9_-]+)~', $url, $m)) {
            return "https://drive.google.com/file/d/{$m[1]}/preview";
        }

        return null;
    }
}
