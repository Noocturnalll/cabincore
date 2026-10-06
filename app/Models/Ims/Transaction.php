<?php

namespace App\Models\Ims;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    
    protected $table = 'ims_transactions';
    protected $guarded = [];
    public function items() { return $this->hasMany(TransactionItem::class); }
    public function requester() { return $this->belongsTo(\App\Models\User::class, 'requested_by'); }

    protected $casts = [
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'expected_return_date' => 'date',
    ];
}