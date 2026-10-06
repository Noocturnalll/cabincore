<?php

namespace App\Http\Controllers;

use App\Models\AcRon;
use App\Models\AcStandby;
use App\Models\TerminalMovement;
use App\Services\GoogleSheetsReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoogleSheetSyncController extends Controller
{
    public function syncSheetData(Request $request)
    {
        $sheetName = $request->input('sheet_name');
        $rowsData = $request->input('data');

        if (empty($sheetName) || ! is_array($rowsData)) {
            return response()->json(['status' => 'error', 'message' => 'Missing sheet_name or data'], 422);
        }

        Log::info("Webhook Sync triggered for sheet: {$sheetName}", ['rows' => count($rowsData)]);

        try {
            DB::beginTransaction();

            if (in_array($sheetName, ['TERMINAL 1', 'TERMINAL 2'])) {
                TerminalMovement::where('terminal_name', $sheetName)->delete();
                foreach ($rowsData as $row) {
                    TerminalMovement::create([
                        'terminal_name' => $sheetName,
                        'flight_date' => GoogleSheetsReader::parseDate($row['date'] ?? null),
                        'no_seq' => GoogleSheetsReader::parseInteger($row['no'] ?? null),
                        'registration' => $row['registrasi'] ?? null,
                        'flight_no_in' => $row['flight_no_in'] ?? null,
                        'sta' => $row['sta'] ?? null,
                        'eta' => $row['eta'] ?? null,
                        'plan_ps' => $row['plan_ps'] ?? null,
                        'flight_no_out' => $row['flight_no_out'] ?? null,
                        'std' => $row['std'] ?? null,
                        'atd' => $row['atd'] ?? null,
                        'engineer_handle' => $row['engineer'] ?? null,
                        'input_afml' => $row['input_afml'] ?? null,
                        'actual_registration' => $row['actual_reg'] ?? null,
                        'tear_off_afml_pink' => $row['pink_afml'] ?? null,
                    ]);
                }
            } elseif ($sheetName === 'AC RON') {
                AcRon::query()->delete();
                foreach ($rowsData as $row) {
                    AcRon::create([
                        'ron_date' => GoogleSheetsReader::parseDate($row['tgl'] ?? null),
                        'no_seq' => GoogleSheetsReader::parseInteger($row['no'] ?? null),
                        'reg_flt' => $row['reg_flt'] ?? null,
                        'ex_flt' => $row['ex_flt'] ?? null,
                        'sta_ata' => $row['sta_ata'] ?? null,
                        'stand' => $row['stand'] ?? null,
                        'flt_no' => $row['flt_no'] ?? null,
                        'route' => $row['route'] ?? null,
                        'std' => $row['std'] ?? null,
                        'remarks' => $row['remarks'] ?? null,
                        'note' => $row['note'] ?? null,
                    ]);
                }
            } elseif ($sheetName === 'AC STBY') {
                AcStandby::query()->delete();
                foreach ($rowsData as $row) {
                    AcStandby::create([
                        'airline_category' => $row['airline'] ?? 'General',
                        'no_seq' => GoogleSheetsReader::parseInteger($row['no'] ?? null),
                        'reg_flt' => $row['reg_flt'] ?? null,
                        'parking' => $row['parking'] ?? null,
                        'plan_rts' => $row['plan_rts'] ?? null,
                        'remarks' => $row['remarks'] ?? null,
                    ]);
                }
            } else {
                DB::rollBack();

                return response()->json(['status' => 'error', 'message' => "Unknown sheet: {$sheetName}"], 422);
            }

            DB::commit();

            return response()->json(['status' => 'success', 'message' => "Sheet {$sheetName} synced successfully"]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Sync failed for sheet {$sheetName}: ".$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Sync failed: '.$e->getMessage()], 500);
        }
    }
}
