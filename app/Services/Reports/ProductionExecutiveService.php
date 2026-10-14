<?php

namespace App\Services\Reports;

use App\Models\AircraftCleaning;
use App\Models\Airport;
use App\Models\CapacityTarget;
use App\Models\CmlLog;
use App\Models\DmiLog;
use App\Models\NsrdiLog;
use App\Models\WoLog;
use App\Services\Aiec\StationCapabilityService;
use App\Support\TaskStation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductionExecutiveService
{
    /**
     * Categorize a CML defect description into standard cabin/painting systems.
     */
    public static function categorizeDefect(string $description): string
    {
        $d = strtoupper(trim($description));
        if ($d === '') {
            return 'General Cabin';
        }

        if (preg_match('/\b(PAINT|REPAINT|PPO|PEEL|LIVERY|DECAL|PLACARD|SCRATCH|FADED)\b/i', $d)) {
            return 'Painting & Placard';
        }
        if (preg_match('/\b(SEAT|RECLINE|TRAY|TABLE|ARMREST|CUSHION|BELT|BOTTOM|BACKREST)\b/i', $d)) {
            return 'Seat & Mechanism';
        }
        if (preg_match('/\b(LAV|LAVATORY|TOILET|FLUSH|SINK|FAUCET|MIRROR|SOAP|SHROUD)\b/i', $d)) {
            return 'Lavatory System';
        }
        if (preg_match('/\b(GALLEY|OVEN|COFFEE|CART|LATCH|BOILER|STANDARD\s*UNIT)\b/i', $d)) {
            return 'Galley Equipment';
        }
        if (preg_match('/\b(BIN|OVERHEAD|PSU|GASPER|LIGHT|READING|CALL|BULB)\b/i', $d)) {
            return 'Overhead Bin & PSU';
        }
        if (preg_match('/\b(CARPET|FLOOR|SIDEWALL|CEILING|CURTAIN|PANEL|DADO)\b/i', $d)) {
            return 'Flooring & Interior Furnishing';
        }
        if (preg_match('/\b(VEST|OXYGEN|MASK|POB|FLASHLIGHT|MEGAPHONE|SLIDE|FIRST\s*AID|AXE)\b/i', $d)) {
            return 'Emergency & Safety Equipment';
        }
        if (preg_match('/\b(BEAUTIFICATION|CLEAN|POLISH|WASH)\b/i', $d)) {
            return 'Beautification & Cosmetic';
        }

        return 'Other Defect';
    }

    /**
     * Categorize painting work description into specific job types.
     */
    public static function categorizePaintingJob(string $description): string
    {
        $d = strtoupper(trim($description));
        if (preg_match('/\b(PPO|PEEL|BLISTER|FLAKING)\b/i', $d)) {
            return 'Paint Peel Off (PPO)';
        }
        if (preg_match('/\b(LIVERY|LOGO|CHEATLINE|DECAL)\b/i', $d)) {
            return 'Livery & Exterior Marking';
        }
        if (preg_match('/\b(PLACARD|CAUTION|WARNING|MARKING)\b/i', $d)) {
            return 'Placards & Signage';
        }
        if (preg_match('/\b(TOUCH\s*UP|SCRATCH|CORROSION)\b/i', $d)) {
            return 'Paint Touch Up';
        }
        if (preg_match('/\b(INT|GALLEY|CABIN|FRAME)\b/i', $d)) {
            return 'Interior Painting';
        }

        return 'Repaint / General Paint';
    }

    /**
     * The station filter lets a task through when either station matches; this keeps it only where the shared rule
     * puts it (planned: plan station, unplanned: actual station), so one task is never counted at two stations.
     */
    private function placedAt(Collection $logs, ?string $station): Collection
    {
        if (! $station) {
            return $logs;
        }
        $station = strtoupper($station);

        return $logs->filter(fn ($r) => TaskStation::of($r) === $station)->values();
    }

    /**
     * Build full consolidated production statistics for executive meeting.
     *
     * @return array<string, mixed>
     */
    public function getProductionReport(string $startDate, string $endDate, ?string $filterStation = null): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();
        $station = $filterStation ? strtoupper(trim($filterStation)) : null;

        $daysCount = max(1, (int) $start->diffInDays($end) + 1);

        // Target capacity per day from CapacityTarget
        $dailyTarget = (int) CapacityTarget::sum('target_nsrdi');
        if ($dailyTarget <= 0) {
            $dailyTarget = 55; // default fallback (JT: 10, ID: 15, IU: 18, IW: 12)
        }
        $periodTarget = $dailyTarget * $daysCount;

        // ----------------------------------------------------
        // 1. PILAR CABIN MAINTENANCE (CBM)
        // ----------------------------------------------------
        $nsrdiCbmQ = NsrdiLog::where('is_submitted', true)
            ->where(function ($q) {
                $q->where('category', 'CBM')->orWhereNull('category');
            })
            ->where(function ($q) use ($startStr, $endStr) {
                $q->where(fn ($x) => $x->whereDate('plan_date', '>=', $startStr)->whereDate('plan_date', '<=', $endStr))
                    ->orWhere(fn ($x) => $x->whereDate('report_date', '>=', $startStr)->whereDate('report_date', '<=', $endStr))
                    ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr));
            });
        if ($station) {
            $nsrdiCbmQ->where(fn ($q) => $q->where('act_station', $station)->orWhere('plan_station', $station));
        }
        $nsrdiCbm = $this->placedAt($nsrdiCbmQ->with('dailyJobAssignment')->get(), $station);

        $woQ = WoLog::where('is_submitted', true)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->where(fn ($x) => $x->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr))
                    ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr));
            });
        if ($station) {
            $woQ->where(fn ($q) => $q->where('act_station', $station)->orWhere('plan_station', $station));
        }
        $wo = $this->placedAt($woQ->with('dailyJobAssignment')->get(), $station);

        $dmiQ = DmiLog::where('is_submitted', true)
            ->where(function ($q) use ($startStr, $endStr) {
                $q->where(fn ($x) => $x->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr))
                    ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr));
            });
        if ($station) {
            $dmiQ->where(fn ($q) => $q->where('act_station', $station)->orWhere('plan_station', $station));
        }
        $dmi = $this->placedAt($dmiQ->with('dailyJobAssignment')->get(), $station);

        $cmlQ = CmlLog::where('kind', 'CML')->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr);
        if ($station) {
            $cmlQ->where('station', $station);
        }
        $cml = $cmlQ->get();

        // Separate CML into Cabin vs Painting
        $cmlCabin = $cml->filter(fn ($r) => self::categorizeDefect($r->description ?? '') !== 'Painting & Placard');
        $cmlPainting = $cml->filter(fn ($r) => self::categorizeDefect($r->description ?? '') === 'Painting & Placard');

        $cbmTotal = $nsrdiCbm->count() + $wo->count() + $dmi->count() + $cmlCabin->count();
        $cbmClosed = $nsrdiCbm->where('status', 'Closed')->count()
            + $wo->where('status', 'Closed')->count()
            + $dmi->where('status', 'Closed')->count()
            + $cmlCabin->where('status', 'Closed')->count();
        $cbmOpen = $cbmTotal - $cbmClosed;
        $cbmClosureRate = $cbmTotal > 0 ? round(($cbmClosed / $cbmTotal) * 100, 1) : 0.0;

        $cbmPlannedCount = $nsrdiCbm->whereNotNull('dja_id')->count() + $wo->whereNotNull('dja_id')->count() + $dmi->whereNotNull('dja_id')->count();
        $cbmUnplannedCount = $nsrdiCbm->whereNull('dja_id')->count() + $wo->whereNull('dja_id')->count() + $dmi->whereNull('dja_id')->count() + $cmlCabin->count();

        // NSRDI Target vs Achievement
        $nsrdiDeployed = $nsrdiCbm->count();
        $nsrdiClosed = $nsrdiCbm->where('status', 'Closed')->count();
        $targetAchievementRate = $periodTarget > 0 ? round(($nsrdiClosed / $periodTarget) * 100, 1) : 0.0;

        // ----------------------------------------------------
        // 2. PILAR AIRCRAFT PAINTING
        // ----------------------------------------------------
        $nsrdiPaintQ = NsrdiLog::where('is_submitted', true)
            ->where('category', 'PAINTING')
            ->where(function ($q) use ($startStr, $endStr) {
                $q->where(fn ($x) => $x->whereDate('plan_date', '>=', $startStr)->whereDate('plan_date', '<=', $endStr))
                    ->orWhere(fn ($x) => $x->whereDate('report_date', '>=', $startStr)->whereDate('report_date', '<=', $endStr))
                    ->orWhereHas('dailyJobAssignment', fn ($d) => $d->whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr));
            });
        if ($station) {
            $nsrdiPaintQ->where(fn ($q) => $q->where('act_station', $station)->orWhere('plan_station', $station));
        }
        $nsrdiPaint = $this->placedAt($nsrdiPaintQ->with('dailyJobAssignment')->get(), $station);

        $paintingTotal = $nsrdiPaint->count() + $cmlPainting->count();
        $paintingClosed = $nsrdiPaint->where('status', 'Closed')->count() + $cmlPainting->where('status', 'Closed')->count();
        $paintingOpen = $paintingTotal - $paintingClosed;
        $paintingClosureRate = $paintingTotal > 0 ? round(($paintingClosed / $paintingTotal) * 100, 1) : 0.0;
        $paintingPlanned = $nsrdiPaint->whereNotNull('dja_id')->count();
        $paintingUnplanned = $nsrdiPaint->whereNull('dja_id')->count() + $cmlPainting->count();

        // ----------------------------------------------------
        // 3. PILAR AIRCRAFT CLEANING (AIEC)
        // ----------------------------------------------------
        $cleanQ = AircraftCleaning::whereDate('date', '>=', $startStr)->whereDate('date', '<=', $endStr);
        if ($station) {
            $cleanQ->where('station', $station);
        }
        $cleanings = $cleanQ->get();

        $cleanTotal = $cleanings->count();
        $cleanClosed = $cleanings->filter(fn ($c) => strtolower((string) $c->status) === 'closed' || strtolower((string) $c->status) === 'selesai')->count();
        $cleanOpen = $cleanTotal - $cleanClosed;
        $cleanClosureRate = $cleanTotal > 0 ? round(($cleanClosed / $cleanTotal) * 100, 1) : 0.0;

        $dciCount = $cleanings->where('type', 'DCI')->count();
        $dceCount = $cleanings->where('type', 'DCE')->count();
        $generalCount = $cleanings->where('type', 'General')->count();
        $transitCount = $cleanings->where('type', 'Transit')->count();
        $dbiCount = $cleanings->where('type', 'DBI')->count();
        $otherCleanCount = $cleanTotal - ($dciCount + $dceCount + $generalCount + $transitCount + $dbiCount);

        // Daily DCIE capability total is 12 DCI + 9 DCE
        $dciTargetPeriod = 12 * $daysCount;
        $dceTargetPeriod = 9 * $daysCount;
        $dciAchievementRate = $dciTargetPeriod > 0 ? round(($dciCount / $dciTargetPeriod) * 100, 1) : 0.0;
        $dceAchievementRate = $dceTargetPeriod > 0 ? round(($dceCount / $dceTargetPeriod) * 100, 1) : 0.0;

        // Man Hours & Man Power across all
        $totalManHours = round(
            $wo->sum('man_hour') +
            $cml->sum('man_hour') +
            $cleanings->sum('man_hour'),
            1
        );

        // ----------------------------------------------------
        // 4. MATRIX STATIONS: CBM
        // ----------------------------------------------------
        $airports = Airport::where('status', 'Aktif')->orderBy('kode')->pluck('kode')->all();
        if ($station && ! in_array($station, $airports, true)) {
            $airports[] = $station;
        }

        $cbmStationMatrix = [];
        foreach ($airports as $code) {
            $sta = strtoupper($code);
            if ($station && $sta !== $station) {
                continue;
            }

            $sNsrdi = $nsrdiCbm->filter(fn ($r) => TaskStation::of($r) === $sta);
            $sWo = $wo->filter(fn ($r) => TaskStation::of($r) === $sta);
            $sDmi = $dmi->filter(fn ($r) => TaskStation::of($r) === $sta);
            $sCml = $cmlCabin->filter(fn ($r) => strtoupper(trim($r->station ?: '')) === $sta);

            $sTotal = $sNsrdi->count() + $sWo->count() + $sDmi->count() + $sCml->count();
            if ($sTotal === 0 && ! $station) {
                continue; // Skip stations without activity to keep matrix concise
            }

            $sClosed = $sNsrdi->where('status', 'Closed')->count() +
                $sWo->where('status', 'Closed')->count() +
                $sDmi->where('status', 'Closed')->count() +
                $sCml->where('status', 'Closed')->count();

            $cbmStationMatrix[$sta] = [
                'station' => $sta,
                'nsrdi' => $sNsrdi->count(),
                'nsrdi_closed' => $sNsrdi->where('status', 'Closed')->count(),
                'wo' => $sWo->count(),
                'wo_closed' => $sWo->where('status', 'Closed')->count(),
                'dmi' => $sDmi->count(),
                'dmi_closed' => $sDmi->where('status', 'Closed')->count(),
                'cml' => $sCml->count(),
                'cml_closed' => $sCml->where('status', 'Closed')->count(),
                'total' => $sTotal,
                'closed' => $sClosed,
                'open' => $sTotal - $sClosed,
                'rate' => $sTotal > 0 ? round(($sClosed / $sTotal) * 100, 1) : 0.0,
            ];
        }

        // Sort by total desc
        uasort($cbmStationMatrix, fn ($a, $b) => $b['total'] <=> $a['total']);

        // ----------------------------------------------------
        // 5. MATRIX STATIONS: PAINTING
        // ----------------------------------------------------
        $allPaintItems = collect()->merge($nsrdiPaint)->merge($cmlPainting);
        $paintJobTypes = ['Paint Peel Off (PPO)', 'Livery & Exterior Marking', 'Placards & Signage', 'Paint Touch Up', 'Interior Painting', 'Repaint / General Paint'];

        $paintingMatrix = [];
        foreach ($allPaintItems as $p) {
            $sta = strtoupper(trim($p->act_station ?: ($p->plan_station ?: ($p->station ?: 'CGK'))));
            if ($station && $sta !== $station) {
                continue;
            }

            $type = self::categorizePaintingJob($p->description ?? '');
            if (! isset($paintingMatrix[$sta])) {
                $paintingMatrix[$sta] = [
                    'station' => $sta,
                    'types' => array_fill_keys($paintJobTypes, 0),
                    'total' => 0,
                    'closed' => 0,
                    'open' => 0,
                ];
            }

            $paintingMatrix[$sta]['types'][$type]++;
            $paintingMatrix[$sta]['total']++;
            if (strtolower(trim($p->status ?? '')) === 'closed') {
                $paintingMatrix[$sta]['closed']++;
            } else {
                $paintingMatrix[$sta]['open']++;
            }
        }
        uasort($paintingMatrix, fn ($a, $b) => $b['total'] <=> $a['total']);

        // ----------------------------------------------------
        // 6. MATRIX STATIONS: CLEANING CAPABILITY HUB
        // ----------------------------------------------------
        $cleaningHubMetrics = StationCapabilityService::getStationMetrics(null, $startStr, $endStr);
        if ($station) {
            $cleaningHubMetrics = $cleaningHubMetrics->filter(fn ($m) => $m['code'] === $station)->values();
        }

        // ----------------------------------------------------
        // 7. PARETO DEFECT CABIN & TOP REASON OPEN
        // ----------------------------------------------------
        // Defect Pareto from CML + WO
        $defectCounts = [];
        foreach ($cmlCabin as $c) {
            $cat = self::categorizeDefect($c->description ?? '');
            $defectCounts[$cat] = ($defectCounts[$cat] ?? 0) + 1;
        }
        foreach ($wo as $w) {
            $cat = $w->wo_category ?: self::categorizeDefect($w->description ?? '');
            $defectCounts[$cat] = ($defectCounts[$cat] ?? 0) + 1;
        }
        arsort($defectCounts);
        $topDefects = collect($defectCounts)->take(6)->map(fn ($count, $name) => [
            'name' => $name,
            'count' => $count,
            'percentage' => $cbmTotal > 0 ? round(($count / $cbmTotal) * 100, 1) : 0,
        ])->values()->all();

        // Reason Open Pareto (Why items are not closed)
        $reasons = [];
        $openItems = collect()
            ->merge($nsrdiCbm->where('status', 'Open'))
            ->merge($wo->where('status', 'Open'))
            ->merge($dmi->where('status', 'Open'))
            ->merge($nsrdiPaint->where('status', 'Open'));

        foreach ($openItems as $item) {
            $r = trim($item->reason_open ?: ($item->hold_reason_category ?: ($item->code_reason ?: '')));
            if (! $r) {
                $r = 'Unspecified / In Progress';
            }
            $reasons[$r] = ($reasons[$r] ?? 0) + 1;
        }
        arsort($reasons);
        $topReasonsOpen = collect($reasons)->take(5)->map(fn ($count, $name) => [
            'reason' => $name,
            'count' => $count,
        ])->values()->all();

        // Top 5 Aircraft with highest active defects/work
        $acCounts = [];
        $allWork = collect()->merge($nsrdiCbm)->merge($wo)->merge($dmi)->merge($cml)->merge($nsrdiPaint);
        foreach ($allWork as $item) {
            $reg = strtoupper(trim($item->aircraft_registration ?? ''));
            if ($reg !== '' && $reg !== '–' && $reg !== '-') {
                $acCounts[$reg] = ($acCounts[$reg] ?? 0) + 1;
            }
        }
        arsort($acCounts);
        $topAircraft = collect($acCounts)->take(5)->map(fn ($count, $reg) => [
            'reg' => $reg,
            'count' => $count,
        ])->values()->all();

        return [
            'period' => [
                'start' => $startStr,
                'end' => $endStr,
                'days' => $daysCount,
                'label' => $start->format('d M Y').($startStr !== $endStr ? ' - '.$end->format('d M Y') : ''),
            ],
            'station' => $station,
            'kpis' => [
                'cbm' => [
                    'total' => $cbmTotal,
                    'closed' => $cbmClosed,
                    'open' => $cbmOpen,
                    'rate' => $cbmClosureRate,
                    'planned' => $cbmPlannedCount,
                    'unplanned' => $cbmUnplannedCount,
                    'target_period' => $periodTarget,
                    'target_daily' => $dailyTarget,
                    'nsrdi_closed' => $nsrdiClosed,
                    'target_achievement' => $targetAchievementRate,
                ],
                'painting' => [
                    'total' => $paintingTotal,
                    'closed' => $paintingClosed,
                    'open' => $paintingOpen,
                    'rate' => $paintingClosureRate,
                    'planned' => $paintingPlanned,
                    'unplanned' => $paintingUnplanned,
                ],
                'cleaning' => [
                    'total' => $cleanTotal,
                    'closed' => $cleanClosed,
                    'open' => $cleanOpen,
                    'rate' => $cleanClosureRate,
                    'dci' => $dciCount,
                    'dce' => $dceCount,
                    'general' => $generalCount,
                    'transit' => $transitCount,
                    'dbi' => $dbiCount,
                    'other' => $otherCleanCount,
                    'dci_target' => $dciTargetPeriod,
                    'dce_target' => $dceTargetPeriod,
                    'dci_rate' => $dciAchievementRate,
                    'dce_rate' => $dceAchievementRate,
                ],
                'man_hours' => $totalManHours,
            ],
            'matrices' => [
                'cbm_stations' => $cbmStationMatrix,
                'painting_stations' => $paintingMatrix,
                'paint_job_types' => $paintJobTypes,
                'cleaning_hubs' => $cleaningHubMetrics,
            ],
            'pareto' => [
                'top_defects' => $topDefects,
                'top_reasons_open' => $topReasonsOpen,
                'top_aircraft' => $topAircraft,
            ],
        ];
    }
}
