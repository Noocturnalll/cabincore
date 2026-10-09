<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $table = 'ims_stock_movements';

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
