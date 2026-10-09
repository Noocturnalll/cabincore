<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aoc extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'include_in_report' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function aircraft()
    {
        return $this->hasMany(Aircraft::class);
    }

    /** Label used in every report: code plus airline name, e.g. "JT - Lion Air". */
    public function label(): string
    {
        return $this->code.' - '.$this->name;
    }
}
