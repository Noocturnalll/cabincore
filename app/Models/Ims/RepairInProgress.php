<?php

namespace App\Models\Ims;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepairInProgress extends Model
{
    use SoftDeletes;

    protected $table = 'ims_repair_in_progress';

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'estimated_completion_date' => 'date'];
    }
}
