<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\MonthlyBackupExport;
use Maatwebsite\Excel\Facades\Excel;

class BackupExportController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->month;
        $year = $request->year;

        $fileName = "backup_report_{$year}_{$month}.xlsx";

        return Excel::download(new MonthlyBackupExport($month, $year), $fileName);
    }
}
