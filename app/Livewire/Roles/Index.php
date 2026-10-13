<?php

namespace App\Livewire\Roles;

use App\Helpers\RoleHelper;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal Create / Edit Role
    public bool $isRoleModalOpen = false;

    public bool $isEditRoleMode = false;

    public ?int $editingRoleId = null;

    public string $roleName = '';

    // Modal Permission Matrix
    public bool $isMatrixModalOpen = false;

    public ?int $matrixRoleId = null;

    public string $matrixRoleName = '';

    public string $permissionSearch = '';

    /** @var array<string, bool> */
    public array $selectedPermissions = [];

    protected function rules(): array
    {
        return [
            'roleName' => ['required', 'string', 'min:2', 'max:50', 'unique:roles,name,'.($this->editingRoleId ?? 'NULL').',id'],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['roleName' => 'nama role'];
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->can('security.roles.view'), 403, 'Hanya pengguna dengan izin pengelolaan peran yang dapat mengelola hak akses peran.');
    }

    public function mount(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function hydrate(): void
    {
        $this->authorizeSuperAdmin();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function createRole(): void
    {
        $this->resetValidation();
        $this->editingRoleId = null;
        $this->roleName = '';
        $this->isEditRoleMode = false;
        $this->isRoleModalOpen = true;
    }

    public function editRole(int $id): void
    {
        $this->resetValidation();
        $role = Role::findOrFail($id);

        if ($role->name === RoleHelper::SUPER_ADMIN) {
            session()->flash('error', 'Nama role Super Admin tidak dapat diubah.');

            return;
        }

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->isEditRoleMode = true;
        $this->isRoleModalOpen = true;
    }

    public function saveRole(): void
    {
        $this->validate();

        if ($this->isEditRoleMode && $this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $oldName = $role->name;
            $role->update(['name' => trim($this->roleName)]);
            session()->flash('message', "Role '{$oldName}' berhasil diubah menjadi '{$role->name}'.");
        } else {
            $role = Role::create([
                'name' => trim($this->roleName),
                'guard_name' => 'web',
            ]);
            session()->flash('message', "Role '{$role->name}' berhasil dibuat. Silakan atur hak aksesnya.");
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->isRoleModalOpen = false;
        $this->reset(['roleName', 'editingRoleId', 'isEditRoleMode']);
    }

    public function deleteRole(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->name === RoleHelper::SUPER_ADMIN) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Role Super Admin dilindungi dan tidak dapat dihapus.']);
            session()->flash('error', 'Role Super Admin dilindungi dan tidak dapat dihapus.');

            return;
        }

        if ($role->users_count > 0) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => "Role '{$role->name}' masih digunakan oleh {$role->users_count} pengguna. Pindahkan pengguna terlebih dahulu."]);
            session()->flash('error', "Role '{$role->name}' masih digunakan oleh {$role->users_count} pengguna. Pindahkan pengguna terlebih dahulu.");

            return;
        }

        $name = $role->name;
        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->dispatch('notify', ['icon' => 'success', 'message' => "Role '{$name}' berhasil dihapus."]);
        session()->flash('success', "Role '{$name}' berhasil dihapus.");
    }

    public function openPermissionsMatrix(int $roleId): void
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->matrixRoleId = $role->id;
        $this->matrixRoleName = $role->name;
        $this->permissionSearch = '';

        // Populate array of active permissions with data_set
        $selected = [];
        foreach ($role->permissions as $perm) {
            data_set($selected, $perm->name, true);
        }
        $this->selectedPermissions = $selected;

        $this->isMatrixModalOpen = true;
    }

    public function toggleAll(bool $enable): void
    {
        foreach ($this->allPermissionGroups() as $group) {
            foreach (array_keys($group['items']) as $permName) {
                data_set($this->selectedPermissions, $permName, $enable);
            }
        }
    }

    public function toggleCategory(string $categoryKey, bool $enable): void
    {
        $groups = $this->allPermissionGroups();
        if (! isset($groups[$categoryKey])) {
            return;
        }

        foreach (array_keys($groups[$categoryKey]['items']) as $permName) {
            data_set($this->selectedPermissions, $permName, $enable);
        }
    }

    public function savePermissions(): void
    {
        if (! $this->matrixRoleId) {
            return;
        }

        $role = Role::findOrFail($this->matrixRoleId);
        $activePerms = $this->flattenPermissions($this->selectedPermissions);

        // Sync permissions
        $role->syncPermissions($activePerms);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        session()->flash('message', "Hak akses untuk role '{$role->name}' (".count($activePerms).' izin) berhasil diperbarui.');
        $this->isMatrixModalOpen = false;
        $this->reset(['matrixRoleId', 'matrixRoleName', 'selectedPermissions', 'permissionSearch']);
    }

    private function flattenPermissions(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenPermissions($value, $fullKey));
            } elseif (! empty($value)) {
                $result[] = $fullKey;
            }
        }

        return $result;
    }

    public function closeModals(): void
    {
        $this->isRoleModalOpen = false;
        $this->isMatrixModalOpen = false;
        $this->reset(['roleName', 'editingRoleId', 'isEditRoleMode', 'matrixRoleId', 'matrixRoleName', 'selectedPermissions', 'permissionSearch']);
        $this->resetValidation();
    }

    public function permissionGroups(): array
    {
        $all = $this->allPermissionGroups();
        $query = trim(strtolower($this->permissionSearch));

        if ($query === '') {
            return $all;
        }

        $filtered = [];
        foreach ($all as $key => $group) {
            $matchingItems = array_filter(
                $group['items'],
                fn ($label, $permKey) => str_contains(strtolower($label), $query) || str_contains(strtolower($permKey), $query),
                ARRAY_FILTER_USE_BOTH
            );

            if (! empty($matchingItems)) {
                $group['items'] = $matchingItems;
                $filtered[$key] = $group;
            }
        }

        return $filtered;
    }

    /**
     * Complete permission groups organized by Department / Division.
     *
     * @return array<string, array{title: string, desc: string, items: array<string, string>}>
     */
    public function allPermissionGroups(): array
    {
        return [
            'cabin' => [
                'title' => 'Divisi Cabin Maintenance (CBM)',
                'desc' => 'DJA, log teknis kabin, tracking stretcher, daily report, dan pengawasan NSRDI.',
                'items' => [
                    'menu.production' => 'Akses Induk Modul Cabin Maintenance',
                    'menu.cabin.dja' => 'DJA (Daily Job Assignment)',
                    'menu.cabin.cml' => 'CML Logs (Cabin Maintenance Log)',
                    'menu.cabin.nsrdi' => 'NSRDI Logs (Non-Routine Defects)',
                    'menu.cabin.dmi' => 'DMI Logs (Deferred Maintenance Item)',
                    'menu.cabin.wo' => 'WO Logs (Work Order)',
                    'menu.cabin.stretcher' => 'Stretcher Tracking (Pemasangan & Pelepasan)',
                    'menu.cabin.dbi' => 'Daily Beautification (DBI)',
                    'menu.cabin.daily_report' => 'Daily Report Cabin Maintenance',
                    'lgt.view' => 'Long Ground Time (LGT) Monitoring',
                    'lgt.plan' => 'Buat & Edit Draft Rencana LGT',
                    'lgt.manage' => 'Update Penutupan Pekerjaan LGT',
                    'leader.import' => 'Laporan Leader & Closing DJA',
                    'menu.nsrdi.overdue' => 'NSRDI Overdue Monitoring',
                    'menu.nsrdi.no_spare' => 'NSRDI No Spare',
                    'menu.nsrdi.pivot' => 'Pivot Analisis per AOC',
                ],
            ],
            'painting' => [
                'title' => 'Divisi Aircraft Painting',
                'desc' => 'Pekerjaan pengecatan eksterior/interior pesawat, NSRDI khusus painting & daily report.',
                'items' => [
                    'menu.painting' => 'Akses Induk Modul Aircraft Painting',
                    'menu.painting.nsrdi' => 'NSRDI Logs Painting',
                    'menu.painting.daily_report' => 'Daily Report Painting',
                ],
            ],
            'cleaning' => [
                'title' => 'Divisi Aircraft Cleaning (AIEC)',
                'desc' => 'Pembersihan interior, eksterior, 7 tipe cleaning log, sinkronisasi dan laporan.',
                'items' => [
                    'menu.cleaning' => 'Akses Induk Modul Aircraft Cleaning',
                    'menu.cleaning.hub' => 'Cleaning Logs (7 Jenis: General, Interior, Exterior, GCI, GCE, LGT, Transit)',
                    'menu.cleaning.daily_report' => 'Daily Report Aircraft Cleaning',
                    'menu.cleaning.sync' => 'Cleaning Data Sync',
                ],
            ],
            'inventory' => [
                'title' => 'Divisi Supporting & Inventory (IMS & Asset)',
                'desc' => 'Manajemen stok gudang suku cadang, permintaan, persetujuan, repair, serta inventaris aset.',
                'items' => [
                    'menu.inventory' => 'Akses Induk Inventory Management (IMS)',
                    'ims.catalog.view' => 'Lihat Katalog Barang & Ketersediaan Stok',
                    'ims.request.create' => 'Ajukan Permintaan Pengambilan Barang',
                    'ims.request.view_own' => 'Lihat Riwayat Permintaan Sendiri',
                    'ims.request.view_all' => 'Lihat Semua Permintaan Barang',
                    'ims.approval.view' => 'Buka Menu Antrean Persetujuan Barang',
                    'ims.approval.act' => 'Beri Persetujuan / Tolak Permintaan (Approval)',
                    'ims.stock.handover' => 'Serah Terima Fisik Barang Gudang (Handover)',
                    'ims.stock.in' => 'Penerimaan Barang Masuk (Receive)',
                    'ims.stock.adjust' => 'Stock Opname & Penyesuaian Saldo',
                    'ims.stock.transfer' => 'Transfer Stok Antar Lokasi / Rak',
                    'ims.repair.request' => 'Buat Tiket Barang Rusak Masuk Repair',
                    'ims.repair.manage' => 'Proses & Update Status Barang Repair',
                    'ims.report.view' => 'Lihat Laporan Mutasi & Stok Gudang',
                    'ims.report.export' => 'Export Laporan Gudang (Excel/PDF)',
                    'ims.master.manage' => 'Kelola Master Barang, Kategori, & Rak IMS',
                    'asset.view' => 'Lihat Daftar Data Asset Perusahaan',
                    'asset.manage' => 'Tambah, Edit & Hapus Data Asset',
                    'asset.assign' => 'Peminjaman & Pengembalian Asset',
                    'menu.tools_equipment' => 'Tools & Equipment Operasional',
                    'compliance.view' => 'Briefing, Attlist & 5R',
                ],
            ],
            'ict' => [
                'title' => 'Divisi ICT & IFE',
                'desc' => 'Pencatatan dan tindak lanjut temuan Passenger Information (PI) dan status perbaikan.',
                'items' => [
                    'menu.ict' => 'Akses Induk Modul Finding ICT',
                    'menu.ict.pi' => 'Findings ICT PI (Passenger Information)',
                    'menu.ict.tbd' => 'Findings ICT (TBD)',
                ],
            ],
            'capacity' => [
                'title' => 'Capacity & Planning',
                'desc' => 'Monitoring kapasitas stasiun, target, pergerakan armada (AC Movement) & rotasi pesawat.',
                'items' => [
                    'menu.capacity' => 'Akses Induk Capacity Management',
                    'menu.capacity.index' => 'Capacity Stasiun & Target',
                    'menu.capacity.rotation' => 'Aircraft Rotation (Rotasi Jadwal Pesawat)',
                    'menu.capacity.movement' => 'AC Movement (Pergerakan Armada Pesawat)',
                ],
            ],
            'people' => [
                'title' => 'Human Capital & Development (HR / GA)',
                'desc' => 'Database profil karyawan, rekap presensi, tracking PAS bandara, dan training berkala.',
                'items' => [
                    'hr.view' => 'Lihat Database Karyawan (Lingkup Divisi)',
                    'hr.view_all' => 'Lihat Database Karyawan (Semua Divisi)',
                    'hr.manage' => 'Tambah, Edit & Hapus Data Karyawan',
                    'attendance.view' => 'Lihat Rekap Presensi & Disiplin',
                    'attendance.manage' => 'Impor & Kelola Data Presensi',
                    'registry.pas.view' => 'Lihat Data PAS Bandara',
                    'registry.pas.manage' => 'Kelola Data PAS Bandara',
                    'registry.training.view' => 'Lihat Data Training & Sertifikasi',
                    'registry.training.manage' => 'Kelola Data Training Karyawan',
                    'registry.assess_internal.view' => 'Lihat Assessment Internal',
                    'registry.assess_internal.manage' => 'Kelola Assessment Internal',
                    'registry.assess_hc.view' => 'Lihat Assessment Human Capital',
                    'registry.assess_hc.manage' => 'Kelola Assessment Human Capital',
                    'registry.form.view' => 'Lihat Form Kerja Karyawan',
                    'registry.form.manage' => 'Kelola Form Kerja Karyawan',
                ],
            ],
            'operational' => [
                'title' => 'Operasional & Dispatch',
                'desc' => 'Antrean verifikasi hasil pekerjaan teknisi dan rekap giliran kerja shift.',
                'items' => [
                    'menu.operational.verification' => 'Verification Queue Hasil Pekerjaan',
                    'menu.operational.shift_recap' => 'Shift Recap Operasional',
                ],
            ],
            'analytics' => [
                'title' => 'Analitik & Reporting',
                'desc' => 'Statistik performa armada, ringkasan eksekutif, akurasi dokumen, dan KPI harian.',
                'items' => [
                    'menu.analytics' => 'Akses Induk Menu Analitik',
                    'reports.summary.view' => 'Summary Performa Operasional',
                    'reports.kpi.view' => 'KPI & Man Hours Karyawan',
                    'kpi.view' => 'Kartu KPI Dashboard & Performa Stasiun',
                    'reports.document_accuracy.view' => 'KPI Document Accuracy',
                    'reports.lgt.view' => 'LGT Monitoring Report',
                    'reports.manpower.view' => 'Manpower Harian Karyawan',
                    'reports.executive.view' => 'Executive Reports (Ringkasan Manajemen)',
                ],
            ],
            'master_data' => [
                'title' => 'Data Master & Sistem',
                'desc' => 'Master referensi bandara, registrasi pesawat, jabatan, divisi, dan sinkronisasi sumber data.',
                'items' => [
                    'master.airports.view' => 'Master Bandara / Station',
                    'master.aircraft.view' => 'Master Registrasi Pesawat',
                    'master.categories.view' => 'Master Kategori Pekerjaan',
                    'master.divisions.view' => 'Master Divisi Perusahaan',
                    'master.positions.view' => 'Master Jabatan / Posisi',
                    'master.capacity.view' => 'Master Kapasitas Stasiun & Target',
                    'master.system.view' => 'Master Data Sistem (Kustom)',
                    'sources.view' => 'Lihat Status Sumber Data',
                    'sources.sync' => 'Jalankan Sinkronisasi Sumber Data Manual',
                ],
            ],
            'security' => [
                'title' => 'Sistem & Keamanan',
                'desc' => 'Manajemen akun pengguna, konfigurasi matriks peran (roles), dan audit trail.',
                'items' => [
                    'security.users.view' => 'User Management (Kelola Akun)',
                    'security.roles.view' => 'Peran & Hak Akses (Role Matrix)',
                    'security.audit.view' => 'Audit Trail (Jejak Log Aktivitas)',
                ],
            ],
            'documents' => [
                'title' => 'Pusat Dokumen & Profil',
                'desc' => 'Dokumentasi SOP, CMPM, regulasi teknis, dan pengaturan akun.',
                'items' => [
                    'documents.view' => 'Pusat Dokumen (CMPM, SOP & Regulasi)',
                    'documents.master.view' => 'Master Data Kategori & File Dokumen',
                    'profile.view' => 'Pengaturan Profil Pengguna',
                ],
            ],
        ];
    }

    public function render()
    {
        $roles = Role::withCount(['users', 'permissions'])
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.roles.index', [
            'roles' => $roles,
            'groups' => $this->permissionGroups(),
            'totalRoles' => Role::count(),
            'totalPermissions' => Permission::count(),
        ])->layout('components.layouts.app', ['title' => 'Peran & Hak Akses']);
    }
}
