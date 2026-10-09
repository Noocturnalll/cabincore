<?php

namespace App\Services\Master;

use App\Models\Division;
use App\Models\MasterEntry;
use App\Services\Kpi\StationKpiService;
use Illuminate\Support\Facades\Cache;

/**
 * Reads the configuration that lives in Data Master. When a list has not been filled in yet (fresh install, tests)
 * every method falls back to config/kpi.php and config/master.php defaults, so nothing breaks before the seeder ran.
 */
class MasterSettings
{
    private const CACHE_KEY = 'master.entries.v1';

    /** Call after any change to master_entries so the next read sees it. */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, array<string, array{label: string, attrs: array, active: bool}>> type => code => entry */
    private function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $out = [];
            foreach (MasterEntry::orderBy('sort_order')->orderBy('code')->get() as $e) {
                $out[$e->type][$e->code] = ['label' => $e->label, 'attrs' => $e->attrs ?? [], 'active' => $e->is_active];
            }

            return $out;
        });
    }

    /** @return array<string, array{label: string, attrs: array, active: bool}> */
    public function entries(string $type, bool $onlyActive = true): array
    {
        $rows = $this->all()[$type] ?? [];

        return $onlyActive ? array_filter($rows, fn ($r) => $r['active']) : $rows;
    }

    /** @return array<string, float> shift code => effective hours */
    public function shiftHours(): array
    {
        $rows = $this->entries('shift');
        if (! $rows) {
            return config('kpi.effective_hours_by_shift');
        }

        return array_map(fn ($r) => (float) ($r['attrs']['effective_hours'] ?? config('kpi.effective_hours')), $rows);
    }

    /** @return array<int, string> teams whose people count as technicians in the capacity */
    public function capacityTeams(): array
    {
        $rows = $this->entries('team');
        if (! $rows) {
            return config('kpi.capacity_teams');
        }

        return array_keys(array_filter($rows, fn ($r) => ($r['attrs']['capacity'] ?? 'Ya') === 'Ya'));
    }

    /** @return array<string, string> team code => label */
    public function teamLabels(): array
    {
        $rows = $this->entries('team');

        return $rows ? array_map(fn ($r) => $r['label'], $rows) : ['CBM' => 'CBM', 'AIEC' => 'AIEC', 'PAINTING' => 'Painting', 'IRREG' => 'Irreg', 'PI' => 'PI', 'COD' => 'COD'];
    }

    /** The roster team a division belongs to, e.g. "Cabin" => CBM; null when the division is not a roster team. */
    public function teamForDivision(?int $divisionId): ?string
    {
        $name = $divisionId ? Division::whereKey($divisionId)->value('name') : null;
        if (! $name) {
            return null;
        }
        foreach ($this->entries('team') as $code => $row) {
            if (strcasecmp((string) ($row['attrs']['division'] ?? ''), $name) === 0) {
                return $code;
            }
        }

        return StationKpiService::DIVISION_TEAM[$name] ?? null;
    }

    /** A Ya / Tidak switch from Data Master > Pengaturan Integrasi. */
    public function flag(string $code, bool $default = false): bool
    {
        $row = $this->entries('integration')[$code] ?? null;

        return $row ? (($row['attrs']['value'] ?? 'Tidak') === 'Ya') : $default;
    }

    public function rule(string $code, float|int $default): float
    {
        $row = $this->entries('attendance_rule')[$code] ?? null;

        return (float) ($row['attrs']['value'] ?? $default);
    }

    /** @return array<string, string> status code => group (hadir, sakit, cuti ...) */
    public function attendanceGroups(): array
    {
        $rows = $this->entries('attendance_status');
        if (! $rows) {
            foreach (config('master.types.attendance_status.defaults') as $code => [, $attrs]) {
                $rows[$code] = ['attrs' => $attrs];
            }
        }

        return array_map(fn ($r) => $r['attrs']['group'] ?? 'hadir', $rows);
    }

    /** @return array<int, string> condition codes that may be lent out */
    public function lendableConditions(): array
    {
        $rows = $this->entries('asset_condition');
        if (! $rows) {
            return ['BAIK'];
        }

        return array_keys(array_filter($rows, fn ($r) => ($r['attrs']['available'] ?? 'Ya') === 'Ya'));
    }
}
