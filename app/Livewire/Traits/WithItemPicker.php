<?php

namespace App\Livewire\Traits;

use App\Models\Ims\Item;

/**
 * Searchable item dropdown shared by the IMS stock forms (receive / opname / transfer).
 * The component must declare public $itemId.
 */
trait WithItemPicker
{
    public $searchItem = '';

    /** Up to 20 matches for the search text; the selected item is always included. */
    protected function pickerItems()
    {
        $items = Item::where('is_active', true)
            ->when($this->searchItem !== '', function ($q) {
                $q->where(function ($w) {
                    $w->where('name', 'like', '%'.$this->searchItem.'%')
                        ->orWhere('part_number', 'like', '%'.$this->searchItem.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        if ($this->itemId && ! $items->contains('id', (int) $this->itemId)) {
            $selected = Item::find($this->itemId);
            if ($selected) {
                $items->prepend($selected);
            }
        }

        return $items;
    }

    /** Server-side guard: the view hides these pages by permission, the action must too. */
    protected function authorizeStockAction(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403, 'Anda tidak memiliki akses untuk aksi ini.');
    }
}
