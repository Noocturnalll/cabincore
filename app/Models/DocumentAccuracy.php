<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAccuracy extends Model
{
    protected $table = 'document_accuracy';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['work_date' => 'date:Y-m-d'];
    }
}
