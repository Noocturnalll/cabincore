<?php

namespace App\Services\Dja;

use App\Models\DjaSyncReview;

/**
 * One pipeline for a planner row: map -> (human decision | rules) -> store, or park in the review list.
 * Shared by the Google Sheets sync and the Excel import so both behave identically.
 */
class DjaIngestor
{
    /** @var array<string, string> row_key => accepted|rejected */
    private array $decisions = [];

    /** @var array<string, int> */
    private array $stats = [];

    private bool $ready = false;

    public function __construct(
        private DjaRowMapper $mapper,
        private DjaClassifier $classifier,
        private DjaPersister $persister,
    ) {}

    /** Reset counters and reload the human decisions. Call once per sync run. */
    public function begin(): void
    {
        $this->decisions = DjaSyncReview::whereNotNull('decision')->pluck('decision', 'row_key')->all();
        $this->stats = [
            'created' => 0, 'updated' => 0, 'unchanged' => 0,
            'review' => 0, 'rejected' => 0, 'manual_accepted' => 0, 'manual_rejected' => 0,
        ];
        $this->ready = true;
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return $this->stats;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $headerMap
     * @return string|null the task id when the row is part of the DJA, null when it was skipped / parked
     */
    public function ingest(string $tab, array $row, array $headerMap, ?string $spreadsheetId): ?string
    {
        if (! $this->ready) {
            $this->begin();
        }

        $mapped = $this->mapper->map($tab, $row, $headerMap);
        if ($mapped === null) {
            return null;
        }

        $key = DjaSyncReview::keyFor($tab, $mapped['task_id'], $mapped['ac_reg'], $mapped['description']);
        $decision = $this->decisions[$key] ?? null;

        if ($decision === 'rejected') {
            $this->stats['manual_rejected']++;
            DjaSyncReview::where('row_key', $key)->update(['last_seen_at' => now()]);

            return null;
        }

        if ($decision === 'accepted') {
            $verdict = ['action' => DjaClassifier::ACCEPT, 'rule' => 'manual.accepted', 'reason' => 'Diterima manual.'];
            $this->stats['manual_accepted']++;
        } else {
            $verdict = $this->remembered($mapped) ?? $this->classifier->classify($mapped['kind'], $mapped['ata'], $mapped['category'], $mapped['classify_text'] ?? $mapped['description']);
        }

        if ($verdict['action'] === DjaClassifier::ACCEPT) {
            $stored = $this->persister->store($mapped, $spreadsheetId);
            $this->stats[$stored['result']]++;

            // Rules accept it now (e.g. the planner filled in the ATA): drop the stale review entry
            if ($decision === null) {
                DjaSyncReview::where('row_key', $key)->whereNull('decision')->delete();
            }

            return $stored['task_id'];
        }

        $this->park($key, $mapped, $verdict, $spreadsheetId);
        $this->stats[$verdict['action'] === DjaClassifier::REVIEW ? 'review' : 'rejected']++;

        return null;
    }

    /** What people decided about the same job before (WO / DMI only): CBM accepts, LINE rejects, conflicting answers go to review. */
    private function remembered(array $mapped): ?array
    {
        if (! in_array($mapped['kind'], ['wo', 'dmi'], true)) {
            return null;
        }
        $memory = app(ClassificationMemory::class)->lookup($mapped['kind'], $mapped['task_card'] ?? null, $mapped['classify_text'] ?? $mapped['description']);
        if (! $memory) {
            return null;
        }

        return match ($memory['category']) {
            ClassificationMemory::CBM => ['action' => DjaClassifier::ACCEPT, 'rule' => 'memory.cbm', 'reason' => "Pekerjaan serupa sebelumnya diputuskan CBM ({$memory['hits']}x)."],
            ClassificationMemory::LINE => ['action' => DjaClassifier::REJECT, 'rule' => 'memory.line', 'reason' => "Pekerjaan serupa sebelumnya diputuskan line maintenance ({$memory['hits']}x)."],
            default => ['action' => DjaClassifier::REVIEW, 'rule' => 'memory.conflict', 'reason' => 'Pekerjaan serupa pernah diputuskan CBM dan juga line: perlu dilihat lagi.'],
        };
    }

    private function park(string $key, array $mapped, array $verdict, ?string $spreadsheetId): void
    {
        $review = DjaSyncReview::firstOrNew(['row_key' => $key]);
        if (! $review->exists) {
            $review->first_seen_at = now();
        }

        $review->fill([
            'spreadsheet_id' => $spreadsheetId,
            'tab' => $mapped['tab'],
            'kind' => $mapped['kind'],
            'task_id' => $mapped['task_id'],
            'aircraft_registration' => $mapped['ac_reg'],
            'description' => $mapped['description'],
            'ata' => $mapped['ata'],
            'category' => $mapped['category'],
            'bucket' => $verdict['action'],
            'rule' => $verdict['rule'],
            'reason' => $verdict['reason'],
            'payload' => $mapped,
            'last_seen_at' => now(),
        ])->save();
    }

    /** Removes undecided entries that no sync has seen for a while. Returns how many were removed. */
    public function pruneStale(): int
    {
        return DjaSyncReview::whereNull('decision')
            ->where('last_seen_at', '<', now()->subDays(config('dja.review_retention_days', 3)))
            ->delete();
    }
}
