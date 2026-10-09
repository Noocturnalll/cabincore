<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterEntry extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['attrs' => 'array', 'is_active' => 'boolean'];
    }
}
