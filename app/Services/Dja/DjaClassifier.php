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
    public function classify(string $kind, ?string $ata, ?string $category, ?string $description, ?string $workGroup = null): array
    {
        return $kind === 'nsrdi'
            ? $this->classifyNsrdi($category, $description)
            : $this->classifyCabin($kind, $ata, $description, $workGroup);
    }

    private function classifyNsrdi(?string $category, ?string $description = null): array
    {
        $text = $description ? $this->normalise($description) : '';

        // Hard non-cabin check (cargo, cockpit windows, airframe systems): never CBM
        $hardExclude = $text !== '' ? $this->firstMatch($text, $this->config['hard_exclude'] ?? []) : null;
        if ($hardExclude !== null) {
            return $this->result(self::REJECT, 'keyword.hard_exclude', "NSRDI non-cabin \"{$hardExclude}\" langsung ditolak.");
        }

        $exclude = $text !== '' ? $this->firstMatch($text, $this->config['exclude'] ?? []) : null;

        $category = strtoupper(trim((string) $category));
        $allowed = array_map('strtoupper', $this->config['nsrdi_categories']);

        if ($exclude !== null) {
            return in_array($category, $allowed, true)
                ? $this->result(self::REVIEW, 'keyword.conflict', "Kategori {$category} tetapi memuat kata non-cabin \"{$exclude}\".")
                : $this->result(self::REJECT, 'keyword.exclude', "Memuat kata non-cabin \"{$exclude}\".");
        }

        if ($category === '') {
            return $this->result(self::REVIEW, 'nsrdi.no_category', 'Kolom CATEGORY kosong, tidak bisa ditentukan otomatis.');
        }

        return in_array($category, $allowed, true)
            ? $this->result(self::ACCEPT, 'nsrdi.category', "Kategori {$category}.")
            : $this->result(self::REJECT, 'nsrdi.category_excluded', "Kategori {$category} bukan ".implode('/', $allowed).'.');
    }

    private function classifyCabin(string $kind, ?string $ata, ?string $description, ?string $workGroup = null): array
    {
        // 1. Work group check: if specified and is non-cabin (AVIONICS, MECHANIC, STRUCTURE, etc.), reject immediately.
        $wg = strtoupper(trim((string) $workGroup));
        if ($wg !== '') {
            foreach ($this->config['work_group_exclude'] ?? [] as $excludedWg) {
                if ($wg === $excludedWg || str_starts_with($wg, $excludedWg.' ') || str_contains($wg, ' '.$excludedWg)) {
                    return $this->result(self::REJECT, 'work_group.reject', "Work Group \"{$workGroup}\" bukan Cabin.");
                }
            }
        }

        // 2. ATA reject check
        $ataPrefix = preg_match('/^\s*(\d{2})/', (string) $ata, $m) ? $m[1] : '';
        if ($ataPrefix !== '' && in_array($ataPrefix, $this->config['ata_reject'], true)) {
            return $this->result(self::REJECT, 'ata.reject', "ATA {$ataPrefix} dikecualikan.");
        }

        $text = $this->normalise((string) $description);

        // 3. Absolute non-cabin check (cargo, cockpit windows, landing gear, etc.): never cabin regardless of ATA
        $hardExclude = $this->firstMatch($text, $this->config['hard_exclude'] ?? []);
        if ($hardExclude !== null) {
            return $this->result(self::REJECT, 'keyword.hard_exclude', "Pekerjaan non-cabin \"{$hardExclude}\" langsung ditolak.");
        }

        // 4. Cockpit emergency equipment exception: life vests, flashlights, PBE, etc. located in cockpit
        $isCockpit = (bool) preg_match('/\b(COCKPIT|CKPT|FLIGHT DECK)\b/', $text);
        if ($isCockpit) {
            $cockpitEmergency = $this->firstMatch($text, $this->config['cockpit_emergency_include'] ?? []);
            if ($cockpitEmergency !== null) {
                return $this->result(self::ACCEPT, 'keyword.cockpit_emergency', "Emergency equipment cockpit \"{$cockpitEmergency}\" termasuk CBM.");
            }

            return $this->result(self::REJECT, 'keyword.cockpit_exclude', 'Pekerjaan cockpit non-emergency equipment bukan Cabin.');
        }

        // 6. Aerodynamic surfaces exception: escape slides (offwing/overwing) and lavatory flap vs wing flap
        if (preg_match('/\b(WING|FLAP|SLAT|SPOILER|AILERON|RUDDER|ELEVATOR)\b/', $text)) {
            $isSlide = (bool) preg_match('/\b(OFFWING|OVERWING|OFF WING|OVER WING|ESCAPE SLIDE|SLIDE RAFT|LIFE RAFT)\b/', $text);
            $isWasteFlap = (bool) preg_match('/\b(WASTE FLAP|BIN FLAP|TOILET|LAVATORY|WASTE COMPARTMENT)\b/', $text);
            if (! $isSlide && ! $isWasteFlap) {
                return $this->result(self::REJECT, 'keyword.aerodynamic_surface', 'Pekerjaan wing/flap/aerodynamic surface bukan Cabin.');
            }
        }

        $exclude = $this->firstMatch($text, $this->config['exclude'] ?? []);

        // 7. If it contains other non-cabin words (CAPT, F/O, ENGINE, etc.)
        if ($exclude !== null) {
            $include = $this->firstMatch($text, $this->config[$kind]['include'] ?? []);
            $hasAcceptedAta = ($ataPrefix !== '' && in_array($ataPrefix, $this->config['ata_accept'], true));

            if ($include !== null || $hasAcceptedAta) {
                return $this->result(self::REVIEW, 'keyword.conflict', "Memuat kata cabin tetapi juga memuat kata non-cabin \"{$exclude}\".");
            }

            return $this->result(self::REJECT, 'keyword.exclude', "Memuat kata non-cabin \"{$exclude}\".");
        }

        // 8. ATA accept check (safe because non-cabin words have already been checked)
        if ($ataPrefix !== '' && in_array($ataPrefix, $this->config['ata_accept'], true)) {
            return $this->result(self::ACCEPT, 'ata.accept', "ATA {$ataPrefix} termasuk cabin.");
        }

        // 9. Keyword include check
        $include = $this->firstMatch($text, $this->config[$kind]['include'] ?? []);
        if ($include !== null) {
            return $this->result(self::ACCEPT, 'keyword.include', "Kata kunci \"{$include}\".");
        }

        // 10. Weak keywords (review list)
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
