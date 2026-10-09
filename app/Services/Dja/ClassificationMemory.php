<?php

namespace App\Services\Dja;

use App\Models\ClassificationMemoryEntry;

/**
 * Remembers what a person decided about a WO / DMI - cabin work (CBM) or line maintenance (LINE) - so the same job
 * is recognised the next time without anyone looking at it again.
 *
 * A job is recognised by its task card (B789-25-430-00-01, the "-IDN" and crew suffixes ignored) or, when there is
 * none, by the first meaningful words of its description. Only decisions made by a person are stored. When people
 * decided the same job both ways the memory does not guess: the answer is CONFLICT and the job goes to review.
 */
class ClassificationMemory
{
    public const CBM = 'CBM';

    public const LINE = 'LINE';

    public const CONFLICT = 'CONFLICT';

    /** @return array{0: ?string, 1: ?string} signature, 'card' | 'text' */
    public static function signature(?string $taskCard, ?string $description): array
    {
        $card = strtoupper(trim((string) $taskCard));
        if ($card !== '' && ! str_starts_with($card, '999999')) {
            $card = preg_replace('/-IDN$/', '', $card);
            $card = preg_replace('/-[A-Z]{2,4}$/', '', $card);   // crew / variant tag such as -GEF

            if (preg_match('/\d/', $card)) {
                return [mb_substr($card, 0, 110), 'card'];
            }
        }

        $words = [];
        foreach (preg_split('/[^A-Z0-9]+/', strtoupper((string) $description), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (strlen($word) >= 3 && ! preg_match('/\d/', $word)) {   // positions, serials and part numbers vary job to job
                $words[] = $word;
            }
            if (count($words) === 6) {
                break;
            }
        }

        return count($words) >= 2 ? [implode(' ', $words), 'text'] : [null, null];
    }

    public function remember(string $kind, ?string $taskCard, ?string $description, string $category, ?int $userId = null): bool
    {
        if (! in_array($category, [self::CBM, self::LINE], true) || ! in_array($kind, ['wo', 'dmi'], true)) {
            return false;
        }
        [$signature, $type] = self::signature($taskCard, $description);
        if (! $signature) {
            return false;
        }

        $entry = ClassificationMemoryEntry::firstOrNew(['kind' => $kind, 'signature' => $signature, 'category' => $category]);
        $entry->fill([
            'signature_type' => $type,
            'hits' => ($entry->exists ? $entry->hits : 0) + 1,
            'example' => mb_substr(trim((string) $description), 0, 250) ?: $entry->example,
            'decided_by' => $userId ?? $entry->decided_by,
        ])->save();

        return true;
    }

    /** @return array{category: string, hits: int, signature: string}|null null when nothing is remembered for this job */
    public function lookup(string $kind, ?string $taskCard, ?string $description): ?array
    {
        // The task card is the stronger identity; fall back to the description when the card has never been decided
        $candidates = [];
        [$cardSig, $cardType] = self::signature($taskCard, null);
        if ($cardType === 'card') {
            $candidates[] = $cardSig;
        }
        [$textSig, $textType] = self::signature(null, $description);
        if ($textType === 'text') {
            $candidates[] = $textSig;
        }

        foreach ($candidates as $signature) {
            $entries = ClassificationMemoryEntry::where('kind', $kind)->where('signature', $signature)->get();
            if ($entries->isEmpty()) {
                continue;
            }
            $cbm = (int) $entries->where('category', self::CBM)->sum('hits');
            $line = (int) $entries->where('category', self::LINE)->sum('hits');

            return [
                'category' => $cbm && $line ? self::CONFLICT : ($cbm ? self::CBM : self::LINE),
                'hits' => $cbm + $line,
                'signature' => $signature,
            ];
        }

        return null;
    }
}
