<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairCompleted extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_repair_completed';
    protected $guarded = [];
}