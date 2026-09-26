<?php

// 1. Create or Update Model
$modelContent = <<<PHP
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model
{
    use HasFactory;

    protected \$table = 'aircrafts';
    protected \$fillable = ['registration', 'tipe', 'maskapai', 'status'];
}
PHP;
file_put_contents('c:/Users/achai/cbm/app/Models/Aircraft.php', $modelContent);

// 2. Update Livewire Component
$livewireContent = <<<PHP
<?php

namespace App\Livewire\Master;

use App\Models\Aircraft;
use Livewire\Component;
use Livewire\WithPagination;

class Aircrafts extends Component // Wait, the route uses \App\Livewire\Master\Aircraft
{
    use WithPagination;

    public \$search = '';
    
    public \$aircraft_id, \$registration, \$tipe, \$maskapai, \$status = 'Aktif';
    public \$isEditMode = false;
    public \$isOpen = false;

    protected \$rules = [
        'registration' => 'required|string|max:20',
        'tipe' => 'required|string|max:100',
        'maskapai' => 'required|string|max:100',
        'status' => 'required|string',
    ];

    public function updatingSearch()
    {
        \$this->resetPage();
    }

    public function create()
    {
        \$this->resetInputFields();
        \$this->isOpen = true;
        \$this->isEditMode = false;
    }

    public function edit(\$id)
    {
        \$aircraft = Aircraft::findOrFail(\$id);
        \$this->aircraft_id = \$id;
        \$this->registration = \$aircraft->registration;
        \$this->tipe = \$aircraft->tipe;
        \$this->maskapai = \$aircraft->maskapai;
        \$this->status = \$aircraft->status;
        
        \$this->isOpen = true;
        \$this->isEditMode = true;
    }

    public function store()
    {
        \$this->validate();

        Aircraft::create([
            'registration' => \$this->registration,
            'tipe' => \$this->tipe,
            'maskapai' => \$this->maskapai,
            'status' => \$this->status,
        ]);

        \$this->isOpen = false;
        \$this->resetInputFields();
    }

    public function update()
    {
        \$this->validate();

        \$aircraft = Aircraft::findOrFail(\$this->aircraft_id);
        \$aircraft->update([
            'registration' => \$this->registration,
            'tipe' => \$this->tipe,
            'maskapai' => \$this->maskapai,
            'status' => \$this->status,
        ]);

        \$this->isOpen = false;
        \$this->resetInputFields();
    }

    public function delete(\$id)
    {
        Aircraft::findOrFail(\$id)->delete();
    }

    public function close()
    {
        \$this->isOpen = false;
        \$this->resetInputFields();
    }

    public function resetInputFields()
    {
        \$this->aircraft_id = null;
        \$this->registration = '';
        \$this->tipe = '';
        \$this->maskapai = '';
        \$this->status = 'Aktif';
    }

    public function render()
    {
        \$aircrafts = Aircraft::where('registration', 'like', '%'.\$this->search.'%')
            ->orWhere('tipe', 'like', '%'.\$this->search.'%')
            ->orWhere('maskapai', 'like', '%'.\$this->search.'%')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.master.aircraft', [
            'aircrafts' => \$aircrafts
        ])->layout('components.layouts.app');
    }
}
PHP;

// Wait, the class was named Aircraft, not Aircrafts. Let me fix that.
$livewireContent = str_replace('class Aircrafts extends Component', 'class Aircraft extends Component', $livewireContent);

file_put_contents('c:/Users/achai/cbm/app/Livewire/Master/Aircraft.php', $livewireContent);

