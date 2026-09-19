<?php

namespace App\Livewire\Verification;

use Livewire\Component;

class Queue extends Component
{
    public function render()
    {
        return view('livewire.verification.queue')->layout('components.layouts.app');
    }
}
