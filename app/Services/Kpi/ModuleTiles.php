<?php

namespace App\Services\Kpi;

use App\Models\Aircraft;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Document;
use App\Models\Employee;
use App\Models\LeaderReportImport;
use App\Models\LgtRecord;
use App\Models\RegistryRecord;
use App\Models\SyncSetting;
use App\Models\User;
use App\Services\Attendance\AttendanceScorer;
use App\Services\Compliance\ComplianceReport;
use App\Support\ExpiryStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One tile per menu of the sidebar, so the dashboard shows something for every module the user can open.
 * A tile is [group, title, value, sub, tone, route, optional permission]. Tiles the user has no permission for are left
 * out, and where a module has stations the numbers follow the user's station scope.
 * A tile that fails to compute is dropped instead of breaking the page.
 */
class ModuleTiles
{
    public const GROUPS = [
        'production' => 'Production',
        'cleaning' => 'Cleaning & LGT',
        'flight' => 'Capacity & Movement',
        'people' => 'People & Compliance',
        'assets' => 'Asset & Inventory',
        'system' => 'Data & Sistem',
    ];

    /**
     * @param  array<int, string>|null  $stations
     * @return array<string, array<int, array<string, mixed>>> group => tiles
     */
    public function build(User $user, ReportPeriod $period, ?array $stations = null): array
    {
        $from = $period->from->toDateString();
        $to = $period->to->toDateString();
        $tiles = [];

        $add = function (string $group, string $key, callable $make, ?string $permission = null) use (&$tiles, $user) {
            if ($permission && ! $user->can($permission)) {
                return;
            }
            try {
                $tile = $make();
            } catch (\Throwable $e) {
                report($e);

                return;
            }
            if ($tile) {
                $tiles[$group][] = ['key' => $key] + $tile;
            }
        };

        $station = fn ($q, string $expr) => $stations === null ? $q : $q->whereIn(DB::raw($expr), $stations);
        $pct = fn (int $done, int $total) => $total ? round($done / $total * 100, 1) : null;
        $tone = fn (?float $p) => match (AchievementStatus::for($p, 100)) {
            'green' => 'green', 'yellow' => 'yellow', 'red' => 'red', default => 'none',
        };
        $closed = fn ($q) => $q->whereRaw("LOWER(status) = 'closed'");

        // ── Production: planned work of the DJA (deployed in the period) ──
        foreach (['wo' => ['WO', 'wo_logs', 'modules.wo'], 'dmi' => ['DMI', 'dmi_logs', 'modules.dmi'], 'nsrdi' => ['NSRDI', 'nsrdi_logs', 'modules.nsrdi']] as $key => [$label, $table, $route]) {
            $add('production', $key, function () use ($table, $label, $route, $from, $to, $station, $pct, $tone) {
                $q = DB::table("$table as l")->join('daily_job_assignments as d', 'd.id', '=', 'l.dja_id')
                    ->whereDate('d.date', '>=', $from)->whereDate('d.date', '<=', $to);
                $q = $station($q, 'COALESCE(l.act_station, l.plan_station)');
                $total = (clone $q)->count();
                $done = (clone $q)->whereRaw("LOWER(l.status) = 'closed'")->count();
                $p = $pct($done, $total);

                return ['title' => "$label DJA", 'value' => "$done / $total", 'sub' => $p !== null ? "$p% closed" : 'belum ada deploy', 'tone' => $tone($p), 'route' => $route];
            });
        }
        $add('production', 'cml', function () use ($from, $to, $station) {
            $q = $station(DB::table('cml_logs')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station');
            $open = (clone $q)->whereRaw("LOWER(status) <> 'closed'")->count();

            return ['title' => 'CML', 'value' => number_format((clone $q)->count()), 'sub' => $open ? "$open masih open" : 'semua closed', 'tone' => $open ? 'yellow' : 'green', 'route' => 'modules.cml'];
        });
        $add('production', 'unplanned', function () use ($from, $to, $station) {
            $n = $total = 0;
            foreach ([['wo_logs', 'date'], ['dmi_logs', 'date'], ['nsrdi_logs', 'refresh_date']] as [$t, $c]) {
                $q = $station(DB::table($t)->whereNull('dja_id')->whereDate($c, '>=', $from)->whereDate($c, '<=', $to), 'COALESCE(act_station, plan_station)');
                $total += (clone $q)->count();
                $n += (clone $q)->whereRaw("LOWER(status) = 'closed'")->count();
            }

            return ['title' => 'Unplanned', 'value' => "$n / $total", 'sub' => 'WO + DMI + NSRDI di luar DJA', 'tone' => 'blue', 'route' => 'modules.dja'];
        });
        $add('production', 'dja', function () use ($from, $to, $station) {
            $q = $station(DB::table('daily_job_assignments')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station');
            $review = DB::table('dja_sync_reviews')->whereNull('decision')->where('bucket', 'review')->count();

            return ['title' => 'DJA review', 'value' => number_format($review), 'sub' => number_format((clone $q)->count()).' tugas pada periode ini', 'tone' => $review ? 'yellow' : 'green', 'route' => 'modules.dja'];
        });
        $add('production', 'leader', function () {
            $pending = LeaderReportImport::where('status', 'preview')->count();
            $last = LeaderReportImport::where('status', 'applied')->latest('applied_at')->first();

            return ['title' => 'Laporan Leader', 'value' => (string) $pending, 'sub' => $pending ? 'pratinjau menunggu Terapkan' : ($last ? 'terakhir '.$last->applied_at->diffForHumans() : 'belum ada impor'), 'tone' => $pending ? 'yellow' : 'green', 'route' => 'modules.leader-report'];
        }, 'leader.import');

        // ── Cleaning & LGT ──
        $add('cleaning', 'cleaning', function () use ($from, $to, $station) {
            $q = $station(DB::table('aircraft_cleanings')->whereDate('date', '>=', $from)->whereDate('date', '<=', $to), 'station');
            $byType = (clone $q)->select('type', DB::raw('COUNT(*) n'))->groupBy('type')->orderByDesc('n')->pluck('n', 'type');
            $hours = (float) (clone $q)->sum('man_hour');

            return [
                'title' => 'Aircraft Cleaning', 'value' => number_format($byType->sum()),
                'sub' => $byType->take(4)->map(fn ($n, $t) => "$t $n")->implode(' · ').($hours ? ' · '.number_format($hours, 0).' jam' : ''),
                'tone' => 'blue', 'route' => 'modules.cleaning.hub',
            ];
        });
        $add('cleaning', 'lgt', function () use ($from, $to, $station, $pct, $tone) {
            $rows = $station(LgtRecord::query()->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to), 'station')->get(['cbm_status', 'aiec_status']);
            $cbm = $pct($rows->where('cbm_status', 'CLOSED')->count(), $rows->whereNotNull('cbm_status')->count());
            $aiec = $pct($rows->where('aiec_status', 'CLOSED')->count(), $rows->whereNotNull('aiec_status')->count());
            $worst = collect([$cbm, $aiec])->filter(fn ($v) => $v !== null)->min();

            return ['title' => 'Long Ground Time', 'value' => $cbm !== null ? $cbm.'%' : '–', 'sub' => 'CBM · AIEC '.($aiec !== null ? $aiec.'%' : '–').' · '.$rows->count().' pekerjaan', 'tone' => $tone($worst), 'route' => 'modules.lgt'];
        }, 'lgt.view');

        // ── Capacity & movement ──
        $add('flight', 'capacity', function () use ($period, $tone) {
            $sum = app(ManHourService::class)->summary($period);

            return ['title' => 'Capacity (man hours)', 'value' => $sum['utilisation'] !== null ? $sum['utilisation'].'%' : '–', 'sub' => number_format($sum['total_used'], 0).' dari '.number_format($sum['capacity']['hours'], 0).' jam', 'tone' => $tone($sum['utilisation']), 'route' => 'modules.capacity'];
        });
        $add('flight', 'ron', function () {
            $today = now()->toDateString();
            $ron = DB::table('ac_rons')->whereDate('ron_date', $today)->count();
            $latest = DB::table('ac_rons')->max('ron_date');

            return ['title' => 'A/C RON', 'value' => (string) ($ron ?: DB::table('ac_rons')->whereDate('ron_date', $latest)->count()), 'sub' => $ron ? 'malam ini' : ($latest ? 'data '.substr((string) $latest, 0, 10) : 'belum ada data'), 'tone' => 'blue', 'route' => 'modules.ac-movement'];
        });
        $add('flight', 'standby', fn () => ['title' => 'A/C Standby', 'value' => (string) DB::table('ac_standbies')->count(), 'sub' => 'pesawat standby', 'tone' => 'blue', 'route' => 'modules.ac-movement']);
        $add('flight', 'rotation', function () {
            $latest = DB::table('rotations')->latest('id')->first();

            return ['title' => 'Aircraft Rotation', 'value' => (string) DB::table('rotations')->count(), 'sub' => $latest ? 'terbaru: '.mb_substr((string) $latest->title, 0, 30) : 'belum ada rotasi', 'tone' => 'none', 'route' => 'modules.aircraft-rotation'];
        });
        $add('flight', 'ict', function () {
            $open = DB::table('ict_findings')->whereRaw("LOWER(status) <> 'closed'")->count();

            return ['title' => 'ICT Findings', 'value' => (string) $open, 'sub' => 'masih open dari '.DB::table('ict_findings')->count(), 'tone' => $open ? 'yellow' : 'green', 'route' => 'modules.ict-pi'];
        });
        $add('flight', 'overdue', function () {
            $open = DB::table('nsrdi_overdues')->whereNull('closed_at')->count();

            return ['title' => 'NSRDI overdue', 'value' => (string) $open, 'sub' => 'belum closed', 'tone' => $open ? 'red' : 'green', 'route' => 'modules.nsrdi-overdue'];
        });

        // ── People & compliance ──
        $add('people', 'compliance', function () use ($period, $stations, $tone) {
            $r = app(ComplianceReport::class)->build($period->from, $period->to, $stations)['total'];

            return ['title' => 'Briefing · Attlist · 5R', 'value' => $r['percent'] !== null ? $r['percent'].'%' : '–', 'sub' => "{$r['complete']} dari {$r['expected']} laporan lengkap", 'tone' => $tone($r['percent']), 'route' => 'compliance.daily'];
        }, 'compliance.view');
        $add('people', 'accuracy', function () use ($from, $to, $station, $tone) {
            $q = $station(DB::table('document_accuracy')->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to), 'station');
            $rep = (int) (clone $q)->selectRaw('COALESCE(SUM(cml_reported + nsrdi_reported),0) n')->value('n');
            $miss = (int) (clone $q)->selectRaw('COALESCE(SUM(cml_missed + nsrdi_missed),0) n')->value('n');
            $p = $rep ? round(($rep - $miss) / $rep * 100, 1) : null;

            return ['title' => 'Document Accuracy', 'value' => $p !== null ? $p.'%' : '–', 'sub' => $miss ? "$miss dokumen terlewat" : 'tidak ada yang terlewat', 'tone' => $tone($p), 'route' => 'reports.document-accuracy'];
        }, 'kpi.view');
        $add('people', 'attendance', function () use ($period, $stations) {
            $r = app(AttendanceScorer::class)->build($period->from->copy()->startOfMonth(), $period->from->copy()->endOfMonth(), $stations);
            $s = $r['summary'];
            $attention = ($s['flags']['sering_terlambat'] ?? 0) + ($s['flags']['banyak_sakit'] ?? 0) + ($s['flags']['banyak_cuti'] ?? 0) + ($s['flags']['alpa'] ?? 0);

            return ['title' => 'Presensi & Disiplin', 'value' => (string) $attention, 'sub' => 'perlu perhatian · '.($s['flags']['rajin'] ?? 0).' rajin · '.$s['people'].' karyawan', 'tone' => $attention ? 'yellow' : 'green', 'route' => 'attendance.index'];
        }, 'attendance.view');
        $add('people', 'employees', function () use ($stations) {
            $e = Employee::query()->when($stations, fn ($q) => $q->whereIn('station', $stations))->get();
            $contract = $e->filter(fn ($x) => in_array($x->contractStatus(), [ExpiryStatus::YELLOW, ExpiryStatus::RED, ExpiryStatus::EXPIRED], true))->count();
            $passport = $e->filter(fn ($x) => in_array($x->passportStatus(), [ExpiryStatus::YELLOW, ExpiryStatus::RED, ExpiryStatus::EXPIRED], true))->count();
            $pas = RegistryRecord::where('module', 'pas')->whereNotNull('due_date')->get()->filter(fn ($r) => in_array($r->expiryStatus(), [ExpiryStatus::YELLOW, ExpiryStatus::RED, ExpiryStatus::EXPIRED], true))->count();
            $total = $contract + $passport + $pas;

            return ['title' => 'Karyawan', 'value' => number_format($e->count()), 'sub' => $total ? "$contract kontrak · $passport paspor · $pas PAS segera habis" : 'tidak ada dokumen mendekati habis', 'tone' => $total ? 'yellow' : 'green', 'route' => 'hr.employees'];
        }, 'hr.view');

        // ── Asset & inventory ──
        $add('assets', 'assets', function () {
            $assets = Asset::all();
            $out = AssetAssignment::whereNull('returned_at')->get();
            $late = $out->filter(fn ($a) => $a->isOverdue())->count();
            $available = $assets->sum(fn ($a) => $a->unitsAvailable((int) $out->where('asset_id', $a->id)->sum('qty')));

            return ['title' => 'Data Asset', 'value' => number_format($available), 'sub' => 'unit tersedia dari '.number_format($assets->sum('qty_total')).($late ? " · $late lewat jatuh tempo" : ''), 'tone' => $late ? 'red' : 'green', 'route' => 'assets.index'];
        }, 'asset.view');
        $add('assets', 'ims', function () {
            $pending = DB::table('ims_approvals')->where('status', 'pending')->count();
            $low = DB::table('ims_items as i')->leftJoin('ims_stocks as s', 's.item_id', '=', 'i.id')->where('i.is_active', 1)->where('i.min_stock', '>', 0)
                ->groupBy('i.id', 'i.min_stock')->havingRaw('COALESCE(SUM(s.qty_on_hand),0) < i.min_stock')->select('i.id')->get()->count();

            return ['title' => 'Inventory (IMS)', 'value' => (string) $pending, 'sub' => "menunggu approval · $low barang di bawah stok minimum", 'tone' => ($pending || $low) ? 'yellow' : 'green', 'route' => 'ims.catalog'];
        });
        $add('assets', 'documents', fn () => ['title' => 'Pusat Dokumen', 'value' => number_format(Document::count()), 'sub' => 'CMPM, SOP, template, regulasi', 'tone' => 'none', 'route' => 'documents.index']);
        $add('assets', 'aircraft', fn () => ['title' => 'Master pesawat', 'value' => number_format(Aircraft::count()), 'sub' => number_format(Aircraft::whereNotNull('wg')->count()).' sudah punya WG', 'tone' => 'none', 'route' => 'master.aircraft']);

        // ── Data & system ──
        $add('system', 'sources', function () {
            $keys = collect(config('sources.sources'))->filter(fn ($s) => isset($s['handler']))->keys();
            $rows = SyncSetting::whereIn('key', $keys)->get();
            $failed = $rows->where('last_status', 'failed')->count();
            $never = $keys->count() - $rows->whereNotNull('last_synced_at')->count();
            $oldest = $rows->whereNotNull('last_synced_at')->min('last_synced_at');

            return ['title' => 'Sumber Data', 'value' => $failed ? "$failed gagal" : 'Sehat', 'sub' => $never ? "$never sumber belum pernah disinkron" : ($oldest ? 'terlama '.Carbon::parse($oldest)->diffForHumans() : ''), 'tone' => $failed ? 'red' : ($never ? 'yellow' : 'green'), 'route' => 'sources.index'];
        }, 'sources.view');

        return collect(self::GROUPS)->mapWithKeys(fn ($label, $g) => [$g => $tiles[$g] ?? []])->filter()->all();
    }
}
