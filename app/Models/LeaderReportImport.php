<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaderReportImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['report_date' => 'date', 'stats' => 'array', 'applied_at' => 'datetime'];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(LeaderReportRow::class, 'import_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
