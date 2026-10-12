<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvidenceSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_phone',
        'sender_name',
        'group_jid',
        'source_channel',
        'tc_type',
        'doc_type',
        'doc_no',
        'filename',
        'file_size',
        'mime_type',
        'cloud_storage',
        'cloud_url',
        'cloud_path',
        'local_path',
        'raw_caption',
        'matched_model_type',
        'matched_model_id',
        'status',
        'notes',
    ];

    /**
     * Get the associated operational model (WoLog, NsrdiLog, CmlLog, DmiLog).
     */
    public function matchedModel(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'matched_model_type', 'matched_model_id');
    }
}
