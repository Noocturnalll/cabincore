<?php

namespace App\Livewire\AuditTrail;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.audittrail.index')->layout('components.layouts.app');
    }
}
