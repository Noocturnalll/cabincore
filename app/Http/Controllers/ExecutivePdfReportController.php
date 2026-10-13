<?php

namespace App\Http\Controllers;

use App\Services\Reports\ProductionExecutiveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExecutivePdfReportController extends Controller
{
    /**
     * Render the official print-to-PDF Executive Production Summary.
     */
    public function __invoke(Request $request, ProductionExecutiveService $service): View
    {
        $startDate = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $endDate = $request->query('to') ?: now()->toDateString();
        $station = $request->query('sta') ? strtoupper(trim($request->query('sta'))) : null;

        $report = $service->getProductionReport($startDate, $endDate, $station);

        $docNumber = 'LG-CBM-PRD-'.Carbon::parse($startDate)->format('Ymd').'-'.Carbon::parse($endDate)->format('Ymd');
        if ($station) {
            $docNumber .= '-'.$station;
        }

        return view('reports.executive-pdf', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'station' => $station,
            'docNumber' => $docNumber,
            'printedAt' => now()->translatedFormat('d F Y, H:i').' WIB',
            'printedBy' => auth()->user()?->name ?? 'Administrator',
        ]);
    }
}