// 3. Generate View
$bladeContent = <<<'BLADE'
<div>
    <div class="cbm-page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, #8b5cf6, #6366f1); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px rgba(139,92,246,0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.5rem;">Registrasi Pesawat</h1>
                <p class="cbm-greeting-sub">Kelola armada pesawat dan tipe pesawat</p>
            </div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 0;">
            <div style="position: relative; width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--cbm-text-muted);">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari Registrasi..." class="cbm-input" style="padding-left: 2.75rem; width: 100%; height: 2.75rem; border-radius: .875rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" wire:model.live.debounce.300ms="search">
            </div>
            <button type="button" wire:click.prevent="create" class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, #8b5cf6, #6366f1); box-shadow: 0 4px 12px rgba(139,92,246,0.3); border: none; color: white; padding: 0 1.5rem; height: 2.75rem; border-radius: .875rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Data
            </button>
        </div>
        
        <div class="cbm-card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: var(--cbm-bg); border-bottom: 1px solid var(--cbm-card-border);">
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">Registrasi</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">Tipe Pesawat</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">Maskapai</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">Status</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($aircrafts as $ac)
                    <tr style="border-bottom: 1px solid var(--cbm-card-border); transition: background 0.2s;">
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text); font-weight: 600;">{{ $ac->registration }}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{{ $ac->tipe }}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{{ $ac->maskapai }}</td>
                        <td style="padding: 1rem 1.5rem;">
                            @if($ac->status == 'Aktif')
                                <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">Aktif</span>
                            @else
                                <span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(239,68,68,0.2);">Tidak Aktif</span>
                            @endif
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right;">
                            <button type="button" wire:click.prevent="edit({{ $ac->id }})" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                            <button type="button" onclick="confirm('Yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click.prevent="delete({{ $ac->id }})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: var(--cbm-text-muted);">Tidak ada data pesawat yang ditemukan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="cbm-card-footer" style="padding: 1rem 1.5rem; border-top: 1px solid var(--cbm-card-border);">
            {{ $aircrafts->links(data: ['scrollTo' => false]) }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($isOpen)
    <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50;">
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 500px; border-radius: 1.25rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--cbm-text);">{{ $isEditMode ? 'Edit Pesawat' : 'Tambah Pesawat Baru' }}</h3>
                <button type="button" wire:click.prevent="close" style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            <div style="padding: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Registrasi</label>
                    <input type="text" wire:model="registration" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" placeholder="Contoh: PK-LBF">
                    @error('registration') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Tipe Pesawat</label>
                    <input type="text" wire:model="tipe" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" placeholder="Contoh: Boeing 737-800">
                    @error('tipe') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Maskapai</label>
                    <select wire:model="maskapai" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        <option value="">Pilih Maskapai...</option>
                        <option value="Batik Air">Batik Air</option>
                        <option value="Lion Air">Lion Air</option>
                        <option value="Super Air Jet">Super Air Jet</option>
                        <option value="Wings Air">Wings Air</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                    @error('maskapai') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: .875rem; font-weight: 600; color: var(--cbm-text); margin-bottom: .5rem;">Status</label>
                    <select wire:model="status" class="cbm-input" style="width: 100%; padding: .75rem 1rem; border-radius: .75rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);">
                        <option value="Aktif">Aktif</option>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                    </select>
                    @error('status') <span style="color: #ef4444; font-size: .75rem; margin-top: .25rem; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>
            <div style="padding: 1.25rem 1.5rem; border-top: 1px solid var(--cbm-card-border); background: var(--cbm-bg); display: flex; justify-content: flex-end; gap: .75rem;">
                <button type="button" wire:click.prevent="close" style="padding: .75rem 1.5rem; border-radius: .75rem; background: transparent; border: 1px solid var(--cbm-card-border); color: var(--cbm-text); font-weight: 600; cursor: pointer;">Batal</button>
                <button type="button" wire:click.prevent="{{ $isEditMode ? 'update' : 'store' }}" style="padding: .75rem 1.5rem; border-radius: .75rem; background: linear-gradient(135deg, #8b5cf6, #6366f1); border: none; color: white; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(139,92,246,0.3);">{{ $isEditMode ? 'Simpan Perubahan' : 'Tambah Data' }}</button>
            </div>
        </div>
    </div>
    @endif
</div>
BLADE;
file_put_contents('c:/Users/achai/cbm/resources/views/livewire/master/aircraft.blade.php', $bladeContent);

echo 'Aircraft CRUD generated successfully!';
