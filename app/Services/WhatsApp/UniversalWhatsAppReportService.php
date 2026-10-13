<?php

namespace App\Services\WhatsApp;

use App\Models\AircraftCleaning;
use App\Models\CmlLog;
use App\Models\DailyJobAssignment;
use App\Models\EvidenceSubmission;
use App\Services\Aiec\StationCapabilityService;
use App\Services\Dja\CodReportService;
use App\Services\Reports\ProductionExecutiveService;
use Carbon\Carbon;

class UniversalWhatsAppReportService
{
    public function __construct(
        protected ProductionExecutiveService $executiveService,
        protected CodReportService $codService
    ) {}

    /**
     * Generate context-aware WhatsApp report based on module context.
     *
     * @param  string  $module  Context module: 'executive', 'cbm', 'painting', 'cleaning', 'cml'
     * @param  array<string, mixed>  $params  Filters such as start_date, end_date, station
     * @return array{
     *     module: string,
     *     title: string,
     *     report_text: string,
     *     evidence_count: int,
     *     evidence_samples: array<int, array<string, mixed>>
     * }
     */
    public function generateReport(string $module, array $params = []): array
    {
        $startDate = $params['start_date'] ?? $params['date'] ?? Carbon::today()->format('Y-m-d');
        $endDate = $params['end_date'] ?? $startDate;
        $station = ! empty($params['station']) ? strtoupper(trim($params['station'])) : null;

        return match (strtolower($module)) {
            'executive' => $this->generateExecutiveReport($startDate, $endDate, $station),
            'cbm', 'daily-report', 'cod' => $this->generateCbmReport($startDate, $endDate, $station),
            'painting' => $this->generatePaintingReport($startDate, $endDate, $station),
            'cleaning', 'aiec' => $this->generateCleaningReport($startDate, $endDate, $station),
            'cml', 'nsrdi' => $this->generateCmlReport($startDate, $endDate, $station),
            default => $this->generateExecutiveReport($startDate, $endDate, $station),
        };
    }

    /**
     * 1. Executive Production Report (3 Pilar).
     */
    protected function generateExecutiveReport(string $startDate, string $endDate, ?string $station): array
    {
        $data = $this->executiveService->getProductionReport($startDate, $endDate, $station);

        $periodStr = $startDate === $endDate
            ? Carbon::parse($startDate)->isoFormat('dddd, D MMMM Y')
            : Carbon::parse($startDate)->format('d/m/Y').' - '.Carbon::parse($endDate)->format('d/m/Y');

        $stationStr = $station ?: 'Nasional (Semua Stasiun)';
        $nowStr = Carbon::now()->format('H:i').' WIB';

        $cbm = $data['kpis']['cbm'];
        $painting = $data['kpis']['painting'];
        $cleaning = $data['kpis']['cleaning'];
        $pareto = $data['pareto'];

        $out = [];
        $out[] = '*LION GROUP AIRCRAFT CABIN TECHNICIAN*';
        $out[] = '*EXECUTIVE PRODUCTION MEETING REPORT*';
        $out[] = "📅 Periode: {$periodStr}";
        $out[] = "📍 Cakupan: {$stationStr}";
        $out[] = "🕒 Waktu Lapor: {$nowStr}";
        $out[] = '----------------------------------------';
        $out[] = '';

        // Pilar 1: CBM
        $out[] = '🛠️ *1. CABIN MAINTENANCE (CBM)*';
        $out[] = "• Total Work   : *{$cbm['total']}* item";
        $out[] = "• Closed       : *{$cbm['closed']}* ({$cbm['rate']}%)";
        $out[] = "• Open         : *{$cbm['open']}* item";
        $out[] = "• Target Achv  : *{$cbm['target_achievement']}%* (NSRDI Closed: {$cbm['nsrdi_closed']})";
        $out[] = '';

        // Pilar 2: Painting
        $out[] = '🎨 *2. AIRCRAFT PAINTING*';
        $out[] = "• Total Job    : *{$painting['total']}* pekerjaan";
        $out[] = "• Planned (DJA): *{$painting['planned']}* | Unplanned: *{$painting['unplanned']}*";
        $out[] = "• Closed       : *{$painting['closed']}* ({$painting['rate']}%)";
        $out[] = '';

        // Pilar 3: Cleaning
        $out[] = '🧼 *3. AIRCRAFT CLEANING (AIEC)*';
        $out[] = "• Total Armada : *{$cleaning['total']}* pesawat";
        $out[] = "• Deep Clean   : DCI (*{$cleaning['dci']}*) | DCE (*{$cleaning['dce']}*)";
        $out[] = "• Transit / Reg: Transit (*{$cleaning['transit']}*) | General (*{$cleaning['general']}*)";
        $out[] = '';

        // Pareto & Highlight
        $out[] = '📊 *HIGHLIGHT & ANALISIS OPERASIONAL*';
        if (! empty($pareto['top_defects'])) {
            $topDef = collect($pareto['top_defects'])->first();
            $out[] = "• Defect Terbanyak : {$topDef['name']} ({$topDef['count']} kasus - {$topDef['percentage']}%)";
        }
        if (! empty($pareto['top_reasons_open'])) {
            $topR = collect($pareto['top_reasons_open'])->first();
            $out[] = "• Kendala Utama    : {$topR['reason']} ({$topR['count']} item)";
        }
        if (! empty($pareto['top_aircraft'])) {
            $topAc = collect($pareto['top_aircraft'])->take(3)->pluck('reg')->implode(', ');
            $out[] = "• Armada Tersibuk  : {$topAc}";
        }
        $out[] = '----------------------------------------';
        $out[] = '_Laporan resmi hasil kalkulasi Bank Data Terpadu_';

        $evidences = $this->getEvidenceSamples($startDate, $endDate);

        return [
            'module' => 'executive',
            'title' => 'Laporan Eksekutif Produksi 3 Pilar',
            'report_text' => implode("\n", $out),
            'evidence_count' => count($evidences),
            'evidence_samples' => $evidences,
        ];
    }

