<?php

namespace App\Models\Ims;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'ims_transactions';

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }

    /** Child transactions, e.g. the IN that takes a loan back. */
    public function returns()
    {
        return $this->hasMany(self::class, 'parent_transaction_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected $casts = [
        'requested_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'expected_return_date' => 'date',
    ];
}
