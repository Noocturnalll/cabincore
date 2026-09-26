<?php

$components = ['General', 'Interior', 'Exterior'];

$bladeTemplate = <<<'BLADE'
<div>
    <div class="cbm-page-header">
        <div>
            <h1 class="cbm-greeting">Aircraft Cleaning <span>- {NAME}</span></h1>
            <div class="cbm-greeting-sub">Kelola data {NAME}</div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header">
            <div>
                <h3 class="cbm-card-title">Sedang Dalam Pengembangan</h3>
                <div class="cbm-card-sub">Fitur ini akan segera hadir.</div>
            </div>
        </div>
        <div class="cbm-card-body" style="padding: 2rem; text-align: center;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 4rem; height: 4rem; margin: 0 auto; color: var(--cbm-text-muted); opacity: 0.5;">
                <path fill-rule="evenodd" d="M9.53 2.47a.75.75 0 011.06 0l4.5 4.5a.75.75 0 010 1.06l-4.5 4.5a.75.75 0 01-1.06-1.06l3.22-3.22H3a.75.75 0 010-1.5h9.69l-3.22-3.22a.75.75 0 010-1.06zM15 12a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5A.75.75 0 0115 12zm0 4.5a.75.75 0 01.75-.75h4.5a.75.75 0 010 1.5h-4.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
            </svg>
            <p style="margin-top: 1rem; color: var(--cbm-text-muted); font-size: .875rem;">Halaman {NAME} belum tersedia secara penuh.</p>
        </div>
    </div>
</div>
BLADE;

$phpTemplate = <<<PHP
<?php

namespace App\Livewire\Modules\AircraftCleaning;

use Livewire\Component;

class {CLASS_NAME} extends Component
{
    public function render()
    {
        return view('livewire.modules.aircraft-cleaning.{VIEW_NAME}')
            ->layout('components.layouts.app', ['title' => 'Aircraft Cleaning - {TITLE}']);
    }
}
PHP;

foreach ($components as $comp) {
    // Generate Blade
    $viewName = strtolower($comp);
    $bladeContent = str_replace('{NAME}', ($comp == 'Interior' ? 'Interior Cleaning (DCI)' : ($comp == 'Exterior' ? 'Exterior Cleaning (DCE)' : 'General Cleaning')), $bladeTemplate);
    file_put_contents("c:/Users/achai/cbm/resources/views/livewire/modules/aircraft-cleaning/{$viewName}.blade.php", $bladeContent);

    // Generate PHP
    $title = ($comp == 'Interior' ? 'Interior (DCI)' : ($comp == 'Exterior' ? 'Exterior (DCE)' : 'General'));
    $phpContent = str_replace(['{CLASS_NAME}', '{VIEW_NAME}', '{TITLE}'], [$comp, $viewName, $title], $phpTemplate);
    file_put_contents("c:/Users/achai/cbm/app/Livewire/Modules/AircraftCleaning/{$comp}.php", $phpContent);
}

// Optionally, delete the old Index.php and index.blade.php as they are no longer used by routes
@unlink('c:/Users/achai/cbm/app/Livewire/Modules/AircraftCleaning/Index.php');
@unlink('c:/Users/achai/cbm/resources/views/livewire/modules/aircraft-cleaning/index.blade.php');

echo 'Files generated successfully!';
