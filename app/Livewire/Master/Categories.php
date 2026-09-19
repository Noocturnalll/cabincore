<?php

namespace App\Livewire\Master;

use Livewire\Component;

class Categories extends Component
{
    public function render()
    {
        return view('livewire.master.categories')->layout('components.layouts.app');
    }
}
