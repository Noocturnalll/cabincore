<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemAlternate extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_item_alternates';
    protected $guarded = [];
}