import re
with open('c:/Users/achai/cbm/app/Livewire/Dashboard.php', 'r') as f:
    content = f.read()

# Add scope and range
content = content.replace('use Livewire\\Component;', 'use App\\Support\\DashboardScope;\nuse Livewire\\Component;\nuse Livewire\\Attributes\\On;')
class_def = 'class Dashboard extends Component\n{'
new_methods = '''class Dashboard extends Component
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
'''
content = content.replace(class_def, new_methods)

# Replace start of getDashboardStats
old_start = '''    private function getDashboardStats(): array
    {
        // "Active date": before 18:00 WIB → show yesterday; at/after 18:00 → show today
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d');'''
new_start = '''    private function getDashboardStats(): array
    {
        $scope = $this->scope();
        [$from, $to] = $this->range();
        $activeCarbon = now()->hour >= 18 ? now() : now()->subDays(1);
        $targetDate = $activeCarbon->format('Y-m-d'); // kept for labels'''
content = content.replace(old_start, new_start)

# Replace all the whereDates
content = content.replace("whereDate('date', $targetDate)", "whereBetween('date', [$from, $to])")
content = content.replace("whereDate('plan_date', $targetDate)", "whereBetween('plan_date', [$from, $to])")
content = content.replace("whereDate('report_date', $targetDate)", "whereBetween('report_date', [$from, $to])")
content = content.replace("whereDate('created_at', $targetDate)", "whereBetween('created_at', [$from, $to])")
content = content.replace('whereDate("{$table}.{$dateCol}", $targetDate)', 'whereBetween("{$table}.{$dateCol}", [$from, $to])')

# The trend dates
old_trend = '''        $targetDates = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $activeCarbon->copy()->subDays($i);
            $trendLabels[] = $d->translatedFormat('d M');
            $targetDates[] = $d->format('Y-m-d');
        }'''
new_trend = '''        $targetDates = [];
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
        $endDateStr = $targetDates[$loopCount - 1][1] . ' 23:59:59';'''
content = content.replace(old_trend, new_trend)

# Remove the old array_fills because we moved them inside new_trend
content = re.sub(r'\$trendCmlClosed = array_fill\(0, 7, 0\);\s*\$trendAcTotal = array_fill\(0, 7, 0\);\s*\$trendDja = array_fill\(0, 7, 0\);\s*\$trendUnplanned = array_fill\(0, 7, 0\);', '', content)

# Remove old getDayIndex and start/end dates
content = re.sub(r'\$getDayIndex = function \(string \$dateStr\) use \(\$targetDates\): int \{\s*\$idx = array_search\(\$dateStr, \$targetDates\);\s*return \$idx !== false \? \$idx : -1;\s*\};\s*\$startDateStr = \$targetDates\[0\];\s*\$endDateStr = \$targetDates\[6\];', '', content)


with open('c:/Users/achai/cbm/app/Livewire/Dashboard.php', 'w') as f:
    f.write(content)
print('Patch applied successfully')
