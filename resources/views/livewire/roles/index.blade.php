<div>
    <x-master.page-header 
        title="Peran &amp; Hak Akses" 
        subtitle="Kelola daftar peran (roles) dan konfigurasikan matriks hak akses fitur secara langsung tanpa perlu ubah kode." 
        accent="purple" 
        eyebrow="Sistem &amp; Keamanan" 
        create-label="Tambah Peran" 
    />

    <x-flash />

    {{-- Stats Overview --}}
    <div class="mod-stats">
        <div class="mod-stat">
            <div class="mod-stat-label">Total Peran (Roles)</div>
            <div class="mod-stat-value" style="color:var(--cbm-purple,#a855f7);">{{ $totalRoles }}</div>
            <div class="mod-stat-sub">aktif di sistem</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-label">Total Izin Fitur</div>
            <div class="mod-stat-value" style="color:var(--cbm-blue);">{{ $totalPermissions }}</div>
            <div class="mod-stat-sub">titik kontrol otorisasi</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-label">Mode Otorisasi</div>
            <div class="mod-stat-value" style="color:#10b981;font-size:1.15rem;font-weight:800;">Role &amp; Permission</div>
            <div class="mod-stat-sub">Spatie dynamic matrix</div>
        </div>
    </div>

    {{-- Roles Card --}}
    <div class="mod-card mod-card-accent-purple" style="margin-top:1.25rem;">
        <div class="mod-toolbar">
            <div class="mod-filters">
                <div class="mod-field mod-field-search">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input wire:model.live.debounce.300ms="search" class="mod-search-input" type="search" placeholder="Cari nama peran / role..." aria-label="Cari peran">
                </div>
                @if($search)
                    <button type="button" wire:click="$set('search', '')" class="mod-btn-outline mod-btn-sm">Reset</button>
                @endif
                <span wire:loading wire:target="search" class="cbm-spinner" aria-label="Memuat"></span>
            </div>
            <div class="mod-meta">
                <span class="mod-record-count">{{ number_format($roles->total()) }} peran terdaftar</span>
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        <th style="width:30%;">NAMA PERAN (ROLE)</th>
                        <th style="width:20%;">PENGGUNA AKTIF</th>
                        <th style="width:25%;">HAK AKSES FITUR</th>
                        <th style="text-align:right;width:25%;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr wire:key="role-{{ $role->id }}">
                            <td>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <div style="width:2.25rem;height:2.25rem;border-radius:.625rem;background:linear-gradient(135deg,rgba(168,85,247,.2),rgba(59,130,246,.2));border:1px solid rgba(168,85,247,.3);color:var(--cbm-text);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0;">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:1.1rem;height:1.1rem;color:var(--cbm-purple,#a855f7);">
                                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="mod-aircraft-name" style="font-weight:800;font-size:.95rem;">
                                            {{ $role->name }}
                                            @if($role->name === \App\Helpers\RoleHelper::SUPER_ADMIN)
                                                <span class="mod-badge-closed" style="margin-left:.35rem;font-size:.7rem;">Super User</span>
                                            @endif
                                        </div>
                                        <div class="mod-aircraft-sub">Guard: <code>{{ $role->guard_name }}</code></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="mod-badge-progress" style="background:rgba(59,130,246,.1);color:var(--cbm-blue);border-color:rgba(59,130,246,.25);font-weight:700;">
                                    {{ $role->users_count }} pengguna
                                </span>
                            </td>
                            <td>
                                @if($role->name === \App\Helpers\RoleHelper::SUPER_ADMIN)
                                    <span class="mod-badge-closed" style="background:rgba(16,185,129,.1);color:#10b981;border-color:rgba(16,185,129,.3);">Semua Fitur (Bypass)</span>
                                @else
                                    <span class="mod-badge-open" style="font-weight:700;">
                                        {{ $role->permissions_count }} izin aktif
                                    </span>
                                @endif
                            </td>
                            <td style="text-align:right;white-space:nowrap;">
                                <div style="display:flex;justify-content:flex-end;gap:.5rem;align-items:center;">
                                    @if($role->name !== \App\Helpers\RoleHelper::SUPER_ADMIN)
                                        <button type="button" wire:click="openPermissionsMatrix({{ $role->id }})" class="mod-action-btn" style="background:rgba(168,85,247,.12);color:var(--cbm-purple,#a855f7);border-color:rgba(168,85,247,.35);font-weight:700;">
                                            Atur Hak Akses
                                        </button>
                                        <button type="button" wire:click="editRole({{ $role->id }})" class="mod-action-btn">
                                            Edit
                                        </button>
                                        @if($role->users_count === 0)
                                            <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="Hapus role '{{ $role->name }}'?" class="mod-action-btn" style="color:#ef4444;border-color:rgba(239,68,68,.35);">
                                                Hapus
                                            </button>
                                        @endif
                                    @else
                                        <span class="mod-aircraft-sub" style="font-style:italic;">Dilindungi sistem</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center;padding:2.5rem 1rem;color:var(--cbm-text-muted);">
                                Tidak ada peran yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($roles->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid var(--cbm-card-border);">
                {{ $roles->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Tambah / Edit Role --}}
    <x-master.modal wire:model="isRoleModalOpen" :title="$isEditRoleMode ? 'Edit Nama Peran' : 'Tambah Peran (Role) Baru'" save-label="Simpan Peran" save-method="saveRole">
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div class="cbm-form-group" style="margin-bottom:0;">
                <label class="cbm-form-label" for="role-name">Nama Peran / Role *</label>
                <input id="role-name" wire:model="roleName" type="text" class="cbm-form-input" placeholder="Contoh: QC Inspector, Admin Dispatch, Staff Painting...">
                <span class="mod-hint-inline">Nama unik jabatan atau peran kerja di operasional cabin.</span>
                @error('roleName') <span class="mod-field-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </x-master.modal>

    {{-- Modal Matrix Checklist Hak Akses --}}
    <x-master.modal wire:model="isMatrixModalOpen" title="Matriks Hak Akses: {{ $matrixRoleName }}" save-label="Simpan Hak Akses" save-method="savePermissions" width="max-w-4xl">
        <div style="display:flex;flex-direction:column;gap:1.5rem;max-height:68vh;overflow-y:auto;padding-right:.35rem;">
            <div class="mod-hint mod-hint-ok" style="margin:0;">
                Centang izin fitur apa saja yang boleh diakses oleh pengguna dengan role <strong>{{ $matrixRoleName }}</strong>. Perubahan akan langsung aktif seketika setelah disimpan.
            </div>

            @foreach($groups as $groupKey => $group)
                <div style="background:var(--cbm-bg-subtle, rgba(255,255,255,.03));border:1px solid var(--cbm-card-border);border-radius:1rem;padding:1.15rem;display:flex;flex-direction:column;gap:.85rem;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;border-bottom:1px solid var(--cbm-card-border);padding-bottom:.75rem;">
                        <div>
                            <div style="font-weight:800;font-size:.95rem;color:var(--cbm-text);">{{ $group['title'] }}</div>
                            <div style="font-size:.8rem;color:var(--cbm-text-muted);margin-top:.15rem;">{{ $group['desc'] }}</div>
                        </div>
                        <div style="display:flex;gap:.5rem;">
                            <button type="button" wire:click="toggleCategory('{{ $groupKey }}', true)" class="mod-action-btn" style="font-size:.75rem;padding:.25rem .6rem;">
                                Centang Semua
                            </button>
                            <button type="button" wire:click="toggleCategory('{{ $groupKey }}', false)" class="mod-action-btn" style="font-size:.75rem;padding:.25rem .6rem;">
                                Batalkan Semua
                            </button>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 18rem), 1fr));gap:.65rem;">
                        @foreach($group['items'] as $permKey => $permLabel)
                            <label style="display:flex;align-items:flex-start;gap:.65rem;padding:.6rem .75rem;border-radius:.625rem;background:var(--cbm-card-bg);border:1px solid var(--cbm-card-border);cursor:pointer;transition:border-color .15s ease;" class="hover:border-purple-500">
                                <input type="checkbox" wire:model="selectedPermissions.{{ $permKey }}" style="width:1.1rem;height:1.1rem;margin-top:.15rem;accent-color:var(--cbm-purple,#a855f7);border-radius:.3rem;flex-shrink:0;">
                                <div style="min-width:0;">
                                    <div style="font-weight:700;font-size:.825rem;color:var(--cbm-text);line-height:1.25;">
                                        {{ $permLabel }}
                                    </div>
                                    <div style="font-size:.72rem;color:var(--cbm-text-muted);margin-top:.2rem;font-family:monospace;">
                                        <code>{{ $permKey }}</code>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-master.modal>
</div>
