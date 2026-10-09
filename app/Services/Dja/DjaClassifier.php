<?php

namespace App\Services\Dja;

/**
 * Decides whether a planner-sheet row belongs in CBM (WO / DMI / NSRDI).
 *
 * Pure and stateless: it only reads config/dja.php, so it is cheap to unit test.
 * Human overrides (dja_sync_reviews) are applied by the caller before this runs.
 */
class DjaClassifier
{
    public const ACCEPT = 'accept';

    public const REVIEW = 'review';

    public const REJECT = 'reject';

    public function __construct(private ?array $config = null)
    {
        $this->config ??= config('dja');
    }

    /**
     * @param  string  $kind  'wo' | 'dmi' | 'nsrdi'
     * @return array{action: string, rule: string, reason: string}
     */
    public function classify(string $kind, ?string $ata, ?string $category, ?string $description): array
    {
        return $kind === 'nsrdi'
            ? $this->classifyNsrdi($category)
            : $this->classifyCabin($kind, $ata, $description);
    }

    private function classifyNsrdi(?string $category): array
    {
        $category = strtoupper(trim((string) $category));
        $allowed = array_map('strtoupper', $this->config['nsrdi_categories']);

        if ($category === '') {
            return $this->result(self::REVIEW, 'nsrdi.no_category', 'Kolom CATEGORY kosong, tidak bisa ditentukan otomatis.');
        }

        return in_array($category, $allowed, true)
            ? $this->result(self::ACCEPT, 'nsrdi.category', "Kategori {$category}.")
            : $this->result(self::REJECT, 'nsrdi.category_excluded', "Kategori {$category} bukan ".implode('/', $allowed).'.');
    }

    private function classifyCabin(string $kind, ?string $ata, ?string $description): array
    {
        $ataPrefix = preg_match('/^\s*(\d{2})/', (string) $ata, $m) ? $m[1] : '';

        if ($ataPrefix !== '' && in_array($ataPrefix, $this->config['ata_reject'], true)) {
            return $this->result(self::REJECT, 'ata.reject', "ATA {$ataPrefix} dikecualikan.");
        }
        if ($ataPrefix !== '' && in_array($ataPrefix, $this->config['ata_accept'], true)) {
            return $this->result(self::ACCEPT, 'ata.accept', "ATA {$ataPrefix} termasuk cabin.");
        }

        $text = $this->normalise((string) $description);

        $include = $this->firstMatch($text, $this->config[$kind]['include'] ?? []);
        $exclude = $this->firstMatch($text, $this->config['exclude'] ?? []);

        if ($include !== null && $exclude === null) {
            return $this->result(self::ACCEPT, 'keyword.include', "Kata kunci \"{$include}\".");
        }
        if ($include !== null) {
            return $this->result(self::REVIEW, 'keyword.conflict', "Kata kunci \"{$include}\" tetapi juga memuat \"{$exclude}\".");
        }

        $weak = $this->firstMatch($text, $this->config['review'] ?? []);
        if ($weak !== null) {
            return $this->result(self::REVIEW, 'keyword.weak', "Hanya kata \"{$weak}\" yang cocok, perlu dicek manual.");
        }

        return $this->result(self::REJECT, 'no_match', 'Tidak ada ATA atau kata kunci cabin yang cocok.');
    }

    /**
     * Category for an NSRDI that has none, plus whether a person should double check it.
     *   PAINTING, sure   paint / PPO / paint peel off, or a placard with an exterior word
     *   PAINTING, check  only a weak word (CAT, LIVERY, FADED, ...)
     *   CBM, check       a placard / decal / logo without an exterior word
     *   CBM, sure        nothing paint related
     * Null category when there is no description to judge.
     *
     * @return array{category: ?string, check: bool}
     */
    public function nsrdiCategory(?string $description): array
    {
        $text = $this->normalise((string) $description);
        if ($text === '') {
            return ['category' => null, 'check' => false];
        }

        $placard = $this->firstMatch($text, $this->config['nsrdi_placard_words'] ?? []);
        $exterior = $this->firstMatch($text, $this->config['nsrdi_exterior_words'] ?? []);

        if ($this->firstMatch($text, $this->config['nsrdi_painting_keywords'] ?? []) || ($placard && $exterior)) {
            return ['category' => 'PAINTING', 'check' => false];
        }
        if ($this->firstMatch($text, $this->config['nsrdi_painting_uncertain'] ?? [])) {
            return ['category' => 'PAINTING', 'check' => true];
        }

        return ['category' => 'CBM', 'check' => (bool) $placard];
    }

    public function inferNsrdiCategory(?string $description): ?string
    {
        return $this->nsrdiCategory($description)['category'];
    }

    /**
     * The WO tab has no ATA column, but its TASK CARD number carries the chapter:
     *   262400-RAI-12010-2-IDN, 335121-RAI-10000-1-IDN   six digits first (chapter = first two)
     *   A32-215200-02-2-02-IDN                            type prefix, then six digits
     *   B789-25-430-00-01-IDN, GEN-EA-25-015-IDN,
     *   A320-EA-32-1094-IDN, A330-INT-12-668-IDN          chapter as its own two-digit segment
     * Generic cards such as 999999-LCC-00000-3-WA-C-IDN (line check) give no chapter.
     */
    public static function ataFromTaskCard(?string $taskCard): ?string
    {
        $card = strtoupper(trim((string) $taskCard));
        if ($card === '' || str_starts_with($card, '999999')) {
            return null;
        }
        foreach (['/^(\d{2})\d{4}-/', '/^[A-Z0-9]+-(\d{2})\d{4}-/', '/-(\d{2})-\d{2,4}(?:-|$)/'] as $pattern) {
            if (preg_match($pattern, $card, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /** Upper-case, punctuation to spaces, single spaces: "Life-Vest  (L/H)" => "LIFE VEST L H". */
    public function normalise(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9]+/', ' ', strtoupper($text))));
    }

    /**
     * Whole-word match with an optional plural suffix, so SEAT matches "SEATS" but not "SEATTLE".
     *
     * @param  array<int, string>  $keywords
     */
    private function firstMatch(string $normalisedText, array $keywords): ?string
    {
        if ($normalisedText === '') {
            return null;
        }

        foreach ($keywords as $keyword) {
            $needle = $this->normalise($keyword);
            if ($needle === '') {
                continue;
            }
            if (preg_match('/(?<![A-Z0-9])'.preg_quote($needle, '/').'(?:S|ES)?(?![A-Z0-9])/', $normalisedText)) {
                return $keyword;
            }
        }

        return null;
    }

    private function result(string $action, string $rule, string $reason): array
    {
        return ['action' => $action, 'rule' => $rule, 'reason' => $reason];
    }
}
