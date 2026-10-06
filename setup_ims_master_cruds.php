<?php

$masters = [
    'Category' => [
        'table' => 'ims_categories',
        'fields' => ['code', 'name', 'ata_chapter', 'is_active'],
        'labels' => ['Kode', 'Kategori', 'ATA Chapter', 'Status'],
        'types'  => ['text', 'text', 'text', 'boolean'],
        'title'  => 'Kategori Barang',
        'route'  => 'categories'
    ],
    'Unit' => [
        'table' => 'ims_units',
        'fields' => ['code', 'name', 'is_active'],
        'labels' => ['Kode', 'Satuan', 'Status'],
        'types'  => ['text', 'text', 'boolean'],
        'title'  => 'Satuan (UOM)',
        'route'  => 'units'
    ],
    'Location' => [
        'table' => 'ims_locations',
        'fields' => ['code', 'name', 'type', 'is_active'],
        'labels' => ['Kode', 'Lokasi', 'Tipe', 'Status'],
        'types'  => ['text', 'text', 'select', 'boolean'],
        'title'  => 'Lokasi & Rak',
        'route'  => 'locations'
    ],
    'Supplier' => [
        'table' => 'ims_suppliers',
        'fields' => ['code', 'name', 'contact_person', 'phone', 'is_active'],
        'labels' => ['Kode', 'Supplier', 'Kontak', 'Telepon', 'Status'],
        'types'  => ['text', 'text', 'text', 'text', 'boolean'],
        'title'  => 'Data Supplier',
        'route'  => 'suppliers'
    ],
    'AircraftType' => [
        'table' => 'ims_aircraft_types',
        'fields' => ['code', 'manufacturer', 'model', 'is_active'],
        'labels' => ['Kode', 'Pabrikan', 'Model', 'Status'],
        'types'  => ['text', 'text', 'text', 'boolean'],
        'title'  => 'Tipe Pesawat',
        'route'  => 'aircraft-types'
    ]
];

$dirClass = __DIR__ . '/app/Livewire/Modules/Ims/Master';
$dirView = __DIR__ . '/resources/views/livewire/modules/ims/master';

if (!is_dir($dirClass)) mkdir($dirClass, 0777, true);
if (!is_dir($dirView)) mkdir($dirView, 0777, true);

