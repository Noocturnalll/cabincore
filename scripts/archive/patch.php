<?php

$content = file_get_contents('c:/Users/achai/cbm/app/Livewire/Dashboard.php');

$content = str_replace('use Livewire\\Component;', 'use App\\Support\\DashboardScope;'.PHP_EOL.'use Livewire\\Component;'.PHP_EOL.'use Livewire\\Attributes\\On;', $content);

$class_def = 'class Dashboard extends Component'.PHP_EOL.'{';
$new_methods = <<<'PHP'
class Dashboard extends Component
{
    public string $period = 'daily';

    private function scope(): DashboardScope
    {
        return DashboardScope::for(auth()->user());
    }

    private function range(): array
    {
        $scope = $this->scope();
        $period = in_array($this->period, $scope->periods, true) ? $this->period : 'daily';
        
        $active = now()->hour >= 18 ? now() : now()->subDay();

        [$from, $to] = match ($period) {
            'weekly'  => [$active->copy()->startOfWeek(), $active->copy()],
            'monthly' => [$active->copy()->startOfMonth(), $active->copy()],
            default   => [$active->copy(), $active->copy()],
        };

        return [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()];
    }

    public function updatedPeriod()
    {
        $this->dispatch('dashboard-updated');
    }

    private function applyStationFilter($query, string $table, ?string $djaJoinTable = null)
    {
        $scope = $this->scope();
        if ($scope->stations === null) {
            return $query;
        }

        if ($djaJoinTable) {
            $query->whereIn("{$djaJoinTable}.station", $scope->stations);
        } else {
            $column = match($table) {
                'wo_logs', 'dmi_logs', 'nsrdi_logs' => 'act_station',
                'cml_logs', 'aircraft_cleanings' => 'station',
                default => null,
            };
            if ($column) {
                $query->whereIn("{$table}.{$column}", $scope->stations);
            }
        }
        
        return $query;
    }
PHP;

$content = str_replace($class_def, $new_methods, $content);

$old_start = <<<'PHP'
    private function getDashboardStats(): array
    {
        // "Active date": before 18:00 WIB → show yesterday; at/after 18:00 → show today
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d');
PHP;

$new_start = <<<'PHP'
    private function getDashboardStats(): array
    {
        $scope = $this->scope();
        [$from, $to] = $this->range();
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d');
PHP;

$content = str_replace($old_start, $new_start, $content);

$content = str_replace("whereDate('date', \$targetDate)", "whereBetween('date', [\$from, \$to])", $content);
$content = str_replace("whereDate('plan_date', \$targetDate)", "whereBetween('plan_date', [\$from, \$to])", $content);
$content = str_replace("whereDate('report_date', \$targetDate)", "whereBetween('report_date', [\$from, \$to])", $content);
$content = str_replace("whereDate('created_at', \$targetDate)", "whereBetween('created_at', [\$from, \$to])", $content);
$content = str_replace('whereDate("{$table}.{$dateCol}", $targetDate)', 'whereBetween("{$table}.{$dateCol}", [$from, $to])', $content);

$old_trend = <<<'PHP'
        $targetDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $activeCarbon->copy()->subDays($i);
            $trendLabels[] = $d->translatedFormat('d M');
            $targetDates[] = $d->format('Y-m-d');
        }
PHP;

$new_trend = <<<'PHP'
        $targetDates = [];
        $periodMode = in_array($this->period, $scope->periods, true) ? $this->period : 'daily';
        $loopCount = match($periodMode) { 'weekly' => 8, 'monthly' => 6, default => 7 };
        
        $trendCmlClosed = array_fill(0, $loopCount, 0);
        $trendAcTotal = array_fill(0, $loopCount, 0);
        $trendDja = array_fill(0, $loopCount, 0);
        $trendUnplanned = array_fill(0, $loopCount, 0);

        for ($i = $loopCount - 1; $i >= 0; $i--) {
            if ($periodMode === 'weekly') {
                $d = $activeCarbon->copy()->subWeeks($i);
                $trendLabels[] = 'W' . $d->weekOfYear;
                $targetDates[] = [$d->copy()->startOfWeek()->format('Y-m-d'), $d->copy()->endOfWeek()->format('Y-m-d')];
            } elseif ($periodMode === 'monthly') {
                $d = $activeCarbon->copy()->subMonths($i);
                $trendLabels[] = $d->translatedFormat('M Y');
                $targetDates[] = [$d->copy()->startOfMonth()->format('Y-m-d'), $d->copy()->endOfMonth()->format('Y-m-d')];
            } else {
                $d = $activeCarbon->copy()->subDays($i);
                $trendLabels[] = $d->translatedFormat('d M');
                $targetDates[] = [$d->format('Y-m-d'), $d->format('Y-m-d')];
            }
        }
        
        $getDayIndex = function (string $dateStr) use ($targetDates, $periodMode): int {
            $dateStr = substr($dateStr, 0, 10);
            foreach ($targetDates as $idx => $range) {
                if ($dateStr >= $range[0] && $dateStr <= $range[1]) return $idx;
            }
            return -1;
        };

        $startDateStr = $targetDates[0][0] . ' 00:00:00';
        $endDateStr = $targetDates[$loopCount - 1][1] . ' 23:59:59';
PHP;

$content = str_replace($old_trend, $new_trend, $content);

$content = preg_replace('/\\$trendCmlClosed = array_fill\\(0, 7, 0\\);\\s*\\$trendAcTotal = array_fill\\(0, 7, 0\\);\\s*\\$trendDja = array_fill\\(0, 7, 0\\);\\s*\\$trendUnplanned = array_fill\\(0, 7, 0\\);/', '', $content);
$content = preg_replace('/\\$getDayIndex = function \\(string \\$dateStr\\) use \\(\\$targetDates\\): int \\{\\s*\\$idx = array_search\\(\\$dateStr, \\$targetDates\\);\\s*return \\$idx !== false \\? \\$idx : -1;\\s*\\};\\s*\\$startDateStr = \\$targetDates\\[0\\];\\s*\\$endDateStr = \\$targetDates\\[6\\];/', '', $content);

// Station filters application
$content = str_replace("DB::table('wo_logs')", "\$this->applyStationFilter(DB::table('wo_logs'), 'wo_logs')", $content);
$content = str_replace("DB::table('dmi_logs')", "\$this->applyStationFilter(DB::table('dmi_logs'), 'dmi_logs')", $content);
$content = str_replace("DB::table('nsrdi_logs')", "\$this->applyStationFilter(DB::table('nsrdi_logs'), 'nsrdi_logs')", $content);
$content = str_replace("DB::table('cml_logs')", "\$this->applyStationFilter(DB::table('cml_logs'), 'cml_logs')", $content);
$content = str_replace("DB::table('aircraft_cleanings')", "\$this->applyStationFilter(DB::table('aircraft_cleanings'), 'aircraft_cleanings')", $content);

// Apply station filter for DJA joining queries (station stats)
$content = str_replace('->whereBetween("{$table}.{$dateCol}", [$from, $to])', "->whereBetween(\"{\$table}.{\$dateCol}\", [\$from, \$to])\n                ->when(\$scope->stations, fn(\$q) => \$q->whereIn('daily_job_assignments.station', \$scope->stations))", $content);

file_put_contents('c:/Users/achai/cbm/app/Livewire/Dashboard.php', $content);
echo "Patch applied successfully\n";
