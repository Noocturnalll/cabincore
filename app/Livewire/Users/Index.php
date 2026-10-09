<?php

namespace App\Livewire\Users;

use App\Helpers\RoleHelper;
use App\Models\Airport;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    /** Password given to new accounts and after a reset; the user must change it at first login. */
    public const DEFAULT_PASSWORD = 'batam123';

    public $search = '';

    public $roleFilter = '';

    public $statusFilter = '';

    public $user_id;

    public $nik;

    public $name;

    public $email;

    public $position_id;

    public $division_id;

    public $station;

    public $role;

    public $status = 'active';

    public $isEditMode = false;

    public $isOpen = false;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    /**
     * The sidebar only shows this page to Super Admin, but every Livewire action is a
     * public endpoint: it has to be guarded on the server too.
     */
    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasRole(RoleHelper::SUPER_ADMIN), 403, 'Hanya Super Admin yang dapat mengelola pengguna.');
    }

    public function mount(): void
    {
        $this->authorizeManage();
    }

    public function hydrate(): void
    {
        $this->authorizeManage();
    }

    private function activeSuperAdmins(?int $exceptId = null): int
    {
        return User::role(RoleHelper::SUPER_ADMIN)
            ->where('status', 'active')
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->count();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $user = User::with('roles')->findOrFail($id);
        $this->user_id = $user->id;
        $this->nik = $user->nik;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->position_id = $user->position_id;
        $this->division_id = $user->division_id;
        $this->station = $user->station;
        $this->status = $user->status;
        $this->role = $user->roles->first()?->name ?? '';

        $this->isOpen = true;
        $this->isEditMode = true;
    }

    protected function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:20', Rule::unique('users', 'nik')->ignore($this->user_id)],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($this->user_id)],
            'position_id' => ['required', 'exists:positions,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'station' => ['nullable', 'string', 'max:10', Rule::exists('airports', 'kode')],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    protected function validationAttributes(): array
    {
        return ['nik' => 'ID', 'name' => 'nama', 'position_id' => 'jabatan', 'division_id' => 'divisi', 'station' => 'station'];
    }

    private function normalise(): void
    {
        $this->nik = trim((string) $this->nik);
        $this->name = trim((string) $this->name);
        $this->email = strtolower(trim((string) $this->email));
        $this->division_id = $this->division_id ?: null;
        $this->station = $this->station ?: null;
    }

    public function store()
    {
        $this->authorizeManage();
        $this->normalise();
        $this->validate();

        $user = User::create([
            'nik' => $this->nik,
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'position_id' => $this->position_id,
            'division_id' => $this->division_id,
            'station' => $this->station,
            'status' => $this->status,
            'is_default_password' => true,
        ]);
        $user->assignRole($this->role);

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => "Pengguna {$user->name} berhasil ditambahkan."]);
    }

    public function update()
    {
        $this->authorizeManage();
        $this->normalise();
        $this->validate();

        $user = User::findOrFail($this->user_id);
        $isSelf = $user->id === auth()->id();
        $losesAdmin = $user->hasRole(RoleHelper::SUPER_ADMIN)
            && ($this->role !== RoleHelper::SUPER_ADMIN || $this->status !== 'active');

        if ($losesAdmin && $isSelf) {
            $this->addError('role', 'Anda tidak dapat menurunkan role atau menonaktifkan akun Anda sendiri.');

            return;
        }
        if ($losesAdmin && $this->activeSuperAdmins($user->id) === 0) {
            $this->addError('role', 'Harus ada minimal satu Super Admin aktif.');

            return;
        }

        $user->update([
            'nik' => $this->nik,
            'name' => $this->name,
            'email' => $this->email,
            'position_id' => $this->position_id,
            'division_id' => $this->division_id,
            'station' => $this->station,
            'status' => $this->status,
        ]);
        $user->syncRoles([$this->role]);

        $this->close();
        $this->dispatch('notify', ['icon' => 'success', 'message' => "Pengguna {$user->name} berhasil diperbarui."]);
    }

    public function delete($id)
    {
        $this->authorizeManage();
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);

            return;
        }
        if ($user->hasRole(RoleHelper::SUPER_ADMIN) && $this->activeSuperAdmins($user->id) === 0) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Super Admin terakhir tidak dapat dihapus.']);

            return;
        }

        $user->delete();
        $this->dispatch('notify', ['icon' => 'success', 'message' => "Pengguna {$user->name} dihapus."]);
    }

    public function resetPassword($id)
    {
        $this->authorizeManage();
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            $this->dispatch('notify', ['icon' => 'error', 'message' => 'Ubah password Anda sendiri melalui halaman Profil.']);

            return;
        }

        $user->update([
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'is_default_password' => true,
        ]);

        $this->dispatch('notify', ['icon' => 'success', 'message' => "Password {$user->name} direset ke default.", 'timer' => 5000]);
    }

    public function close()
    {
        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function resetInputFields()
    {
        $this->user_id = null;
        $this->nik = '';
        $this->name = '';
        $this->email = '';
        $this->position_id = null;
        $this->division_id = null;
        $this->station = '';
        $this->role = '';
        $this->status = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::with(['roles', 'division', 'position'])
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('nik', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('station', 'like', $term));
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->role($this->roleFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'stations' => Airport::where('status', 'Aktif')->orderBy('kode')->get(),
            'positions' => Position::where('status', 'Aktif')->orderBy('name')->get(),
            'divisions' => Division::where('status', 'Aktif')->orderBy('name')->get(),
            'totals' => [
                'all' => User::count(),
                'active' => User::where('status', 'active')->count(),
                'inactive' => User::where('status', '!=', 'active')->count(),
                'admins' => User::role(RoleHelper::SUPER_ADMIN)->count(),
            ],
            'defaultPassword' => self::DEFAULT_PASSWORD,
        ])->layout('components.layouts.app', ['title' => 'User Management']);
    }
}