    /**
     * 2. Cabin Maintenance (CBM / COD) Report.
     */
    protected function generateCbmReport(string $startDate, string $endDate, ?string $station): array
    {
        $dateFormatted = Carbon::parse($startDate)->format('d M Y');
        $nowStr = Carbon::now()->format('H:i').' WIB';

        // Query DJA data for this date
        $djaQuery = DailyJobAssignment::whereDate('date', $startDate);
        if ($station) {
            $djaQuery->where('station', $station);
        }
        $djaRecords = $djaQuery->get();

        $woDeploy = $djaRecords->where('type', 'WO')->count();
        $woClosed = $djaRecords->where('type', 'WO')->where('status', 'CLOSED')->count();
        $woOpen = $woDeploy - $woClosed;

        $dmiDeploy = $djaRecords->where('type', 'DMI')->count();
        $dmiClosed = $djaRecords->where('type', 'DMI')->where('status', 'CLOSED')->count();
        $dmiOpen = $dmiDeploy - $dmiClosed;

        $nsrdiDeploy = $djaRecords->where('type', 'NSRDI')->count();
        $nsrdiClosed = $djaRecords->where('type', 'NSRDI')->where('status', 'CLOSED')->count();
        $nsrdiOpen = $nsrdiDeploy - $nsrdiClosed;

        $totalDeploy = $woDeploy + $dmiDeploy + $nsrdiDeploy;
        $totalClosed = $woClosed + $dmiClosed + $nsrdiClosed;
        $totalOpen = $totalDeploy - $totalClosed;
        $rate = $totalDeploy > 0 ? round(($totalClosed / $totalDeploy) * 100, 1) : 0;

        $out = [];
        $out[] = '*LION GROUP AIRCRAFT CABIN TECHNICIAN*';
        $out[] = '*CABIN ON-DUTY (COD) PRODUCTION REPORT*';
        $out[] = "📅 Tanggal  : {$dateFormatted}";
        $out[] = '📍 Stasiun  : '.($station ?: 'All Stations (Nasional)');
        $out[] = "🕒 Waktu    : {$nowStr}";
        $out[] = '========================================';
        $out[] = '';

        // Ringkasan
        $out[] = "🎯 *TOTAL PRODUCTION: {$totalClosed}/{$totalDeploy} ({$rate}%)*";
        $out[] = "• TOTAL DEPLOY : *{$totalDeploy}*";
        $out[] = "• TOTAL CLOSED : *{$totalClosed}*";
        $out[] = "• TOTAL OPEN   : *{$totalOpen}*";
        $out[] = '';

        // DJA R01 / WO
        $out[] = '> *1. DJA R01 / WO*';
        $out[] = "Deploy: {$woDeploy} | Closed: {$woClosed} | Open: {$woOpen}";
        $out[] = '';

        // DMI CBM
        $out[] = '> *2. DMI CBM*';
        $out[] = "Deploy: {$dmiDeploy} | Closed: {$dmiClosed} | Open: {$dmiOpen}";
        $out[] = '';

        // NSRDI CBM
        $out[] = '> *3. NSRDI CBM*';
        $out[] = "Deploy: {$nsrdiDeploy} | Closed: {$nsrdiClosed} | Open: {$nsrdiOpen}";
        $out[] = '';

        // Station Breakdown jika nasional
        if (! $station) {
            $stationGroups = $djaRecords->groupBy('station')->sortByDesc(fn ($g) => $g->count())->take(6);
            if ($stationGroups->isNotEmpty()) {
                $out[] = '📌 *TOP STASIUN AKTIF:*';
                foreach ($stationGroups as $stName => $stList) {
                    $stClosed = $stList->where('status', 'CLOSED')->count();
                    $stTotal = $stList->count();
                    $out[] = "• {$stName}: {$stClosed}/{$stTotal} closed";
                }
                $out[] = '';
            }
        }

        $out[] = '========================================';
        $out[] = '_Bank Data CBM Operasional - Ready for Briefing_';

        $evidences = $this->getEvidenceSamples($startDate, $endDate);

        return [
            'module' => 'cbm',
            'title' => 'Laporan Cabin On-Duty (CBM)',
            'report_text' => implode("\n", $out),
            'evidence_count' => count($evidences),
            'evidence_samples' => $evidences,
        ];
    }

