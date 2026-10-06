<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairInProgress extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_repair_in_progress';
    protected $guarded = [];
}