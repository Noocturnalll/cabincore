<?php

namespace App\Services\WhatsApp;

class WhatsAppEvidenceParser
{
    /**
     * Standard recognizable document types.
     */
    public const VALID_DOC_TYPES = ['WO', 'NSRDI', 'DMI', 'CML', 'AOC', 'TSK'];

    /**
     * Parse incoming message text / caption for T/C Type, Doc Type, and No Doc.
     *
     * @return array{
     *     tc_type: string|null,
     *     doc_type: string|null,
     *     doc_no: string|null,
     *     is_valid: bool
     * }
     */
    public function parse(?string $caption): array
    {
        if (empty($caption) || trim($caption) === '') {
            return [
                'tc_type' => null,
                'doc_type' => null,
                'doc_no' => null,
                'is_valid' => false,
            ];
        }

        $text = trim($caption);

        $tcType = $this->extractTcType($text);
        $docType = $this->extractDocType($text);
        $docNo = $this->extractDocNo($text, $docType);

        // If doc_type was not found in label, but doc_no has prefix like "NSRDI-...", infer it
        if (! $docType && $docNo) {
            $inferred = $this->inferDocTypeFromNumber($docNo);
            if ($inferred) {
                $docType = $inferred;
            }
        }

        // Positional fallback: e.g. "RECTIFICATION / NSRDI / 90213" or "REPAIR, WO, WO-123"
        if (! $docType || ! $docNo) {
            $positional = $this->parsePositional($text);
            if ($positional['is_valid']) {
                $tcType = $tcType ?: $positional['tc_type'];
                $docType = $docType ?: $positional['doc_type'];
                $docNo = $docNo ?: $positional['doc_no'];
            }
        }

        $isValid = ! empty($docType) && ! empty($docNo);

        return [
            'tc_type' => $tcType ? strtoupper(trim($tcType)) : null,
            'doc_type' => $docType ? strtoupper(trim($docType)) : null,
            'doc_no' => $docNo ? strtoupper(trim($docNo)) : null,
            'is_valid' => $isValid,
        ];
    }

    /**
     * Extract T/C Type from caption.
     */
    private function extractTcType(string $text): ?string
    {
        // 1. Explicit label pattern: T/C TYPE, TC TYPE, T/C, TC, TASK CARD
        if (preg_match('/(?:t\/?c\s*(?:type)?|task\s*card)\s*[:=\-]\s*([a-zA-Z0-9_\-\s\/]+?)(?=[,\n\r;]|$|doc|\bno\b)/i', $text, $matches)) {
            $val = trim($matches[1]);
            if ($val !== '') {
                return $val;
            }
        }

        // 2. Keyword heuristic search for known aviation task card types
        $knownTypes = [
            'RECTIFICATION', 'INSPECTION', 'MODIFICATION', 'REPAIR',
            'REPLACEMENT', 'REPLACE', 'OVERHAUL', 'SERVICING',
            'CLEANING', 'LUBRICATION', 'DEFERRED', 'CORRECTION',
            'TROUBLESHOOTING', 'REMOVAL', 'INSTALLATION', 'CHECK',
        ];

        foreach ($knownTypes as $type) {
            if (preg_match('/\b'.preg_quote($type, '/').'\b/i', $text)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Extract Document Type from caption.
     */
    private function extractDocType(string $text): ?string
    {
        // 1. Explicit label: DOC TYPE, DOC, DOKUMEN, TYPE
        if (preg_match('/(?:doc(?:ument)?\s*(?:type)?|dokumen|type)\s*[:=\-]\s*([a-zA-Z0-9]+)/i', $text, $matches)) {
            $candidate = strtoupper(trim($matches[1]));
            if (in_array($candidate, self::VALID_DOC_TYPES, true)) {
                return $candidate;
            }
        }

        // 2. Direct keyword in text
        foreach (self::VALID_DOC_TYPES as $type) {
            if (preg_match('/(?:\b|#)'.preg_quote($type, '/').'(?:\b|[:\-\d])/i', $text)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Extract Document Number from caption.
     */
    private function extractDocNo(string $text, ?string $docType): ?string
    {
        // 1. Explicit label: NO DOC, NO DOK, DOC NO, NOMOR, NO
        if (preg_match('/(?:(?:no|nomor)\s*(?:doc(?:ument)?|dokumen)?|doc(?:ument)?\s*no)\s*[:=\-]\s*([a-zA-Z0-9\-_.\/]+)/i', $text, $matches)) {
            $num = trim($matches[1]);
            if ($num !== '') {
                return $num;
            }
        }

        // 2. Regex matching document code prefix, e.g. NSRDI-1234, WO-9921
        if (preg_match('/\b(?:NSRDI|WO|DMI|CML|AOC)[-_ ]?([a-zA-Z0-9\-_]+)/i', $text, $matches)) {
            return trim($matches[0]);
        }

        // 3. If docType was specified, look for adjacent numbers or codes
        if ($docType && preg_match('/'.preg_quote($docType, '/').'\s*[:=\-]?\s*([a-zA-Z0-9\-_]+)/i', $text, $matches)) {
            if (strtoupper($matches[1]) !== $docType) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Infer document type from the document number string.
     */
    private function inferDocTypeFromNumber(string $docNo): ?string
    {
        $upper = strtoupper($docNo);
        foreach (self::VALID_DOC_TYPES as $type) {
            if (str_starts_with($upper, $type)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Parse positional separated string: e.g. "RECTIFICATION / NSRDI / 90213"
     *
     * @return array{tc_type: string|null, doc_type: string|null, doc_no: string|null, is_valid: bool}
     */
    private function parsePositional(string $text): array
    {
        // Try delimiters: \n, /, comma
        $delimiters = ["\n", '/', ','];

        foreach ($delimiters as $delim) {
            $parts = array_map('trim', explode($delim, $text));
            if (count($parts) >= 2) {
                $docType = null;
                $docNo = null;
                $tcType = null;

                foreach ($parts as $p) {
                    $upper = strtoupper($p);
                    if (in_array($upper, self::VALID_DOC_TYPES, true)) {
                        $docType = $upper;
                    } elseif (preg_match('/^(?:NSRDI|WO|DMI|CML)[-_ ]?\w+$/i', $p) || is_numeric($p)) {
                        $docNo = $p;
                    } elseif (strlen($p) >= 3) {
                        $tcType = $p;
                    }
                }

                if ($docType && $docNo) {
                    return [
                        'tc_type' => $tcType,
                        'doc_type' => $docType,
                        'doc_no' => $docNo,
                        'is_valid' => true,
                    ];
                }
            }
        }

        return ['tc_type' => null, 'doc_type' => null, 'doc_no' => null, 'is_valid' => false];
    }
}
