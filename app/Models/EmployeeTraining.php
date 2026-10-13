<?php

namespace App\Models;

use App\Support\ExpiryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTraining extends Model
{
    protected $table = 'employee_trainings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'course_date' => 'date',
            'last_recurrent_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function expiryStatus(): string
    {
        if (! $this->expiry_date) {
            return ExpiryStatus::NONE;
        }

        return ExpiryStatus::for($this->expiry_date, 2, 1);
    }
}
