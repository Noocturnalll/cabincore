<?php

namespace App\Imports;

use App\Models\CmlLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Models\DmiLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class DailyReportImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Only process closed records
            if (strtolower($row['status'] ?? '') !== 'closed') {
                continue;
            }

            $docType = strtoupper(trim($row['doc_type'] ?? ''));
            
            // Format dates if necessary, though if date is just string we can store as string,
            // assuming date column is string or date.
            $date = null;
            if (isset($row['date'])) {
                try {
                    $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $date = $row['date']; // fallback if it's already a formatted string
                }
            }

            if ($docType === 'CML') {
                CmlLog::create([
                    'dja_id' => null, // Unplanned / direct input
                    'date' => $date,
                    'operator' => $row['operator'] ?? null,
                    'aircraft_registration' => $row['reg_ac'] ?? null,
                    'ac_status' => $row['ac_status'] ?? null,
                    'station' => $row['sta'] ?? null,
                    'doc_type' => $row['doc_type'] ?? null,
                    'no_doc' => $row['no_doc'] ?? null,
                    'description' => $row['action_taken_reason'] ?? null,
                    'status' => 'Closed',
                ]);
            } elseif ($docType === 'NSRDI') {
                $noDoc = $row['no_doc'] ?? null;
                if (!$noDoc) continue;

                $existing = NsrdiLog::whereNotNull('dja_id')->where('nsrdi_number', $noDoc)->first();
                if ($existing) {
                    $existing->update([
                        'status' => 'Closed',
                        'description' => $row['action_taken_reason'] ?? $existing->description
                    ]);
                } else {
                    NsrdiLog::create([
                        'dja_id' => null,
                        'nsrdi_number' => $noDoc,
                        'aircraft_registration' => $row['reg_ac'] ?? null,
                        'act_station' => $row['sta'] ?? null,
                        'description' => $row['action_taken_reason'] ?? null,
                        'status' => 'Closed',
                        'report_date' => $date
                    ]);
                }
            } elseif ($docType === 'WO') {
                $noDoc = $row['no_doc'] ?? null;
                if (!$noDoc) continue;

                $existing = WoLog::whereNotNull('dja_id')->where('wo_number', $noDoc)->first();
                if ($existing) {
                    $existing->update([
                        'status' => 'Closed',
                        'description' => $row['action_taken_reason'] ?? $existing->description
                    ]);
                } else {
                    WoLog::create([
                        'dja_id' => null,
                        'wo_number' => $noDoc,
                        'aircraft_registration' => $row['reg_ac'] ?? null,
                        'act_station' => $row['sta'] ?? null,
                        'description' => $row['action_taken_reason'] ?? null,
                        'status' => 'Closed',
                        'date' => $date,
                        'operator' => $row['operator'] ?? null,
                    ]);
                }
            } elseif ($docType === 'DMI') {
                $noDoc = $row['no_doc'] ?? null;
                if (!$noDoc) continue;

                $existing = DmiLog::whereNotNull('dja_id')->where('dmi_number', $noDoc)->first();
                if ($existing) {
                    $existing->update([
                        'status' => 'Closed',
                        'description' => $row['action_taken_reason'] ?? $existing->description
                    ]);
                } else {
                    DmiLog::create([
                        'dja_id' => null,
                        'dmi_number' => $noDoc,
                        'aircraft_registration' => $row['reg_ac'] ?? null,
                        'act_station' => $row['sta'] ?? null,
                        'description' => $row['action_taken_reason'] ?? null,
                        'status' => 'Closed',
                        'date' => $date,
                    ]);
                }
            }
        }
    }
}
