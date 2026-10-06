<?php

namespace App\Livewire\Modules\Ims\Master;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.modules.ims.master.index')
            ->layout('components.layouts.app', ['title' => 'Data Master - IMS']);
    }
}
