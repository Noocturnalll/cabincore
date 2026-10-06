<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSerial extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_item_serials';
    protected $guarded = [];
}