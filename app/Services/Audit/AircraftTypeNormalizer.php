<?php

namespace App\Services\Audit;

/**
 * Turns the many spellings of an aircraft type into a fleet (B737, A320, A330, ATR72, ...) and a variant.
 *
 *   B737-800NG / B737-800 / B738  -> B737 / 800
 *   B738MAX / B737MAX / B737-8 / B737-MAX 8 -> B737 / MAX 8        (-8 is the MAX 8 designation)
 *   B737-9                        -> B737 / MAX 9
 *   B739ER                        -> B737 / 900ER
 *   A320 NEO                      -> A320 / NEO
 * The original text is always kept by the caller in aircrafts.type_raw.
 */
class AircraftTypeNormalizer
{
    /** @return array{fleet: string|null, variant: string|null} */
    public function normalize(?string $raw): array
    {
        $type = strtoupper(trim((string) $raw));
        if ($type === '') {
            return ['fleet' => null, 'variant' => null];
        }

        $compact = preg_replace('/[\s\-_\/]+/', '', $type);

        if (str_starts_with($compact, 'A320')) {
            return ['fleet' => 'A320', 'variant' => str_contains($compact, 'NEO') ? 'NEO' : null];
        }
        if (str_starts_with($compact, 'A330')) {
            return ['fleet' => 'A330', 'variant' => null];
        }
        if (str_starts_with($compact, 'ATR72')) {
            return ['fleet' => 'ATR72', 'variant' => null];
        }

        if (preg_match('/^B73[789]/', $compact)) {
            return ['fleet' => 'B737', 'variant' => $this->boeingVariant($compact)];
        }

        // C172, BE58 and anything else: the type itself is the fleet
        return ['fleet' => $compact, 'variant' => null];
    }

    private function boeingVariant(string $compact): ?string
    {
        if (str_contains($compact, 'MAX')) {
            // B738MAX, B737MAX, B737MAX8, B737-MAX 8
            if (preg_match('/MAX([789])/', $compact, $m)) {
                return 'MAX '.$m[1];
            }

            return str_starts_with($compact, 'B739') ? 'MAX 9' : (str_starts_with($compact, 'B738') ? 'MAX 8' : 'MAX');
        }
        if (str_starts_with($compact, 'B739ER') || str_contains($compact, '900ER')) {
            return '900ER';
        }
        if (preg_match('/^B737([789])$/', $compact, $m)) {
            return 'MAX '.$m[1];            // B737-7 / -8 / -9 are the MAX models
        }
        if (str_starts_with($compact, 'B738') || str_contains($compact, '800')) {
            return '800';                    // 800NG, 800 and B738 are the same type
        }

        return null;                         // plain "B737": variant unknown
    }
}
