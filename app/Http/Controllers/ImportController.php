<?php

namespace App\Http\Controllers;

use App\Imports\DailyReportImport;
use App\Models\Rotation;
use App\Notifications\SystemNotification;
use App\Services\ExcelHtmlRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

            // FIX: Prevent SQLite "General error 14 unable to open database file" on Windows
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::unprepared('PRAGMA temp_store = MEMORY;');
            }

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

    public function aircraftRotation(Request $request)
    {
        $file = $request->file('importFile');

        if ($file && ! $file->isValid()) {
            Log::error('Upload Aircraft Rotation gagal', [
                'code' => $file->getError(),
                'message' => $file->getErrorMessage(),
            ]);

            return back()->with('error', 'Upload gagal (kode '.$file->getError().'): '.$file->getErrorMessage());
        }

        $request->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:50240',
        ]);

        try {
            // Proses file Excel ke HTML memakan waktu lama untuk file besar (misal 12 sheet)
            set_time_limit(300);

            Log::info('Importing Aircraft Rotation file: '.$file->getClientOriginalName());

            $filename = $file->getClientOriginalName();
            $path = $file->store('rotations');

            $renderer = app(ExcelHtmlRenderer::class);
            $html = $renderer->render(Storage::path($path));

            $htmlPath = 'rotations/'.pathinfo($path, PATHINFO_FILENAME).'.html';
            Storage::put($htmlPath, $html);

            Rotation::create([
                'title' => pathinfo($filename, PATHINFO_FILENAME),
                'file_path' => $path,
                'html_path' => $htmlPath,
            ]);

            $msg = 'File Rotasi berhasil diunggah dan dirender.';
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => $msg]));

            return back()->with('message', $msg);
        } catch (\Throwable $e) {
            Log::error('Import Aircraft Rotation gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $msg = 'Terjadi kesalahan saat memproses data: '.$e->getMessage();
            auth()->user()->notify(new SystemNotification(['type' => 'error', 'title' => 'Sistem', 'message' => $msg]));

            return back()->with('error', $msg);
        }
    }

    /**
     * Upload tanpa multipart: browser mengirim isi file sebagai body mentah
     * (Content-Type: application/octet-stream). PHP membaca body lewat php://input,
     * jadi TIDAK memakai upload_tmp_dir; error "No temp dir" (code 6) tidak berlaku.
     * Batas ukuran hanya post_max_size di php.ini.
     */
    public function dailyReportRaw(Request $request)
    {
        $name = basename(urldecode($request->header('X-File-Name', 'import.xlsx')));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            return response()->json(['message' => 'Format file harus .xlsx atau .xls'], 422);
        }

        $content = $request->getContent();

        if ($content === '' || strlen($content) > 50 * 1024 * 1024) {
            return response()->json(['message' => 'File kosong atau lebih dari 50 MB'], 422);
        }

        $path = 'imports/'.now()->format('Ymd_His').'_'.Str::random(6).'.'.$ext;
        Storage::disk('local')->put($path, $content);

        try {
            Log::info("Import raw: {$name} (".strlen($content).' bytes)');

            // FIX: Prevent SQLite "General error 14 unable to open database file" on Windows
            if (DB::connection()->getDriverName() === 'sqlite') {
                DB::unprepared('PRAGMA temp_store = MEMORY;');
            }

            DB::transaction(function () use ($path) {
                Excel::import(new DailyReportImport, $path, 'local');
            });

            $msg = 'Data Daily Report berhasil diimport.';
            auth()->user()->notify(new SystemNotification(['type' => 'success', 'title' => 'Sistem', 'message' => $msg]));

            return response()->json(['message' => $msg]);
        } catch (\Throwable $e) {
            Log::error('Import raw gagal: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['message' => 'Terjadi kesalahan saat mengimport data: '.$e->getMessage()], 500);
        }
    }
}