    /**
     * 3. Aircraft Painting Production Report.
     */
    protected function generatePaintingReport(string $startDate, string $endDate, ?string $station): array
    {
        $dateFormatted = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d M Y')
            : Carbon::parse($startDate)->format('d/m/Y').' - '.Carbon::parse($endDate)->format('d/m/Y');

        $keywords = ['PAINT', 'REPAINT', 'PPO', 'PEEL', 'LIVERY', 'DECAL', 'PLACARD', 'TOUCH UP', 'SCRATCH'];

        $cmlQuery = CmlLog::whereBetween('defect_date', [$startDate, $endDate])
            ->where(function ($q) use ($keywords) {
                $q->where('category', 'LIKE', '%PAINT%');
                foreach ($keywords as $kw) {
                    $q->orWhere('defect_description', 'LIKE', "%{$kw}%");
                }
            });

        if ($station) {
            $cmlQuery->where('station', $station);
        }
        $cmlRecords = $cmlQuery->get();

        $djaQuery = DailyJobAssignment::whereBetween('date', [$startDate, $endDate])
            ->where(function ($q) use ($keywords) {
                $q->where('type', 'PAINTING');
                foreach ($keywords as $kw) {
                    $q->orWhere('description', 'LIKE', "%{$kw}%");
                }
            });

        if ($station) {
            $djaQuery->where('station', $station);
        }
        $djaRecords = $djaQuery->get();

        $plannedCount = $djaRecords->count();
        $unplannedCount = max(0, $cmlRecords->count() - $plannedCount);
        $totalJobs = $cmlRecords->count() ?: ($plannedCount + $unplannedCount);

        // Group by type
        $types = [];
        foreach ($cmlRecords as $row) {
            $type = ProductionExecutiveService::categorizePaintingJob($row->defect_description ?? '');
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        arsort($types);

        // Group by tail
        $aircrafts = $cmlRecords->pluck('aircraft_registration')->filter()->unique()->values()->take(8)->all();

        $out = [];
        $out[] = '*LION GROUP AIRCRAFT CABIN TECHNICIAN*';
        $out[] = '*AIRCRAFT PAINTING PRODUCTION REPORT*';
        $out[] = "📅 Periode  : {$dateFormatted}";
        $out[] = '📍 Stasiun  : '.($station ?: 'All Stations (Nasional)');
        $out[] = '🕒 Waktu    : '.Carbon::now()->format('H:i').' WIB';
        $out[] = '========================================';
        $out[] = '';
        $out[] = "🎨 *TOTAL PEKERJAAN : {$totalJobs} JOB*";
        $out[] = "• Planned (Sesuai DJA) : *{$plannedCount}*";
        $out[] = "• Unplanned (Ad-hoc)   : *{$unplannedCount}*";
        $out[] = '';

        if (! empty($types)) {
            $out[] = '📋 *RINCIAN JENIS PENGECATAN:*';
            foreach ($types as $tName => $tCnt) {
                $out[] = "• {$tName}: *{$tCnt}*";
            }
            $out[] = '';
        }

        if (! empty($aircrafts)) {
            $out[] = '✈️ *ARMADA TERKAIT:*';
            $out[] = '• '.implode(', ', $aircrafts);
            $out[] = '';
        }

        $out[] = '========================================';
        $out[] = '_Painting & Placard Division - Bank Data Terpadu_';

        $evidences = $this->getEvidenceSamples($startDate, $endDate);

        return [
            'module' => 'painting',
            'title' => 'Laporan Produksi Aircraft Painting',
            'report_text' => implode("\n", $out),
            'evidence_count' => count($evidences),
            'evidence_samples' => $evidences,
        ];
    }

    /**
     * 4. Aircraft Cleaning (AIEC) Production Report.
     */
    protected function generateCleaningReport(string $startDate, string $endDate, ?string $station): array
    {
        $dateFormatted = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d M Y')
            : Carbon::parse($startDate)->format('d/m/Y').' - '.Carbon::parse($endDate)->format('d/m/Y');

        $query = AircraftCleaning::whereBetween('date', [$startDate, $endDate]);
        if ($station) {
            $query->where('station', $station);
        }
        $records = $query->get();

        $deep = $records->filter(fn ($r) => preg_match('/DEEP/i', $r->type ?? ''))->count();
        $ron = $records->filter(fn ($r) => preg_match('/RON/i', $r->type ?? ''))->count();
        $transit = $records->filter(fn ($r) => preg_match('/TRANSIT/i', $r->type ?? ''))->count();
        $total = $records->count();

        // Check station capability warnings
        $warnings = [];
        foreach ($records->groupBy('station') as $stName => $stList) {
            if ($stName && ! StationCapabilityService::hasCapability($stName)) {
                $warnings[] = "{$stName} ({$stList->count()} ac)";
            }
        }

        $out = [];
        $out[] = '*LION GROUP AIRCRAFT CABIN TECHNICIAN*';
        $out[] = '*AIRCRAFT CLEANING (AIEC) PRODUCTION REPORT*';
        $out[] = "📅 Periode  : {$dateFormatted}";
        $out[] = '📍 Stasiun  : '.($station ?: 'All Stations (Nasional)');
        $out[] = '🕒 Waktu    : '.Carbon::now()->format('H:i').' WIB';
        $out[] = '========================================';
        $out[] = '';
        $out[] = "🧼 *TOTAL ARMADA DIBERSIHKAN: {$total} PESAWAT*";
        $out[] = "• Deep Clean : *{$deep}* pesawat";
        $out[] = "• RON Clean  : *{$ron}* pesawat";
        $out[] = "• Transit    : *{$transit}* pesawat";
        $out[] = '';

        if (! empty($warnings)) {
            $out[] = '⚠️ *PERINGATAN STASIUN DI LUAR HUB CAPABILITY:*';
            $out[] = '• Terdeteksi pekerjaan di: '.implode(', ', $warnings);
            $out[] = '';
        }

        // Top Stations
        $topStations = $records->groupBy('station')->sortByDesc(fn ($g) => $g->count())->take(5);
        if ($topStations->isNotEmpty()) {
            $out[] = '📍 *STASIUN TERBANYAK:*';
            foreach ($topStations as $sName => $sList) {
                $out[] = "• {$sName}: {$sList->count()} pesawat";
            }
            $out[] = '';
        }

        $out[] = '========================================';
        $out[] = '_AIEC Cleaning Division - Bank Data Terpadu_';

        $evidences = $this->getEvidenceSamples($startDate, $endDate);

        return [
            'module' => 'cleaning',
            'title' => 'Laporan Produksi Aircraft Cleaning (AIEC)',
            'report_text' => implode("\n", $out),
            'evidence_count' => count($evidences),
            'evidence_samples' => $evidences,
        ];
    }

    /**
     * 5. CML Log / NSRDI Defect Report.
     */
    protected function generateCmlReport(string $startDate, string $endDate, ?string $station): array
    {
        $dateFormatted = $startDate === $endDate
            ? Carbon::parse($startDate)->format('d M Y')
            : Carbon::parse($startDate)->format('d/m/Y').' - '.Carbon::parse($endDate)->format('d/m/Y');

        $query = CmlLog::whereBetween('defect_date', [$startDate, $endDate]);
        if ($station) {
            $query->where('station', $station);
        }
        $records = $query->get();

        $totalDefects = $records->count();
        $closed = $records->where('status', 'CLOSED')->count();
        $open = $totalDefects - $closed;
        $rate = $totalDefects > 0 ? round(($closed / $totalDefects) * 100, 1) : 0;

        // Operator
        $operators = $records->groupBy(fn ($r) => strtoupper(substr($r->aircraft_registration ?? '', 0, 2)))
            ->map(fn ($g) => $g->count());

        $out = [];
        $out[] = '*LION GROUP AIRCRAFT CABIN TECHNICIAN*';
        $out[] = '*REKAPITULASI DEFECT CML & NSRDI*';
        $out[] = "📅 Periode : {$dateFormatted}";
        $out[] = '📍 Stasiun : '.($station ?: 'All Stations (Nasional)');
        $out[] = '🕒 Waktu   : '.Carbon::now()->format('H:i').' WIB';
        $out[] = '========================================';
        $out[] = '';
        $out[] = "📌 *TOTAL TEMUAN DEFECT: {$totalDefects} KASUS*";
        $out[] = "• Closed : *{$closed}* ({$rate}%)";
        $out[] = "• Open   : *{$open}* item";
        $out[] = '';

        if ($operators->isNotEmpty()) {
            $out[] = '✈️ *DISTRIBUSI OPERATOR:*';
            foreach ($operators as $op => $c) {
                $opName = match ($op) {
                    'PK' => 'Lion Air / Batik / SAJ (PK)',
                    '9M' => 'Batik Air Malaysia (9M)',
                    'HS' => 'Thai Lion Air (HS)',
                    default => "Operator {$op}",
                };
                $out[] = "• {$opName}: *{$c}* defect";
            }
            $out[] = '';
        }

        $out[] = '========================================';
        $out[] = '_Cabin Defect & CML Logs - Bank Data Terpadu_';

        $evidences = $this->getEvidenceSamples($startDate, $endDate);

        return [
            'module' => 'cml',
            'title' => 'Rekapitulasi Defect CML & NSRDI',
            'report_text' => implode("\n", $out),
            'evidence_count' => count($evidences),
            'evidence_samples' => $evidences,
        ];
    }

    /**
     * Get evidence photo samples if available for attachment/reference.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getEvidenceSamples(string $startDate, string $endDate): array
    {
        return EvidenceSubmission::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ])
            ->whereNotNull('cloud_url')
            ->latest()
            ->take(6)
            ->get(['id', 'doc_type', 'doc_no', 'cloud_url', 'raw_caption'])
            ->toArray();
    }
}
