<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date:Y-m-d', 'submitted_at' => 'datetime'];
    }

    public function isComplete(): bool
    {
        return $this->url_brf && $this->url_att && $this->url_5r;
    }
}
