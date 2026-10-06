<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapacityReport extends Model
{
    protected $fillable = [
        'report_date', 'order_no', 'kh_region', 'group_type', 'station_code', 'code_store', 'working_hours',
        'tech_day', 'tech_night', 'ron_jt', 'ron_iw', 'ron_id', 'ron_iu', 'ron_sl', 'ron_od',
    ];
}
