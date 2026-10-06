<?php

namespace App\Imports\Sheets;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Import satu sheet Daily Report.
 *
 * PENTING: class ini TIDAK memakai WithCalculatedFormulas.
 * Di file Anda, satu-satunya rumus ada di kolom S (ACT STA) sheet NSRDI: rumus
 * COUNTIFS sepanjang ~3000 karakter yang mengacu ke seluruh kolom (A:A, B:B, ...)
 * di workbook eksternal "List AC". Kalau dihitung ulang oleh PhpSpreadsheet,
 * itulah yang membuat memory habis di Coordinate.php. Jadi kita hanya membaca
 * nilai cache yang sudah disimpan Excel di dalam file.
 */
class DailyReportSheetImport implements OnEachRow
{
    /** Kolom yang isinya tanggal (setelah header di-slug). */
    private const DATE_COLUMNS = [
        'date', 'refresh_date', 'report_date', 'due_date', 'plan_date', 'close_date',
    ];

    /** Kolom nomor dokumen: paksa jadi string + trim (di file ada yang int, ada yang ' ID027943'). */
    private const STRING_ID_COLUMNS = ['wo', 'no_doc', 'dmi_no', 'nsrdi'];

    /** Kolom yang isinya angka desimal berkoma di file ('1,00', '0,25'). */
    private const DECIMAL_COMMA_COLUMNS = ['man_hours'];

    /** Header dicari di N baris pertama (baris 1 = tanggal + banner, header asli di baris 2). */
    private const HEADER_SCAN_ROWS = 10;

    /** Slug header yang menandai sebuah baris adalah baris header. */
    private const HEADER_MARKERS = ['ac_reg', 'reg'];

    /** [huruf kolom => slug header], diisi saat baris header ditemukan. */
    private array $headers = [];

    private bool $headerFound = false;

    private array $stats = ['read' => 0, 'skipped' => 0, 'saved' => 0];

    public function __construct(private string $type) {}

    public function onRow(Row $row): void
    {
        // Belum ketemu header: baris di atas header (tanggal, banner) dilewati.
        if (! $this->headerFound) {
            if ($row->getIndex() <= self::HEADER_SCAN_ROWS) {
                $this->tryReadHeader($row);
            }

            return;
        }

        $data = $this->readRow($row);

        // Lewati baris kosong / baris sampah (mis. sel nyasar berisi ``` di UNPLANNED).
        // Baris valid harus punya nomor registrasi pesawat.
        $reg = $data['ac_reg'] ?? $data['reg'] ?? null;
        if ($reg === null) {
            $this->stats['skipped']++;

            return;
        }

        // Sheet NSRDI tidak punya kolom DATE. Tanggal data = PLAN DATE (tanggal data itu keluar
        // di laporan), BUKAN REFRESH DATE (tanggal data lama). refresh_date tetap disimpan
        // sebagai field sendiri.
        if ($this->type === 'NSRDI') {
            $data['date'] = $data['plan_date'] ?? null;
        }

        $this->stats['read']++;
        $this->store($this->resolveTarget($data), $data, $row->getIndex());
    }

    // ---------------------------------------------------------------- header

    /**
     * Baris dianggap header kalau salah satu selnya bernama 'AC REG' / 'REG'.
     * Baris 1 (tanggal + banner) tidak pernah memenuhi syarat ini, jadi otomatis terlewati.
     */
    private function tryReadHeader(Row $row): void
    {
        $candidate = [];

        $it = $row->getDelegate()->getCellIterator();
        $it->setIterateOnlyExistingCells(true);

        foreach ($it as $cell) {
            $text = trim((string) $cell->getValue());
            if ($text !== '') {
                $candidate[$cell->getColumn()] = Str::slug($text, '_'); // "AC REG" => "ac_reg"
            }
        }

        if (array_intersect(self::HEADER_MARKERS, $candidate)) {
            $this->headers = $candidate;
            $this->headerFound = true;
        }
    }

    // ------------------------------------------------------------------ baris

    private function readRow(Row $row): array
    {
        $data = [];
        $it = $row->getDelegate()->getCellIterator();
        $it->setIterateOnlyExistingCells(true);

        foreach ($it as $cell) {
            $key = $this->headers[$cell->getColumn()] ?? null;
            if ($key === null) {
                continue;
            }
            $data[$key] = $this->normalize($key, $this->cellValue($cell));
        }

        return $data;
    }

