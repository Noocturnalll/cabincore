<?php

namespace App\Livewire\Modules\Lgt;

use App\Models\Aircraft;
use App\Models\LgtRecord;
use App\Models\RosterEntry;
use App\Services\Audit\AocResolver;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Long Ground Time plan and result. Somebody lists the aircraft that will sit on the ground for a long time and the
 * jobs for the CBM and the AIEC (cleaning) team; the teams then close or cancel each job. The monitor
 * (Analitik > LGT Monitoring) summarises what is entered here.
 * lgt.view reads (own station unless registry.view_all), lgt.manage plans and updates.
 */
class Index extends Component
{
    public const STATUSES = ['OPEN', 'CLOSED', 'CANCEL'];

    #[Url(as: 'd')]
    public string $date = '';

    #[Url(as: 'sta')]
    public string $station = '';

    #[Url(as: 's')]
    public string $statusFilter = '';

    public bool $isOpen = false;

    public ?int $recordId = null;

    public string $form_date = '';

    public string $form_station = '';

    public string $aircraft_registration = '';

    public ?string $sta_time = null;

    public ?string $std_time = null;

    public ?string $cbm_action = null;

    public ?string $cbm_mp = null;

    public string $cbm_status = 'OPEN';

    public ?string $aiec_action = null;

    public ?string $aiec_mp = null;

    public string $aiec_status = 'OPEN';