foreach ($masters as $model => $config) {
    // Generate Component Class
    $className = $model . 's'; // e.g. Categories
    if ($model == 'Category') $className = 'Categories';
    if ($model == 'AircraftType') $className = 'AircraftTypes';
    
    $rules = [];
    $properties = [];
    $resetData = [];
    foreach ($config['fields'] as $field) {
        if ($field != 'is_active') {
            $rules[] = "'form.{$field}' => 'required|string|max:255'";
        } else {
            $rules[] = "'form.is_active' => 'boolean'";
        }
        $properties[] = "        '{$field}' => " . ($field == 'is_active' ? 'true' : "''") . ",";
        $resetData[] = "\$this->form['{$field}'] = " . ($field == 'is_active' ? 'true' : "''") . ";";
    }
    
    if ($model == 'Location') {
        $rules[2] = "'form.type' => 'required|in:warehouse,rack,shelf,repair_area,quarantine'";
    }

    $rulesStr = implode(",\n        ", $rules);
    $propsStr = implode("\n", $properties);
    $resetStr = implode("\n        ", $resetData);
    
    $classCode = <<<PHP
<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ims\\{$model};

class {$className} extends Component
{
    use WithPagination;

    public \$search = '';
    public \$isOpen = false;
    public \$isEdit = false;
    public \$editId = null;

    public \$form = [
{$propsStr}
    ];

    public function updatingSearch() { \$this->resetPage(); }

    public function create()
    {
        \$this->resetForm();
        \$this->isEdit = false;
        \$this->isOpen = true;
    }

    public function edit(\$id)
    {
        \$this->resetForm();
        \$record = {$model}::findOrFail(\$id);
        \$this->editId = \$id;
        \$this->isEdit = true;
        
        foreach (array_keys(\$this->form) as \$key) {
            \$this->form[\$key] = \$record->{\$key};
        }
        
        \$this->isOpen = true;
    }

    public function save()
    {
        \$this->validate([
            {$rulesStr}
        ]);

        if (\$this->isEdit) {
            \$record = {$model}::findOrFail(\$this->editId);
            \$record->update(\$this->form);
            session()->flash('success', 'Data berhasil diperbarui.');
        } else {
            {$model}::create(\$this->form);
            session()->flash('success', 'Data berhasil ditambahkan.');
        }

        \$this->isOpen = false;
    }

    public function delete(\$id)
    {
        \$record = {$model}::findOrFail(\$id);
        \$record->delete();
        session()->flash('success', 'Data berhasil dihapus.');
    }

    public function resetForm()
    {
        {$resetStr}
        \$this->editId = null;
    }

    public function render()
    {
        \$query = {$model}::query();
        
        if (\$this->search) {
            \$query->where('name', 'like', '%' . \$this->search . '%')
                  ->orWhere('code', 'like', '%' . \$this->search . '%');
        }

        return view('livewire.modules.ims.master.' . strtolower('{$className}'), [
            'records' => \$query->paginate(15)
        ])->layout('components.layouts.app', ['title' => '{$config['title']} - IMS Master']);
    }
}
PHP;

    file_put_contents($dirClass . '/' . $className . '.php', $classCode);

    // Generate View
    $viewName = strtolower($className);
    
    $thStr = "";
    $tdStr = "";
    $formStr = "";
    
    foreach ($config['fields'] as $index => $field) {
        $label = $config['labels'][$index];
        $type = $config['types'][$index];
        
        $thStr .= "<th>{$label}</th>\n                        ";
        
        if ($field == 'is_active') {
            $tdStr .= "<td>\n                                @if(\$record->is_active)\n                                    <span class=\"mod-badge-closed\">Aktif</span>\n                                @else\n                                    <span class=\"mod-badge-inactive\">Nonaktif</span>\n                                @endif\n                            </td>\n                            ";
        } else {
            $tdStr .= "<td>{{ \$record->{$field} }}</td>\n                            ";
        }

        if ($field == 'is_active') {
            $formStr .= <<<HTML
                <div style="margin-bottom: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;">
                        <input type="checkbox" wire:model="form.is_active" style="width: 1rem; height: 1rem;">
                        Status Aktif
                    </label>
                </div>

HTML;
        } elseif ($field == 'type' && $model == 'Location') {
            $formStr .= <<<HTML
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">Tipe Lokasi</label>
                    <select wire:model="form.type" class="mod-search-input" style="width: 100%;">
                        <option value="">-- Pilih Tipe --</option>
                        <option value="warehouse">Warehouse</option>
                        <option value="rack">Rak</option>
                        <option value="shelf">Shelf</option>
                        <option value="repair_area">Repair Area</option>
                        <option value="quarantine">Quarantine</option>
                    </select>
                    @error('form.type') <span style="color: red; font-size: 0.75rem;">{{ \$message }}</span> @enderror
                </div>

HTML;
        } else {
            $formStr .= <<<HTML
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem;">{$label}</label>
                    <input type="text" wire:model="form.{$field}" class="mod-search-input" style="width: 100%;">
                    @error('form.{$field}') <span style="color: red; font-size: 0.75rem;">{{ \$message }}</span> @enderror
                </div>

HTML;
        }
    }

    $viewCode = <<<HTML
<div>
    <div class="cbm-page-header mod-header">
        <div class="mod-title-block">
            <span class="mod-title-accent mod-title-accent-blue">Master Data</span>
            <h1 class="mod-title">{$config['title']}</h1>
            <p class="mod-subtitle">Kelola data {$config['title']} untuk sistem IMS.</p>
        </div>
        
        <div class="mod-header-actions">
            <button wire:click="create" class="mod-btn-primary">
                + Tambah Data
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div style="background: #ecfdf5; color: #065f46; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; border: 1px solid #10b981;">
            {{ session('success') }}
        </div>
    @endif

    <div class="mod-card">
        <div class="mod-toolbar">
            <div class="mod-search-wrap" style="max-width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari..." class="mod-search-input" wire:model.live.debounce.300ms="search">
            </div>
        </div>

        <div class="mod-table-wrap">
            <table class="mod-table">
                <thead>
                    <tr>
                        {$thStr}<th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(\$records as \$record)
                        <tr>
                            {$tdStr}<td style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                                <button wire:click="edit({{ \$record->id }})" class="mod-action-btn">Edit</button>
                                <button wire:click="delete({{ \$record->id }})" class="mod-action-btn" style="color: #ef4444; border-color: #ef4444;" onclick="confirm('Yakin ingin menghapus data ini?') || event.stopImmediatePropagation()">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="mod-empty">
                                    <h4 class="mod-empty-title">Data kosong.</h4>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mod-pagination">
            {{ \$records->links('pagination::tailwind') }}
        </div>
    </div>

    <!-- Modal Form -->
    @if(\$isOpen)
    <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5);">
        <div style="background: var(--cbm-card-bg); width: 100%; max-width: 500px; border-radius: 1rem; box-shadow: var(--cbm-card-shadow); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--cbm-border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.25rem; font-weight: 600;">{{ \$isEdit ? 'Edit Data' : 'Tambah Data' }}</h3>
                <button wire:click="\$set('isOpen', false)" style="background: none; border: none; cursor: pointer; color: var(--cbm-text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <form wire:submit.prevent="save">
                <div style="padding: 1.5rem;">
{$formStr}                </div>
                
                <div style="padding: 1rem 1.5rem; background: var(--cbm-sidebar-bg); border-top: 1px solid var(--cbm-border); display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" wire:click="\$set('isOpen', false)" class="mod-action-btn">Batal</button>
                    <button type="submit" class="mod-btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
HTML;
    
    file_put_contents($dirView . '/' . $viewName . '.blade.php', $viewCode);
}

echo "All 5 master CRUD components generated successfully.\n";
