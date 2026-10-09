<?php

namespace App\Livewire\Traits;

/**
 * Shared behaviour for the paginated log tables (WO, DMI, NSRDI, CML):
 * every filter change returns to page 1 and filters can be cleared at once.
 */
trait WithLogTable
{
    public int $perPage = 50;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateStart(): void
    {
        $this->resetPage();
    }

    public function updatedDateEnd(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStation(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array($this->perPage, [25, 50, 100, 200], true) ? $this->perPage : 50;
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        if (property_exists($this, 'dateFilter')) {
            $this->dateFilter = '';
        }
        if (property_exists($this, 'statusFilter')) {
            $this->statusFilter = '';
        }
        if (method_exists($this, 'resetFilters')) {
            $this->resetFilters();
        }
        $this->resetPage();
    }

    public function setTab($tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }
}
