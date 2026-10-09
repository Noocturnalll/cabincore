<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairWaiting extends Model
{
    use SoftDeletes;

    protected $table = 'ims_repair_waiting';

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
