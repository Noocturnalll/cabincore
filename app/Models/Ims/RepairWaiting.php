<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairWaiting extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_repair_waiting';
    protected $guarded = [];
}