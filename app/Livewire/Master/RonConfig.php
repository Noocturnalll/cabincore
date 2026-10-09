<?php

namespace App\Livewire\Master;

use App\Models\CapacityStation;
use Livewire\Component;

class RonConfig extends Component
{
    public $stations;

    // For editing RON
    public $station_id;

    public $station_code;

    public $kh_region;

    public $ron_jt = 0;

    public $ron_iw = 0;

    public $ron_id = 0;

    public $ron_iu = 0;

    public $ron_sl = 0;

    public $ron_od = 0;

    public $isModalOpen = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->stations = CapacityStation::orderBy('order_no')->get();
    }

    public function editRon($id)
    {
        $station = CapacityStation::findOrFail($id);
        $this->station_id = $station->id;
        $this->station_code = $station->station_code;
        $this->kh_region = $station->kh_region;

        $this->ron_jt = $station->ron_jt;
        $this->ron_iw = $station->ron_iw;
        $this->ron_id = $station->ron_id;
        $this->ron_iu = $station->ron_iu;
        $this->ron_sl = $station->ron_sl;
        $this->ron_od = $station->ron_od;

        $this->isModalOpen = true;
    }

    public function saveRon()
    {
        $this->validate([
            'ron_jt' => 'required|integer|min:0|max:999',
            'ron_iw' => 'required|integer|min:0|max:999',
            'ron_id' => 'required|integer|min:0|max:999',
            'ron_iu' => 'required|integer|min:0|max:999',
            'ron_sl' => 'required|integer|min:0|max:999',
            'ron_od' => 'required|integer|min:0|max:999',
        ]);

        CapacityStation::where('id', $this->station_id)->update([
            'ron_jt' => $this->ron_jt,
            'ron_iw' => $this->ron_iw,
            'ron_id' => $this->ron_id,
            'ron_iu' => $this->ron_iu,
            'ron_sl' => $this->ron_sl,
            'ron_od' => $this->ron_od,
        ]);

        $this->isModalOpen = false;
        $this->loadData();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Data RON berhasil diperbarui.']);
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetValidation();
    }

    public function resetToDefault()
    {
        $user = auth()->user();

        // Check if user is Super Admin or Administrator
        if ($user->hasRole('Super Admin') || $user->hasRole('Administrator') || in_array($user->position_id, [1, 6])) {
            CapacityStation::query()->update([
                'ron_jt' => 0,
                'ron_iw' => 0,
                'ron_id' => 0,
                'ron_iu' => 0,
                'ron_sl' => 0,
                'ron_od' => 0,
            ]);
            $msg = 'Semua nilai RON untuk seluruh STA berhasil di-reset menjadi 0.';
        } else {
            $userStations = array_map('trim', explode(',', $user->station ?? ''));

            CapacityStation::whereIn('station_code', $userStations)->update([
                'ron_jt' => 0,
                'ron_iw' => 0,
                'ron_id' => 0,
                'ron_iu' => 0,
                'ron_sl' => 0,
                'ron_od' => 0,
            ]);
            $msg = 'Nilai RON untuk STA '.implode(', ', $userStations).' berhasil di-reset menjadi 0.';
        }

        $this->loadData();
        $this->dispatch('notify', ['icon' => 'success', 'message' => $msg]);
    }

    public function render()
    {
        return view('livewire.master.ron-config')->layout('components.layouts.app', ['title' => 'Master RON']);
    }
}
