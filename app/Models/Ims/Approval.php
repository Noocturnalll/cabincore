<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_approvals';
    protected $guarded = [];
}