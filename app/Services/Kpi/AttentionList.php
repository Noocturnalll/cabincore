<?php

namespace App\Services\Kpi;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * "Perlu perhatian": the things a user has to chase today, counted for them so they do not have to open every menu.
 * Each item belongs to a permission; a user only gets the items of the work they can act on, and where a module has
 * stations the counts follow the station scope. An item that fails to compute is left out.
 *
 * @phpstan-type Item array{label: string, count: int, route: string, tone: string}
 */
class AttentionList
{
    /** @param array<int, string>|null $stations */
    public function build(User $user, ?array $stations = null): array
    {
        $today = now()->toDateString();
        $items = [];
        $station = fn ($q, string $expr) => $stations === null ? $q : $q->whereIn(DB::raw($expr), $stations);

        $add = function (string $permission, string $label, string $route, string $tone, callable $count) use (&$items, $user) {
            if (! collect(explode('|', $permission))->contains(fn ($p) => $user->can($p))) {
                return;
            }
            try {
                $n = (int) $count();
            } catch (\Throwable $e) {
                report($e);

                return;
            }
            if ($n > 0) {
                $items[] = ['label' => $label, 'count' => $n, 'route' => $route, 'tone' => $tone];
            }
        };

        // ── Production ──
        $add('menu.production|menu.painting', 'Tugas DJA hari sebelumnya belum closed', 'modules.dja', 'red', function () use ($today, $station, $user) {
            $tables = [];
            if ($user->can('menu.production')) {
                $tables = ['wo_logs', 'dmi_logs', 'nsrdi_logs'];
            } elseif ($user->can('menu.painting')) {
                $tables = ['nsrdi_logs'];
            }

            return collect($tables)->sum(function ($t) use ($today, $station) {
                $q = DB::table("$t as l")->join('daily_job_assignments as d', 'd.id', '=', 'l.dja_id')
                    ->whereDate('d.date', '<', $today)->whereRaw("LOWER(COALESCE(l.status, '')) <> 'closed'");

                return $station($q, 'COALESCE(l.act_station, l.plan_station)')->count();
            });
        });
        $add('menu.production', 'Hasil sinkron DJA menunggu keputusan', 'modules.dja', 'yellow', fn () => DB::table('dja_sync_reviews')->whereNull('decision')->where('bucket', 'review')->count());
        $add('menu.production', 'CML open lebih dari 2 hari', 'modules.cml', 'yellow', fn () => $station(DB::table('cml_logs')->whereRaw("LOWER(status) <> 'closed'")->whereDate('date', '<', now()->subDays(2)->toDateString()), 'station')->count());
        $add('menu.ict', 'Temuan ICT open lebih dari 7 hari', 'modules.ict-pi', 'yellow', fn () => DB::table('ict_findings')->whereRaw("LOWER(status) <> 'closed'")->whereDate('date', '<', now()->subDays(7)->toDateString())->count());
        $add('menu.nsrdi', 'NSRDI overdue belum closed', 'modules.nsrdi-overdue', 'red', fn () => DB::table('nsrdi_overdues')->whereNull('closed_at')->count());

        // ── LGT ──
        $add('lgt.manage', 'Draft LGT menunggu diisi pekerjaannya', 'modules.lgt', 'yellow', fn () => $station(DB::table('lgt_records')->whereDate('work_date', '>=', $today)->whereNull('cbm_action')->whereNull('aiec_action'), 'station')->count());
        $add('lgt.view', 'Pekerjaan LGT hari lalu masih open', 'modules.lgt', 'red', fn () => $station(DB::table('lgt_records')->whereDate('work_date', '<', $today)->where(fn ($w) => $w->where('cbm_status', 'OPEN')->orWhere('aiec_status', 'OPEN')), 'station')->count());

        // ── Inventory: requests, repair desk, loans, stock ──
        $add('ims.approval.act', 'Permintaan barang menunggu persetujuan Anda', 'ims.approvals', 'yellow', fn () => DB::table('ims_transactions')->where('status', 'pending_approval')->where('type', 'out')->count());
        $add('ims.stock.handover', 'Barang disetujui, belum diserahkan', 'ims.approvals', 'yellow', fn () => DB::table('ims_transactions')->where('type', 'out')->where('status', 'approved')->whereNull('picked_up_at')->count());
        $add('ims.repair.manage', 'Barang rusak menunggu ACC tim repair', 'ims.repairs', 'yellow', fn () => DB::table('ims_repair_waiting')->whereNull('deleted_at')->whereNull('accepted_at')->count());
        $add('ims.repair.back_stage', 'Barang selesai repair (Rak 3), belum kembali ke gudang', 'ims.repairs', 'yellow', fn () => DB::table('ims_repair_completed')->whereNull('deleted_at')->where('result', 'serviceable')->whereNull('returned_to_stock_at')->count());
        $add('ims.approval.view', 'Pinjaman barang lewat jatuh tempo', 'ims.approvals', 'red', fn () => DB::table('ims_transactions as t')->where('t.type', 'out')->where('t.usage_type', 'loan')->where('t.status', 'approved')
            ->whereDate('t.expected_return_date', '<', $today)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('ims_transactions as r')->whereColumn('r.parent_transaction_id', 't.id')->where('r.usage_type', 'loan_return'))->count());
        $add('ims.approval.view', 'Barang di bawah stok minimum', 'ims.catalog', 'yellow', fn () => DB::table('ims_items as i')->leftJoin('ims_stocks as s', 's.item_id', '=', 'i.id')->where('i.is_active', 1)->where('i.min_stock', '>', 0)
            ->groupBy('i.id', 'i.min_stock')->havingRaw('COALESCE(SUM(s.qty_on_hand),0) < i.min_stock')->select('i.id')->get()->count());

        // ── System ──
        $add('sources.view', 'Sumber data gagal sinkron', 'sources.index', 'red', fn () => DB::table('sync_settings')->where('last_status', 'failed')->count());

        usort($items, fn ($a, $b) => [$a['tone'] === 'red' ? 0 : 1, -$a['count']] <=> [$b['tone'] === 'red' ? 0 : 1, -$b['count']]);

        return $items;
    }
}
