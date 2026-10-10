<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        abort_unless(auth()->user()?->can('ims.master.manage'), 403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    public function render()
    {
        return view('livewire.modules.ims.master.index')
            ->layout('components.layouts.app', ['title' => 'Data Master - IMS']);
    }
}
