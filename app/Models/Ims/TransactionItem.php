<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $table = 'ims_transaction_items';

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