    public ?string $reason = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('lgt.view'), 403);
        $this->date = $this->date ?: now()->toDateString();
    }

    private function seesAll(): bool
    {
        return (bool) auth()->user()->can('registry.view_all');
    }

    private function ownStation(): ?string
    {
        return $this->seesAll() ? null : (auth()->user()->station ? strtoupper(auth()->user()->station) : null);
    }

    private function scoped()
    {
        $own = $this->ownStation();

        return LgtRecord::query()->when($own, fn ($q) => $q->where('station', $own));
    }

    public function move(int $days): void
    {
        $this->date = Carbon::parse($this->date)->addDays($days)->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function create(?int $copyFrom = null): void
    {
        abort_unless(auth()->user()->can('lgt.manage'), 403);
        $this->resetForm();
        $this->form_date = $this->date;
        $this->form_station = $this->ownStation() ?? $this->station;

        if ($copyFrom) {   // another job for the same aircraft: keep aircraft, station and times
            $r = $this->scoped()->findOrFail($copyFrom);
            $this->form_date = $r->work_date->toDateString();
            $this->form_station = $r->station;
            $this->aircraft_registration = $r->aircraft_registration;
            $this->sta_time = $r->sta_time;
            $this->std_time = $r->std_time;
        }
        $this->isOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('lgt.manage'), 403);
        $r = $this->scoped()->findOrFail($id);

        $this->resetForm();
        $this->recordId = $r->id;
        $this->form_date = $r->work_date->toDateString();
        $this->form_station = $r->station;
        $this->aircraft_registration = $r->aircraft_registration;
        $this->sta_time = $r->sta_time;
        $this->std_time = $r->std_time;
        $this->cbm_action = $r->cbm_action;
        $this->cbm_mp = $r->cbm_mp;
        $this->cbm_status = $r->cbm_status ?: 'OPEN';
        $this->aiec_action = $r->aiec_action;
        $this->aiec_mp = $r->aiec_mp;
        $this->aiec_status = $r->aiec_status ?: 'OPEN';
        $this->reason = $r->reason;
        $this->isOpen = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('lgt.manage'), 403);
        $this->aircraft_registration = strtoupper(trim($this->aircraft_registration));
        $this->form_station = strtoupper(trim($this->form_station));

        $own = $this->ownStation();
        $this->validate([
            'form_date' => ['required', 'date'],
            'form_station' => ['required', 'string', 'max:10', $own ? Rule::in([$own]) : 'string'],
            'aircraft_registration' => ['required', 'string', 'max:20'],
            'sta_time' => ['nullable', 'date_format:H:i'],
            'std_time' => ['nullable', 'date_format:H:i'],
            'cbm_action' => ['nullable', 'string', 'max:250', 'required_without:aiec_action'],
            'aiec_action' => ['nullable', 'string', 'max:250'],
            'cbm_mp' => ['nullable', 'string', 'max:250'],
            'aiec_mp' => ['nullable', 'string', 'max:250'],
            'cbm_status' => ['required', Rule::in(self::STATUSES)],
            'aiec_status' => ['required', Rule::in(self::STATUSES)],
            'reason' => [($this->cbm_status === 'CANCEL' || $this->aiec_status === 'CANCEL') ? 'required' : 'nullable', 'string', 'max:250'],
        ], [
            'cbm_action.required_without' => 'Isi pekerjaan CBM atau pekerjaan AIEC.',
            'form_station.in' => 'Anda hanya dapat mengelola LGT di station Anda sendiri.',
            'reason.required' => 'Alasan wajib diisi bila ada pekerjaan yang dibatalkan.',
        ], ['form_date' => 'tanggal', 'form_station' => 'station', 'aircraft_registration' => 'registrasi pesawat', 'sta_time' => 'jam STA', 'std_time' => 'jam STD']);

        $aoc = app(AocResolver::class)->resolve($this->aircraft_registration)?->name;
        $data = [
            'work_date' => $this->form_date, 'station' => $this->form_station, 'aircraft_registration' => $this->aircraft_registration,
            'aoc' => $aoc, 'sta_time' => $this->sta_time ?: null, 'std_time' => $this->std_time ?: null,
            'cbm_action' => $this->cbm_action ?: null, 'cbm_mp' => $this->cbm_mp ?: null, 'cbm_status' => $this->cbm_action ? $this->cbm_status : null,
            'aiec_action' => $this->aiec_action ?: null, 'aiec_mp' => $this->aiec_mp ?: null, 'aiec_status' => $this->aiec_action ? $this->aiec_status : null,
            'reason' => $this->reason ?: null,
        ];

        $this->recordId ? $this->scoped()->findOrFail($this->recordId)->update($data) : LgtRecord::create($data);

        $this->date = $this->form_date;
        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'LGT disimpan.']);
    }

    /** One-click Closed for one team of one job. */
    public function close_(int $id, string $team): void
    {
        abort_unless(auth()->user()->can('lgt.manage'), 403);
        abort_unless(in_array($team, ['cbm', 'aiec'], true), 422);
        $r = $this->scoped()->findOrFail($id);
        abort_unless($r->{$team.'_action'}, 422);

        $r->update([$team.'_status' => 'CLOSED']);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('lgt.manage'), 403);
        $this->scoped()->findOrFail($id)->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Baris LGT dihapus.']);
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['recordId', 'form_date', 'form_station', 'aircraft_registration', 'sta_time', 'std_time', 'cbm_action', 'cbm_mp', 'aiec_action', 'aiec_mp', 'reason']);
        $this->cbm_status = 'OPEN';
        $this->aiec_status = 'OPEN';
        $this->resetErrorBag();
    }

    /** Ground time in minutes between STA and STD, past midnight when STD is earlier. */
    public static function groundMinutes(?string $sta, ?string $std): ?int
    {
        if (! $sta || ! $std) {
            return null;
        }
        [$sh, $sm] = array_map('intval', explode(':', $sta));
        [$dh, $dm] = array_map('intval', explode(':', $std));
        $d = ($dh * 60 + $dm) - ($sh * 60 + $sm);

        return $d < 0 ? $d + 24 * 60 : $d;
    }

    public function render()
    {
        $base = $this->scoped()->whereDate('work_date', $this->date)
            ->when($this->station !== '' && $this->seesAll(), fn ($q) => $q->where('station', strtoupper($this->station)));

        $rows = (clone $base)
            ->when($this->statusFilter !== '', fn ($q) => $q->where(fn ($w) => $w->where('cbm_status', $this->statusFilter)->orWhere('aiec_status', $this->statusFilter)))
            ->orderBy('station')->orderBy('sta_time')->orderBy('aircraft_registration')->orderBy('id')->get();

        $all = (clone $base)->get();
        $pct = fn ($f) => ($t = $all->whereNotNull($f)->count()) ? round($all->where($f, 'CLOSED')->count() / $t * 100, 1) : null;
        $summary = [
            'aircraft' => $all->unique(fn ($r) => $r->station.$r->aircraft_registration.$r->sta_time)->count(),
            'tasks' => $all->count(),
            'cbm' => $pct('cbm_status'), 'aiec' => $pct('aiec_status'),
            'open' => $all->filter(fn ($r) => $r->cbm_status === 'OPEN' || $r->aiec_status === 'OPEN')->count(),
            'cancel' => $all->filter(fn ($r) => $r->cbm_status === 'CANCEL' || $r->aiec_status === 'CANCEL')->count(),
        ];

        return view('livewire.modules.lgt.index', [
            'rows' => $rows,
            'summary' => $summary,
            'aircraft' => Aircraft::orderBy('registration')->pluck('registration'),
            'stations' => RosterEntry::query()->distinct()->orderBy('station')->pluck('station')->merge(LgtRecord::query()->distinct()->pluck('station'))->unique()->sort()->values(),
            'seesAll' => $this->seesAll(),
            'ownStation' => $this->ownStation(),
            'canManage' => auth()->user()->can('lgt.manage'),
            'statuses' => self::STATUSES,
        ])->layout('components.layouts.app', ['title' => 'Long Ground Time']);
    }
}
