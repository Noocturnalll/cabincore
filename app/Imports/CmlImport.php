<?php

namespace App\Imports;

use App\Models\CmlLog;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CmlImport implements ToModel, WithHeadingRow
{
    public function model(array $row): Model|array|null
    {
        return new CmlLog([
            'date' => $row['date'] ?? null,
            'operator' => $row['operator'] ?? null,
            'aircraft_registration' => $row['aircraft_registration'] ?? $row['ac_reg'] ?? $row['a_c_reg'] ?? $row['reg_ac'] ?? null,
            'ac_status' => $row['ac_status'] ?? $row['a_c_status'] ?? $row['aircraft_status'] ?? null,
            'station' => $row['station'] ?? $row['sta'] ?? null,
            'doc_type' => $row['doc_type'] ?? null,
            'no_doc' => $row['no_doc'] ?? null,
            'ac_type' => $row['ac_type'] ?? $row['a_c_type'] ?? null,
            'shift' => $row['shift'] ?? null,
            'task_type' => $row['task_type'] ?? null,
            'start_perform' => $row['start_perform'] ?? null,
            'finish_perform' => $row['finish_perform'] ?? null,
            'mp1' => $row['mp_1'] ?? $row['mp1'] ?? null,
            'mp2' => $row['mp_2'] ?? $row['mp2'] ?? null,
            'mp3' => $row['mp_3'] ?? $row['mp3'] ?? null,
            'mp4' => $row['mp_4'] ?? $row['mp4'] ?? null,
            'mp5' => $row['mp_5'] ?? $row['mp5'] ?? null,
            'man_power' => is_numeric($row['total_mp'] ?? null) ? (int) $row['total_mp'] : null,
            'description' => $row['description'] ?? $row['action_taken'] ?? $row['reason'] ?? null,
            'status' => $row['status'] ?? 'Open',
            'hold_reason_category' => $row['hold_reason_category'] ?? null,
            'hold_remarks' => $row['hold_remarks'] ?? null,
        ]);
    }
}
