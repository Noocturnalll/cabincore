<?php

namespace App\Http\Controllers;

use App\Imports\DailyReportImport;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function dailyReport(Request $request)
    {
        // 1) Cek error upload SEBELUM validasi, supaya pesan aslinya kelihatan
        //    (bukan hanya "The import file failed to upload.").
        $file = $request->file('importFile');

        if ($file && ! $file->isValid()) {
            Log::error('Upload gagal', [
                'code' => $file->getError(),
                'message' => $file->getErrorMessage(),
                'tmp_dir' => ini_get('upload_tmp_dir'),
                'sys_tmp' => sys_get_temp_dir(),
                'ini' => php_ini_loaded_file(),
            ]);

            return back()->with('error', 'Upload gagal (kode '.$file->getError().'): '.$file->getErrorMessage());
        }

        $request->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:50240',
        ]);

        try {
            Log::info('Importing file: '.$file->getClientOriginalName());

            // Jangan IOFactory::load() di sini: itu memuat seluruh workbook ke RAM,
            // lalu Excel::import memuatnya lagi (memori 2x lipat).

            DB::transaction(function () use ($file) {
                Excel::import(new DailyReportImport, $file);
            });

            $msg = 'Data Daily Report berhasil diimport.';
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => $msg]));

            return back()->with('message', $msg);
        } catch (\Throwable $e) {
            Log::error('Import Daily Report gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $msg = 'Terjadi kesalahan saat mengimport data: '.$e->getMessage();
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => $msg]));

            return back()->with('error', $msg);
        }
    }
}
