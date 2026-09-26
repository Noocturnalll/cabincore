<?php

namespace App\Livewire\Users;

use App\Models\Airport;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public $search = '';

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

    public function create()
    {
        $this->resetInputFields();
        $this->isOpen = true;
        $this->isEditMode = false;
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->user_id = $id;
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

    public function store()
    {
        $this->validate([
            'nik' => 'required|string|max:20|unique:users,nik',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'position_id' => 'required|exists:positions,id',
            'division_id' => 'nullable|exists:divisions,id',
            'station' => 'nullable|string|max:10',
            'role' => 'required|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $user = User::create([
            'nik' => $this->nik,
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make('batam123'),
            'position_id' => $this->position_id,
            'division_id' => $this->division_id,
            'station' => $this->station,
            'status' => $this->status,
            'is_default_password' => true,
        ]);

        if ($this->role) {
            $user->assignRole($this->role);
        }

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function update()
    {
        $this->validate([
            'nik' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($this->user_id)],
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:100', Rule::unique('users')->ignore($this->user_id)],
            'position_id' => 'required|exists:positions,id',
            'division_id' => 'nullable|exists:divisions,id',
            'station' => 'nullable|string|max:10',
            'role' => 'required|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $user = User::findOrFail($this->user_id);
        $user->update([
            'nik' => $this->nik,
            'name' => $this->name,
            'email' => $this->email,
            'position_id' => $this->position_id,
            'division_id' => $this->division_id,
            'station' => $this->station,
            'status' => $this->status,
        ]);

        if ($this->role) {
            $user->syncRoles([$this->role]);
        }

        $this->isOpen = false;
        $this->resetInputFields();
    }

    public function delete($id)
    {
        User::findOrFail($id)->delete();
    }

    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'password' => Hash::make('batam123'),
            'is_default_password' => true,
        ]);
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
    }

    public function render()
    {
        $users = User::with(['roles', 'division', 'position'])
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('nik', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('station', 'like', '%'.$this->search.'%');
            })
            ->orderBy('id', 'desc')
            ->get();

        $roles = Role::orderBy('name')->get();
        $stations = Airport::where('status', 'Aktif')->orderBy('kode')->get();
        $positions = Position::where('status', 'Aktif')->orderBy('name')->get();
        $divisions = Division::where('status', 'Aktif')->orderBy('name')->get();

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => $roles,
            'stations' => $stations,
            'positions' => $positions,
            'divisions' => $divisions,
        ])->layout('components.layouts.app', ['title' => 'User Management']);
    }
}
