<div class="kd" wire:loading.class="kd-busy">
    <x-kd-styles />
    <style>
        .lg-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(13rem,1fr)); gap:.75rem 1rem; }
        .lg-job { font-size:.82rem; white-space:normal; min-width:11rem; text-align:left; }
        .lg-job .who { color:var(--cbm-text-muted); font-size:.75rem; }
        .lg-team { border:1px solid var(--cbm-divider); border-radius:.8rem; padding:.75rem; margin-top:.75rem; }
        .lg-team h4 { margin:0 0 .5rem; font-size:.8rem; letter-spacing:.06em; text-transform:uppercase; color:var(--cbm-text-muted); }
        .lg-table td { vertical-align:top; }
        @media (max-width:640px) { .lg-hide-sm { display:none; } }
    </style>

    @php
        $tones = ['green' => '#34d399', 'yellow' => '#f59e0b', 'red' => '#ef4444', 'none' => '#94a3b8', 'blue' => '#60a5fa'];
        $bgs = ['green' => 'rgba(52,211,153,.15)', 'yellow' => 'rgba(245,158,11,.17)', 'red' => 'rgba(239,68,68,.16)', 'none' => 'rgba(148,163,184,.13)', 'blue' => 'rgba(96,165,250,.16)'];
        $st = fn ($v) => \App\Services\Kpi\AchievementStatus::for($v, 100);
        $tone = ['CLOSED' => 'green', 'OPEN' => 'yellow', 'CANCEL' => 'red'];
        $badge = fn ($s) => $s ? '<span class="kd-chip" style="min-width:0;color:'.$tones[$tone[$s]].';background:'.$bgs[$tone[$s]].';">'.$s.'</span>' : '<span class="kd-sub">–</span>';
        $hm = function ($min) { return $min === null ? '–' : intdiv($min, 60).'j '.str_pad($min % 60, 2, '0', STR_PAD_LEFT).'m'; };
    @endphp

    <div class="kd-skel" aria-hidden="true"></div>

    <x-master.page-header title="Long Ground Time" subtitle="Daftar pesawat dengan ground time panjang dan pekerjaan yang direncanakan untuk tim CBM dan AIEC. Hasilnya (closed / batal) masuk ke LGT Monitoring." accent="blue" eyebrow="Production" :create-label="$canManage ? 'Tambah LGT' : null" />

    <x-flash />

    <div class="kd-bar">
        <div class="kd-group">
            <div class="kd-nav">
                <button type="button" wire:click="move(-1)" aria-label="Hari sebelumnya">‹</button>
                <span class="kd-label">{{ \Carbon\Carbon::parse($date)->translatedFormat('D, d M Y') }}</span>
                <button type="button" wire:click="move(1)" aria-label="Hari berikutnya">›</button>
                <button type="button" wire:click="today" style="width:auto;padding:0 .7rem;font-size:.8rem;font-weight:700;">Kini</button>
            </div>
            <input type="date" wire:model.live="date" aria-label="Tanggal">
        </div>
        <div class="kd-group">
            @if($seesAll)
                <select wire:model.live="station" aria-label="Station">
                    <option value="">Semua station</option>
                    @foreach($stations as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </select>
            @else
                <span class="kd-sub">Station {{ $ownStation ?? 'semua' }}</span>
            @endif
            <select wire:model.live="statusFilter" aria-label="Status">
                <option value="">Semua status</option>
                @foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="kd-cards">
        <div class="kd-card" style="--tone: {{ $tones['blue'] }}"><div class="t">Pesawat LGT</div><div class="v">{{ $summary['aircraft'] }}</div><div class="s">{{ $summary['tasks'] }} pekerjaan</div></div>
        <div class="kd-card" style="--tone: {{ $tones[$st($summary['cbm'])] }}"><div class="t">CBM terlaksana</div><div class="v">{{ $summary['cbm'] !== null ? $summary['cbm'].'%' : '–' }}</div></div>
        <div class="kd-card" style="--tone: {{ $tones[$st($summary['aiec'])] }}"><div class="t">AIEC terlaksana</div><div class="v">{{ $summary['aiec'] !== null ? $summary['aiec'].'%' : '–' }}</div></div>
        <div class="kd-card" style="--tone: {{ $summary['open'] ? $tones['yellow'] : $tones['none'] }}"><div class="t">Masih open</div><div class="v">{{ $summary['open'] }}</div><div class="s">{{ $summary['cancel'] }} batal</div></div>
    </div>

    <section class="kd-panel">
        <div class="kd-wrap">
            @if($rows->isEmpty())
                <div class="kd-empty">Belum ada LGT pada tanggal ini.<br><span class="kd-sub">{{ $canManage ? 'Klik "Tambah LGT" untuk mendata pesawat dan pekerjaannya.' : 'Data diisi oleh yang menyusun rencana LGT.' }}</span></div>
            @else
                <table class="kd-table lg-table">
                    <thead>
                        <tr>
                            <th style="cursor:default;">Pesawat</th><th style="cursor:default;" class="lg-hide-sm">STA – STD</th><th style="cursor:default;">Ground time</th>
                            <th style="cursor:default;text-align:left;">CBM</th><th style="cursor:default;text-align:left;">AIEC</th><th style="cursor:default;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                            @php $gt = \App\Livewire\Modules\Lgt\Index::groundMinutes($r->sta_time, $r->std_time); @endphp
                            <tr wire:key="lg-{{ $r->id }}">
                                <td><strong>{{ $r->aircraft_registration }}</strong><div class="kd-sub">{{ $r->station }}{{ $r->aoc ? ' · '.$r->aoc : '' }}</div></td>
                                <td class="lg-hide-sm">{{ $r->sta_time ?? '–' }} – {{ $r->std_time ?? '–' }}</td>
                                <td>{{ $hm($gt) }}</td>
                                <td class="lg-job">
                                    @if($r->cbm_action){{ $r->cbm_action }} {!! $badge($r->cbm_status) !!}@if($r->cbm_mp)<div class="who">{{ $r->cbm_mp }}</div>@endif @else <span class="kd-sub">–</span> @endif
                                </td>
                                <td class="lg-job">
                                    @if($r->aiec_action){{ $r->aiec_action }} {!! $badge($r->aiec_status) !!}@if($r->aiec_mp)<div class="who">{{ $r->aiec_mp }}</div>@endif @else <span class="kd-sub">–</span> @endif
                                    @if($r->reason)<div class="who" style="color:#f59e0b;">{{ $r->reason }}</div>@endif
                                </td>
                                <td style="text-align:right;">
                                    @if($canManage)
                                        <div style="display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap;">
                                            @if($r->cbm_action && $r->cbm_status !== 'CLOSED')<button type="button" wire:click="close_({{ $r->id }}, 'cbm')" class="mod-action-btn">CBM Closed</button>@endif
                                            @if($r->aiec_action && $r->aiec_status !== 'CLOSED')<button type="button" wire:click="close_({{ $r->id }}, 'aiec')" class="mod-action-btn">AIEC Closed</button>@endif
                                            <button type="button" wire:click="create({{ $r->id }})" class="mod-action-btn" title="Pekerjaan lain di pesawat yang sama">+ Pekerjaan</button>
                                            <button type="button" wire:click="edit({{ $r->id }})" class="mod-action-btn">Edit</button>
                                            <button type="button" wire:click="delete({{ $r->id }})" wire:confirm="Hapus baris ini?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <x-master.modal :show="$isOpen" :title="$recordId ? 'Edit LGT' : 'Tambah LGT'" submit="save" max-width="42rem">
        <datalist id="lg-aircraft">@foreach($aircraft as $reg)<option value="{{ $reg }}"></option>@endforeach</datalist>
        <div class="lg-grid">
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-date">Tanggal *</label><input id="lg-date" type="date" wire:model="form_date" class="cbm-form-input">@error('form_date')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-sta">Station *</label><input id="lg-sta" type="text" wire:model="form_station" class="cbm-form-input" maxlength="10" style="text-transform:uppercase;" list="lg-stations" @disabled($ownStation)><datalist id="lg-stations">@foreach($stations as $s)<option value="{{ $s }}"></option>@endforeach</datalist>@error('form_station')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-reg">Registrasi pesawat *</label><input id="lg-reg" type="text" wire:model="aircraft_registration" class="cbm-form-input" maxlength="20" style="text-transform:uppercase;" list="lg-aircraft" autocomplete="off">@error('aircraft_registration')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-in">Jam STA (tiba)</label><input id="lg-in" type="time" wire:model="sta_time" class="cbm-form-input">@error('sta_time')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-out">Jam STD (berangkat)</label><input id="lg-out" type="time" wire:model="std_time" class="cbm-form-input">@error('std_time')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
        </div>

        <div class="lg-team">
            <h4>Tim CBM</h4>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-cbm">Pekerjaan</label><input id="lg-cbm" type="text" wire:model="cbm_action" class="cbm-form-input" maxlength="250">@error('cbm_action')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
            <div class="lg-grid">
                <div class="cbm-form-group" style="margin:0;"><label class="cbm-form-label" for="lg-cbm-mp">Pelaksana (ID / nama)</label><input id="lg-cbm-mp" type="text" wire:model="cbm_mp" class="cbm-form-input" maxlength="250"></div>
                <div class="cbm-form-group" style="margin:0;"><label class="cbm-form-label" for="lg-cbm-st">Status</label><div class="cbm-select-wrap"><select id="lg-cbm-st" wire:model="cbm_status" class="cbm-form-select">@foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div></div>
            </div>
        </div>
        <div class="lg-team">
            <h4>Tim AIEC (cleaning)</h4>
            <div class="cbm-form-group"><label class="cbm-form-label" for="lg-aiec">Pekerjaan</label><input id="lg-aiec" type="text" wire:model="aiec_action" class="cbm-form-input" maxlength="250"></div>
            <div class="lg-grid">
                <div class="cbm-form-group" style="margin:0;"><label class="cbm-form-label" for="lg-aiec-mp">Pelaksana (ID / nama)</label><input id="lg-aiec-mp" type="text" wire:model="aiec_mp" class="cbm-form-input" maxlength="250"></div>
                <div class="cbm-form-group" style="margin:0;"><label class="cbm-form-label" for="lg-aiec-st">Status</label><div class="cbm-select-wrap"><select id="lg-aiec-st" wire:model="aiec_status" class="cbm-form-select">@foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div></div>
            </div>
        </div>
        <div class="cbm-form-group" style="margin:.75rem 0 0;"><label class="cbm-form-label" for="lg-reason">Alasan (wajib bila batal)</label><input id="lg-reason" type="text" wire:model="reason" class="cbm-form-input" maxlength="250" placeholder="contoh: AC ROTATION CHANGED">@error('reason')<span class="mod-field-error">{{ $message }}</span>@enderror</div>
    </x-master.modal>
</div>
