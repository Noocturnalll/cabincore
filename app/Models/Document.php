<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{
    /** value => label */
    public const CATEGORIES = [
        'template' => 'Template Excel',
        'sop' => 'SOP & Panduan',
        'cmpm' => 'CMPM',
        'regulasi' => 'Regulasi',
        'lainnya' => 'Lainnya',
    ];

    /** Only office/PDF formats are accepted; anything else served from public storage would be a risk. */
    public const ALLOWED_EXTENSIONS = ['pdf', 'xlsx', 'xls', 'csv', 'docx', 'doc', 'pptx', 'ppt', 'txt'];

    /** Livewire's temporary upload rule is 12 MB unless config/livewire.php says otherwise. */
    public const MAX_KB = 12288;

    protected $fillable = [
        'title',
        'category',
        'file_path',
        'file_size',
        'file_extension',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category);
    }

    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('public')->exists($this->file_path);
    }

    /** Name the user sees when downloading, instead of the hashed storage name. */
    public function downloadFilename(): string
    {
        $ext = $this->file_extension ?: pathinfo((string) $this->file_path, PATHINFO_EXTENSION);

        return (Str::slug($this->title) ?: 'dokumen').($ext ? '.'.$ext : '');
    }

    /** Colour key for the file-type tile. */
    public function typeClass(): string
    {
        return match (strtolower((string) $this->file_extension)) {
            'xlsx', 'xls', 'csv' => 'xls',
            'pdf' => 'pdf',
            'docx', 'doc', 'txt' => 'doc',
            'pptx', 'ppt' => 'ppt',
            default => 'other',
        };
    }
}
