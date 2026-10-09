<div>
    <x-master.page-header title="Manajemen Pengguna" subtitle="Kelola akun pengguna, peran, dan akses sistem." accent="blue" eyebrow="Sistem &amp; Keamanan" create-label="Tambah Pengguna" />

    <x-flash />

    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-label">Total pengguna</div><div class="mod-stat-value">{{ $totals['all'] }}</div><div class="mod-stat-sub">semua akun</div></div>
        <div class="mod-stat"><div class="mod-stat-label">Aktif</div><div class="mod-stat-value" style="color:#10b981;">{{ $totals['active'] }}</div><div class="mod-stat-sub">dapat login</div></div>
        <div class="mod-stat"><div class="mod-stat-label">Nonaktif</div><div class="mod-stat-value">{{ $totals['inactive'] }}</div><div class="mod-stat-sub">tidak dapat login</div></div>
        <div class="mod-stat"><div class="mod-stat-label">Super Admin</div><div class="mod-stat-value">{{ $totals['admins'] }}</div><div class="mod-stat-sub">akses penuh</div></div>
    </div>

    <div class="mod-card mod-card-accent-blue">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari nama, ID, email, station..." aria-label="Cari pengguna">
                </div>
                <label class="mod-field mod-field-labelled"><span>Role</span>
                    <select wire:model.live="roleFilter" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        @foreach($roles as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach
                    </select>
                </label>
                <label class="mod-field mod-field-labelled"><span>Status</span>
                    <select wire:model.live="statusFilter" class="mod-search-input mod-input-plain">
                        <option value="">Semua</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </label>
                @if($search || $roleFilter || $statusFilter)
                    <button type="button" wire:click="clearFilters" class="mod-btn-outline mod-btn-sm">Reset filter</button>
                @endif
                <span wire:loading wire:target="search, roleFilter, statusFilter" class="cbm-spinner" aria-label="Memuat"></span>
            </div>
            <div class="mod-meta"><span class="mod-record-count">{{ number_format($users->total()) }} pengguna</span></div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th>PENGGUNA</th>
                        <th>JABATAN &amp; DIVISI</th>
                        <th>STATION</th>
                        <th>ROLE</th>
                        <th>STATUS</th>
                        <th style="text-align:right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <div style="width:2.25rem;height:2.25rem;border-radius:.625rem;background:linear-gradient(135deg,var(--cbm-blue),var(--cbm-purple,#a855f7));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.8rem;flex-shrink:0;">
                                        {{ strtoupper(mb_substr($user->name, 0, 2)) }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div class="mod-aircraft-name">{{ $user->name }}@if($user->id === auth()->id()) <span class="cbm-tab-count">Anda</span>@endif</div>
                                        <div class="mod-aircraft-sub">{{ $user->nik }} &middot; {{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $user->position->name ?? '-' }}</div>
                                <div class="mod-aircraft-sub">{{ $user->division->name ?? '-' }}</div>
                            </td>
                            <td style="font-weight:700;">{{ $user->station ?: 'Semua' }}</td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="mod-badge-progress" style="background:rgba(168,85,247,.12);color:#a855f7;border-color:rgba(168,85,247,.3);">{{ $role->name }}</span>
                                @empty
                                    <span class="mod-badge-open">Tanpa role</span>
                                @endforelse
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="mod-badge-closed"><span class="mod-badge-dot" style="background:#34d399;"></span>Aktif</span>
                                @else
                                    <span class="mod-badge-inactive">Nonaktif</span>
                                @endif
                                @if($user->is_default_password)
                                    <div class="mod-aircraft-sub" title="Belum mengganti password default">Password default</div>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="mod-action-btn">Edit</button>
                                    @if($user->id !== auth()->id())
                                        <button type="button" wire:click="resetPassword({{ $user->id }})" wire:confirm="Reset password {{ $user->name }} ke default ({{ $defaultPassword }})?" class="mod-action-btn" title="Reset password ke default">Reset PW</button>
                                        <button type="button" wire:click="delete({{ $user->id }})" wire:confirm="Hapus pengguna {{ $user->name }}?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">Hapus</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="mod-empty">
                                <div class="mod-empty-title">{{ ($search || $roleFilter || $statusFilter) ? 'Tidak ada pengguna yang cocok' : 'Belum ada pengguna' }}</div>
                                <div class="mod-empty-sub">{{ ($search || $roleFilter || $statusFilter) ? 'Ubah kata kunci atau reset filter.' : 'Klik "Tambah Pengguna" untuk mulai.' }}</div>
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="mod-pagination">{{ $users->links('pagination::tailwind') }}</div>
        @endif
    </div>

    <x-master.modal :show="$isOpen" :title="$isEditMode ? 'Edit Pengguna' : 'Tambah Pengguna Baru'" :submit="$isEditMode ? 'update' : 'store'" max-width="42rem">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:1rem;">
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-nik">ID *</label>
                <input id="u-nik" type="text" wire:model="nik" class="cbm-form-input" maxlength="20" autocomplete="off">
                @error('nik') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-name">Nama lengkap *</label>
                <input id="u-name" type="text" wire:model="name" class="cbm-form-input" maxlength="100">
                @error('name') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group" style="grid-column:1/-1;">
                <label class="cbm-form-label" for="u-email">Email *</label>
                <input id="u-email" type="email" wire:model="email" class="cbm-form-input" autocomplete="off">
                @error('email') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-pos">Jabatan *</label>
                <div class="cbm-select-wrap">
                    <select id="u-pos" wire:model="position_id" class="cbm-form-select">
                        <option value="">-- Pilih jabatan --</option>
                        @foreach($positions as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                @error('position_id') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-div">Divisi</label>
                <div class="cbm-select-wrap">
                    <select id="u-div" wire:model="division_id" class="cbm-form-select">
                        <option value="">-- Tanpa divisi --</option>
                        @foreach($divisions as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                @error('division_id') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-sta">Station / Bandara</label>
                <div class="cbm-select-wrap">
                    <select id="u-sta" wire:model="station" class="cbm-form-select">
                        <option value="">Semua station</option>
                        @foreach($stations as $s)<option value="{{ $s->kode }}">{{ $s->kode }} - {{ $s->nama }}</option>@endforeach
                    </select>
                </div>
                <span class="mod-hint-inline">Station membatasi data yang terlihat oleh pengguna di dashboard dan modul.</span>
                @error('station') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group">
                <label class="cbm-form-label" for="u-role">Role / hak akses *</label>
                <div class="cbm-select-wrap">
                    <select id="u-role" wire:model="role" class="cbm-form-select">
                        <option value="">-- Pilih role --</option>
                        @foreach($roles as $r)<option value="{{ $r->name }}">{{ $r->name }}</option>@endforeach
                    </select>
                </div>
                @error('role') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="cbm-form-group" style="grid-column:1/-1;margin-bottom:0;">
                <label class="cbm-form-label" for="u-status">Status akun *</label>
                <div class="cbm-select-wrap">
                    <select id="u-status" wire:model="status" class="cbm-form-select">
                        <option value="active">Aktif (dapat login)</option>
                        <option value="inactive">Nonaktif (tidak dapat login)</option>
                    </select>
                </div>
                @error('status') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>

        @unless($isEditMode)
            <div class="mod-hint mod-hint-ok" style="margin:1rem 0 0;">
                Password awal akun baru adalah <code>{{ $defaultPassword }}</code>. Pengguna wajib menggantinya saat login pertama.
            </div>
        @endunless
    </x-master.modal>
</div>
