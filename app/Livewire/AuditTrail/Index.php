<?php

namespace App\Livewire\AuditTrail;

use Livewire\Component;
use App\Models\AuditLog;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $logs = AuditLog::with('user')->orderBy('created_at', 'desc')->paginate(15);
        
        return view('livewire.audit-trail.index', [
            'logs' => $logs
        ])->layout('components.layouts.app', ['title' => 'Audit Trail']);
    }
}
