<?php

namespace App\Services\Crew;

use App\Models\Employee;
use App\Models\JobCrew;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the job_crew pivot in step with the people named on a job. Each person gets an equal share of the job's
 * man hours, so summing job_crew per person gives the hours that person actually worked.
 */
class CrewWriter
{
    /** @var array<string, int>|null employee number => employee id */
    private ?array $employees = null;

    /** Splits "250409, 83117667" / an array into clean, unique references. */
    public static function refs(array|string|null $crew): array
    {
        $list = is_array($crew) ? $crew : preg_split('/[,;\n]+/', (string) $crew);

        return array_values(array_unique(array_filter(array_map(fn ($r) => strtoupper(trim((string) $r)), $list), fn ($r) => $r !== '')));
    }

    /** Replaces the crew of one job. */
    public function replace(string $type, int $id, array|string|null $crew, ?float $manHour, string $date, ?string $station = null): void
    {
        JobCrew::where('jobable_type', $type)->where('jobable_id', $id)->delete();
        $rows = $this->rows($type, $id, self::refs($crew), $manHour, $date, $station);
        if ($rows) {
            JobCrew::insert($rows);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function rows(string $type, int $id, array $refs, ?float $manHour, string $date, ?string $station): array
    {
        if (! $refs) {
            return [];
        }
        $share = $manHour !== null ? round($manHour / count($refs), 2) : null;
        $now = now();

        return array_map(fn ($ref) => [
            'jobable_type' => $type, 'jobable_id' => $id, 'employee_ref' => $ref,
            'employee_id' => $this->employeeId($ref), 'man_hour' => $share,
            'work_date' => $date, 'station' => $station, 'created_at' => $now, 'updated_at' => $now,
        ], $refs);
    }

    /** Removes the crew of jobs that are about to be deleted. */
    public function forget(string $type, array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            JobCrew::where('jobable_type', $type)->whereIn('jobable_id', $chunk)->delete();
        }
    }

    private function employeeId(string $ref): ?int
    {
        $this->employees ??= Employee::pluck('id', 'nik')->mapWithKeys(fn ($id, $nik) => [strtoupper($nik) => $id])->all();

        return $this->employees[$ref] ?? null;
    }

    /** Hours per person over a period, busiest first. @return \Illuminate\Support\Collection<int, object{employee_ref: string, hours: float, jobs: int}> */
    public function hoursPerPerson(string $from, string $to, ?array $stations = null)
    {
        return DB::table('job_crew')
            ->whereDate('work_date', '>=', $from)->whereDate('work_date', '<=', $to)
            ->when($stations, fn ($q) => $q->whereIn('station', $stations))
            ->select('employee_ref', DB::raw('COALESCE(SUM(man_hour),0) as hours'), DB::raw('COUNT(*) as jobs'))
            ->groupBy('employee_ref')->orderByDesc('hours')->get();
    }
}