    /** Nilai mentah sel; untuk rumus ambil hasil cache Excel, bukan hitung ulang. */
    private function cellValue(Cell $cell): mixed
    {
        if ($cell->isFormula()) {
            $cached = $cell->getOldCalculatedValue();

            // Cache berupa error Excel (#NAME?, #REF!, ...) = tidak ada nilai berguna.
            // Di file Anda 4 sel kolom S sheet NSRDI bernilai #NAME? (IFS/IFNA + link eksternal).
            if ($cached === null || (is_string($cached) && str_starts_with($cached, '#'))) {
                return null;
            }

            return $cached;
        }

        return $cell->getValue();
    }

    private function normalize(string $key, mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if ($value === null) {
            return null;
        }

        if (in_array($key, self::DATE_COLUMNS, true)) {
            return $this->parseDate($value, $key);
        }

        if (in_array($key, self::STRING_ID_COLUMNS, true)) {
            return trim((string) $value);
        }

        if (in_array($key, self::DECIMAL_COMMA_COLUMNS, true) && is_string($value)) {
            $num = str_replace(',', '.', $value);

            return is_numeric($num) ? (float) $num : $value;
        }

        return $value;
    }

    /**
     * Tanggal di file campur: serial Excel, DateTime, atau teks seperti '8-Jan-27' / '25-Dec-2026'
     * (kolom DUE DATE di sheet NSRDI seluruhnya berupa teks).
     * Catatan: read_only=true membuat PhpSpreadsheet tidak membaca format sel,
     * jadi tanggal datang sebagai angka; karena itu kolom tanggal ditentukan lewat nama kolom.
     */
    private function parseDate(mixed $value, string $key): ?string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->toDateString();
            }

            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->toDateString();
            }

            $text = trim((string) $value);

            if (preg_match('/^\d{1,2}-[A-Za-z]{3}-(\d{2}|\d{4})$/', $text, $m)) {
                $format = strlen($m[1]) === 2 ? 'j-M-y' : 'j-M-Y';

                return Carbon::createFromFormat($format, $text)->toDateString();
            }

            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            Log::warning("DailyReport import: tanggal tidak terbaca di kolom {$key}: ".json_encode($value));

            return null;
        }
    }

    // -------------------------------------------------------------- tujuan DB

    /**
     * Tab di UI: DJA WO, Unplanned WO, DJA DMI, Unplanned DMI, DJA NSRDI, Unplanned NSRDI, CML.
     * Sheet UNPLANNED berisi kolom DOC TYPE (NSRDI / WO), jadi dipecah berdasarkan itu.
     * (Asumsi saya; sesuaikan kalau aturan bisnisnya beda.)
     */
    private function resolveTarget(array $data): string
    {
        if ($this->type === 'UNPLANNED') {
            return 'unplanned_'.strtolower($data['doc_type'] ?? 'unknown'); // unplanned_wo / unplanned_nsrdi / unplanned_dmi
        }

        return match ($this->type) {
            'WO' => 'wo',
            'DMI' => 'dmi',
            'NSRDI' => 'nsrdi',
            'CML' => 'cml',
        };
    }

    /**
     * TODO: ganti isi method ini dengan penyimpanan ke model/tabel Anda.
     * $data sudah bersih: tanggal = 'Y-m-d', nomor dokumen = string, sel kosong = null,
     * key = slug header Excel (ac_reg, wo_description, plan_sta, act_sta, status, ...).
     *
     * Catatan: nomor NSRDI di file ada yang muncul dobel (mis. NWA033028), jadi jangan
     * pasang unique index pada nomor saja tanpa memutuskan aturan duplikatnya.
     */
    private function store(string $target, array $data, int $rowIndex): void
    {
        // Contoh:
        // match ($target) {
        //     'wo'    => \App\Models\DailyWo::create($this->only($data, [...])),
        //     'cml'   => \App\Models\DailyCml::create(...),
        //     default => null,
        // };

        $this->stats['saved']++;
    }

    public function __destruct()
    {
        if (! $this->headerFound) {
            Log::warning("DailyReport import [{$this->type}]: header (AC REG / REG) tidak ditemukan di ".self::HEADER_SCAN_ROWS.' baris pertama');
        }

        Log::info("DailyReport import [{$this->type}] selesai", $this->stats);
    }
}
