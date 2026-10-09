<?php

namespace App\Services\Audit;

use App\Models\Aircraft;
use App\Models\Aoc;

/**
 * Finds the AOC of a record. The aircraft master is authoritative (registration -> AOC), because registration
 * prefixes are shared between airlines (PK-L.. is both Lion Air and Batik Air). Free text such as "SUPER AIR JET",
 * "IU" or "THAI LION AIR" found in the sheets is only a fallback, matched against the AOC aliases.
 */
class AocResolver
{
    /** @var array<string, int>|null registration => aoc_id */
    private ?array $byRegistration = null;

    /** @var array<string, int>|null normalised alias => aoc_id */
    private ?array $byAlias = null;

    /** @var array<int, Aoc>|null */
    private ?array $aocs = null;

    public function resolve(?string $registration, ?string ...$texts): ?Aoc
    {
        $this->load();

        $id = $this->byRegistration[$this->key($registration)] ?? null;

        foreach ($texts as $text) {
            if ($id) {
                break;
            }
            $id = $this->byAlias[$this->key($text)] ?? null;
        }

        return $id ? ($this->aocs[$id] ?? null) : null;
    }

    public function forAircraft(?string $registration): ?Aoc
    {
        return $this->resolve($registration);
    }

    public function forText(?string $text): ?Aoc
    {
        return $this->resolve(null, $text);
    }

    /** @return array<int, Aoc> keyed by id */
    public function all(): array
    {
        $this->load();

        return $this->aocs;
    }

    public function flush(): void
    {
        $this->byRegistration = $this->byAlias = $this->aocs = null;
    }

    private function load(): void
    {
        if ($this->aocs !== null) {
            return;
        }

        $this->aocs = Aoc::orderBy('sort_order')->get()->keyBy('id')->all();

        $this->byAlias = [];
        foreach ($this->aocs as $aoc) {
            foreach (array_merge([$aoc->code, $aoc->name], $aoc->aliases ?? []) as $alias) {
                $this->byAlias[$this->key($alias)] = $aoc->id;
            }
        }

        $this->byRegistration = Aircraft::whereNotNull('aoc_id')->pluck('aoc_id', 'registration')
            ->mapWithKeys(fn ($id, $reg) => [$this->key($reg) => $id])->all();
    }

    private function key(?string $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}
