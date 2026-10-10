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
        abort_unless(auth()->user()?->hasRole(RoleHelper::SUPER_ADMIN), 403, 'Hanya Super Admin yang dapat mengelola hak akses peran.');
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

        // Populate array of active permissions with data_set
        $selected = [];
        foreach ($role->permissions as $perm) {
            data_set($selected, $perm->name, true);
        }
        $this->selectedPermissions = $selected;

        $this->isMatrixModalOpen = true;
    }

    public function toggleCategory(string $categoryKey, bool $enable): void
    {
        $groups = $this->permissionGroups();
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
        $this->reset(['matrixRoleId', 'matrixRoleName', 'selectedPermissions']);
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
        $this->reset(['roleName', 'editingRoleId', 'isEditRoleMode', 'matrixRoleId', 'matrixRoleName', 'selectedPermissions']);
        $this->resetValidation();
    }

    /**
     * Group permissions with human-readable titles and descriptions.
     *
     * @return array<string, array{title: string, desc: string, items: array<string, string>}>
     */
    public function permissionGroups(): array
    {
        return [
            'menus' => [
                'title' => 'Menu Utama & Sidebar',
                'desc' => 'Menentukan blok navigasi mana saja yang tampil di sidebar pengguna.',
                'items' => [
                    'menu.production' => 'Cabin Maintenance (DJA, WO, DMI, CML, Daily Report)',
                    'menu.painting' => 'Aircraft Painting (NSRDI Logs)',
                    'menu.cleaning' => 'Aircraft Cleaning (Pembersihan Pesawat)',
                    'menu.nsrdi' => 'NSRDI Management (Overdue, No Spare, Pivot)',
                    'menu.ict' => 'Finding ICT (Pencatatan Temuan ICT)',
                    'menu.capacity' => 'Capacity, Aircraft Rotation & Movement',
                    'menu.inventory' => 'Inventory & Gudang (IMS)',
                    'menu.analytics' => 'Analitik & KPI Dashboard',
                ],
            ],
            'ims' => [
                'title' => 'Gudang & Inventory (IMS)',
                'desc' => 'Alur permintaan barang, persetujuan, serah terima, dan stok opname.',
                'items' => [
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
                ],
            ],
            'production' => [
                'title' => 'Produksi & Operasional Pesawat',
                'desc' => 'Laporan leader, draft LGT, dan pencatatan pekerjaan pesawat.',
                'items' => [
                    'leader.import' => 'Impor Laporan Leader & Closing DJA',
                    'lgt.view' => 'Lihat Monitoring Long Ground Time (LGT)',
                    'lgt.plan' => 'Buat Draft Rencana Pesawat LGT',
                    'lgt.manage' => 'Isi Pekerjaan & Penutupan LGT',
                ],
            ],
            'people' => [
                'title' => 'Human Capital & Disiplin',
                'desc' => 'Database karyawan, presensi harian, dan briefing 5R.',
                'items' => [
                    'hr.view' => 'Lihat Database Karyawan (Lingkup Divisi)',
                    'hr.view_all' => 'Lihat Database Karyawan (Semua Divisi)',
                    'hr.manage' => 'Tambah, Edit & Hapus Karyawan',
                    'attendance.view' => 'Lihat Rekap Presensi & Disiplin',
                    'attendance.manage' => 'Impor & Kelola Data Presensi',
                    'compliance.view' => 'Lihat Briefing, Attlist & 5R',
                ],
            ],
            'assets_docs' => [
                'title' => 'Asset, Dokumen & Kepatuhan',
                'desc' => 'Inventaris modal, pusat regulasi SOP, dan sinkronisasi data.',
                'items' => [
                    'asset.view' => 'Lihat Daftar Data Asset',
                    'asset.manage' => 'Tambah & Edit Data Asset',
                    'asset.assign' => 'Peminjaman & Pengembalian Asset',
                    'sources.view' => 'Lihat Status Sumber Data',
                    'sources.sync' => 'Jalankan Sinkronisasi Sumber Data',
                    'kpi.view' => 'Lihat Kartu KPI & Performa Stasiun',
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
