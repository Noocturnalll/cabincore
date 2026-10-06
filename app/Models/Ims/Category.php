<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_categories';
    protected $guarded = [];
}