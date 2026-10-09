<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model
{
    use HasFactory;

    protected $table = 'aircrafts';

    protected $fillable = ['registration', 'tipe', 'maskapai', 'status', 'aoc_id', 'wg', 'fleet', 'variant', 'type_raw'];

    public function aoc()
    {
        return $this->belongsTo(Aoc::class);
    }
}
