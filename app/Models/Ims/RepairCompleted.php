<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairCompleted extends Model
{
    use SoftDeletes;

    protected $table = 'ims_repair_completed';

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
        return ['completed_at' => 'datetime', 'returned_to_stock_at' => 'datetime'];
    }
}
