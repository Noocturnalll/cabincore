<?php

namespace App\Livewire\Modules\Compliance;

use App\Helpers\RoleHelper;
use App\Models\Airport;
use App\Models\ComplianceEntry;
use App\Models\SyncSetting;
use App\Services\Compliance\ComplianceReport;
use App\Services\Compliance\ComplianceSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Daily briefing, attendant list and 5R evidence per unit (AIEC, Cabin Maintenance, Aircraft Painting, Tambahan),
 * station and shift. AIEC's evidence is mirrored from the Apps Script sheet; every unit can also be uploaded here.
 *
 * compliance.view   read (every station for roles that follow a division, otherwise the user's own station)
 * compliance.upload upload / remove evidence, only for the unit the role belongs to (Manager, Super Admin, Deputy: all)
 */
class Index extends Component
{
    use WithFileUploads;

    #[Url(as: 'unit')]
    public string $unit = 'AIEC';

    public string $range = '7';          // '7', '30', '90', 'all'

    public ?string $station = null;

    /** @var array<string, mixed> uploads keyed "{date}_{shift}_{doc}" (expected shifts) or "e{entryId}_{doc}" (Tambahan) */
    public array $files = [];

    // Tambahan: a new line
    public ?string $extraStation = null;

    public ?string $extraDate = null;

    public string $extraShift = 'Pagi';

    public ?string $extraNote = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('compliance.view'), 403);

        if (! array_key_exists($this->unit, config('compliance.units'))) {
            $this->unit = 'AIEC';
        }
        $this->extraDate = now()->toDateString();
    }

    // ── scope & permission ───────────────────────────────────────────────

    /** @return array<int, string>|null null = every station */
    private function allowedStations(): ?array
    {
        $user = auth()->user();
        $follows = $user->hasAnyRole([...RoleHelper::ALL_PIC, ...RoleHelper::COD_DESK, RoleHelper::ADMIN_CGK]);
        if ($user->can('registry.view_all') || $follows || ! $user->station) {
            return null;
        }

        return [strtoupper($user->station)];
    }

    private function canUpload(string $unit): bool
    {
        $user = auth()->user();
        if (! $user->can('compliance.upload')) {
            return false;
        }

        return $user->hasAnyRole(config('compliance.upload_all_roles'))
            || $user->hasAnyRole(config("compliance.units.$unit.roles", []));
    }

    private function stationAllowed(string $station): bool
    {
        $allowed = $this->allowedStations();

        return $allowed === null || in_array(strtoupper($station), $allowed, true);
    }

    // ── navigation ───────────────────────────────────────────────────────

    public function setUnit(string $unit): void
    {
        $this->unit = array_key_exists($unit, config('compliance.units')) ? $unit : 'AIEC';
        $this->station = null;
        $this->files = [];
    }

    public function setRange(string $range): void
    {
        $this->range = in_array($range, ['7', '30', '90', 'all'], true) ? $range : '7';
    }

    public function pick(?string $station): void
    {
        $this->station = $this->station === $station ? null : $station;
        $this->files = [];
    }

    public function syncNow(ComplianceSyncService $service): void
    {
        abort_unless(auth()->user()->can('registry.view_all'), 403);

        $stats = $service->sync();
        $this->dispatch('notify', $stats === null
            ? ['icon' => 'error', 'message' => $service->lastError() ?? 'Sinkronisasi gagal.', 'timer' => 7000]
            : ['icon' => 'success', 'message' => "{$stats['read']} entri dibaca, {$stats['created']} baru."]);
    }

    // ── evidence ─────────────────────────────────────────────────────────

    /**
     * Livewire hook: a file was chosen in $files[$key]. It is validated, stored, and the document column of the
     * station / date / shift line is pointed at it. $key is "{date}_{shift}_{doc}" or, for Tambahan, "e{entryId}_{doc}".
     */
    public function updatedFiles($value, $key): void
    {
        $file = $value;
        unset($this->files[$key]);
        if (! $file || ! is_object($file)) {
            return;
        }

        $max = (int) config('compliance.upload.max_mb') * 1024;
        $validator = Validator::make(['file' => $file], ['file' => ['required', 'file', 'mimes:'.implode(',', config('compliance.upload.mimes')), "max:$max"]], [
            'file.mimes' => 'Bukti harus berupa foto (JPG, PNG, WEBP) atau PDF.',
            'file.max' => 'Ukuran bukti maksimal '.config('compliance.upload.max_mb').' MB.',
            'file.file' => 'Berkas tidak valid.',
            'file.uploaded' => 'Unggahan gagal. Coba lagi.',
        ]);
        if ($validator->fails()) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => $validator->errors()->first('file'), 'timer' => 6000]);

            return;
        }

        abort_unless($this->canUpload($this->unit), 403, 'Anda tidak dapat mengunggah bukti untuk unit ini.');
        abort_unless($this->station && $this->stationAllowed($this->station), 403);

        // which line is it for?
        if (preg_match('/^e(\d+)_(brf|att|5r)$/', (string) $key, $m)) {
            abort_unless($this->unit === 'EXTRA', 422);
            $entry = ComplianceEntry::where('unit', 'EXTRA')->where('station', $this->station)->findOrFail((int) $m[1]);
            $doc = $m[2];
        } elseif (preg_match('/^(\d{4}-\d{2}-\d{2})_([A-Za-z]+)_(brf|att|5r)$/', (string) $key, $m)) {
            [$date, $shift, $doc] = [$m[1], ucfirst(strtolower($m[2])), $m[3]];
            abort_unless(app(ComplianceReport::class)->slots($this->unit, $this->station, Carbon::parse($date), Carbon::parse($date)) !== []
                && in_array([$date, $shift], app(ComplianceReport::class)->slots($this->unit, $this->station, Carbon::parse($date), Carbon::parse($date)), true), 422, 'Shift ini tidak ada pada jadwal.');
            abort_unless(Carbon::parse($date)->lte(now()->endOfDay()), 422);

            $entry = ComplianceEntry::where('unit', $this->unit)->where('station', $this->station)
                ->whereDate('work_date', $date)->where('shift', $shift)->orderBy('id')->first()
                ?? new ComplianceEntry([
                    'entry_id' => 'WEB|'.$this->unit.'|'.$this->station.'|'.$date.'|'.$shift,
                    'unit' => $this->unit, 'station' => $this->station, 'work_date' => $date, 'shift' => $shift,
                ]);
        } else {
            abort(422, 'Kunci unggahan tidak dikenal.');
        }

        $disk = config('compliance.upload.disk');
        $dir = config('compliance.upload.dir').'/'.$entry->unit.'/'.Carbon::parse($entry->work_date)->format('Y-m');
        $name = strtolower($entry->station.'_'.Carbon::parse($entry->work_date)->format('Ymd').'_'.$entry->shift.'_'.$doc.'_'.now()->format('His').'_'.Str::lower(Str::random(5))).'.'.$file->extension();
        $path = $file->storeAs($dir, $name, $disk);

        $column = 'url_'.$doc;
        $this->deleteFileOf($entry->{$column});
        $entry->{$column} = Storage::disk($disk)->url($path);
        $entry->uploaded_by = auth()->id();
        $entry->submitted_at = $entry->submitted_at ?: now();
        $entry->save();

        $this->dispatch('notify', ['icon' => 'success', 'message' => config('compliance.documents.'.$doc).' tersimpan.']);
    }

    public function removeDoc(int $entryId, string $doc): void
    {
        $entry = ComplianceEntry::where('unit', $this->unit)->findOrFail($entryId);
        abort_unless($this->canUpload($entry->unit) && $this->stationAllowed($entry->station), 403);
        abort_unless(array_key_exists($doc, config('compliance.documents')), 422);

        $column = 'url_'.$doc;
        $this->deleteFileOf($entry->{$column});
        $entry->update([$column => null]);
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Bukti dihapus.']);
    }

    public function addExtra(): void
    {
        abort_unless($this->canUpload('EXTRA') && $this->unit === 'EXTRA', 403);

        $data = $this->validate([
            'extraStation' => ['required', 'string', 'regex:/^[A-Za-z]{3,4}$/'],
            'extraDate' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.now()->subDays(60)->toDateString()],
            'extraShift' => ['required', 'in:'.implode(',', config('compliance.shifts'))],
            'extraNote' => ['nullable', 'string', 'max:200'],
        ], [
            'extraStation.regex' => 'Kode station 3-4 huruf (contoh: CGK).',
            'extraDate.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'extraDate.after_or_equal' => 'Tanggal maksimal 60 hari ke belakang.',
        ], ['extraStation' => 'station', 'extraDate' => 'tanggal', 'extraShift' => 'shift']);

        $station = strtoupper($data['extraStation']);
        abort_unless($this->stationAllowed($station), 403);

        ComplianceEntry::create([
            'entry_id' => 'WEB|EXTRA|'.$station.'|'.$data['extraDate'].'|'.$data['extraShift'].'|'.now()->format('His'),
            'unit' => 'EXTRA', 'station' => $station, 'work_date' => $data['extraDate'], 'shift' => $data['extraShift'],
            'uploaded_by' => auth()->id(), 'submitted_at' => now(), 'note' => $data['extraNote'] ?: null,
        ]);

        $this->reset(['extraNote']);
        $this->station = $station;
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Baris tambahan dibuat. Unggah buktinya di bawah.']);
    }

    private function deleteFileOf(?string $url): void
    {
        $disk = config('compliance.upload.disk');
        $prefix = rtrim(Storage::disk($disk)->url(''), '/').'/';
        if ($url && str_starts_with($url, $prefix)) {
            Storage::disk($disk)->delete(substr($url, strlen($prefix)));
        }
    }

    // ── render ───────────────────────────────────────────────────────────

    public function render(ComplianceReport $report)
    {
        $to = now()->startOfDay();
        $from = match ($this->range) {
            'all' => Carbon::parse(collect(config('compliance.stations'))->min('since')),
            default => $to->copy()->subDays((int) $this->range - 1),
        };

        $allowed = $this->allowedStations();
        $data = $report->build($from, $to, $allowed, $this->unit);

        $selected = $this->station && isset($data['stations'][$this->station]) ? $this->station : null;
        $entries = collect();
        $shiftSlots = [];
        if ($selected) {
            $shiftSlots = $this->unit === 'EXTRA' ? [] : app(ComplianceReport::class)->slots($this->unit, $selected, $from, $to->copy()->endOfDay());
            $entries = ComplianceEntry::where('unit', $this->unit)->where('station', $selected)
                ->whereDate('work_date', '>=', $from->toDateString())->whereDate('work_date', '<=', $to->toDateString())
                ->orderByDesc('work_date')->orderBy('id')->get();
        }

        $setting = SyncSetting::for(ComplianceSyncService::Key);

        return view('livewire.modules.compliance.index', [
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'selected' => $selected,
            'entries' => $entries,
            'shiftSlots' => $shiftSlots,
            'units' => config('compliance.units'),
            'setting' => $setting,
            'canSync' => auth()->user()->can('registry.view_all') && $this->unit === 'AIEC',
            'canUpload' => $this->canUpload($this->unit),
            'documents' => config('compliance.documents'),
            'shifts' => config('compliance.shifts'),
            'stationOptions' => $this->unit === 'EXTRA'
                ? Airport::where('status', 'Aktif')->orderBy('kode')->pluck('kode')->when($allowed, fn ($c) => $c->intersect($allowed))->values()
                : collect(),
        ])->layout('components.layouts.app', ['title' => 'Compliance Harian']);
    }
}
