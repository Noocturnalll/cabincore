<?php

namespace App\Livewire\Master;

use App\Models\Airport;
use App\Models\CapacityStation;
use App\Models\CapacityTarget;
use Livewire\Component;

class CapacityConfig extends Component
{
    public $activeTab = 'stations';

    public $stations;

    public $targets;

    // For creating/editing station
    public $station_id;

    public $order_no;

    public $kh_region;

    public $group_type;

    public $station_code;

    public $code_store;

    public $working_hours;

    public $tech_day;

    public $tech_night;

    // For targets
    public $target_jt;

    public $target_iu;

    public $target_id;

    public $isModalOpen = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->stations = CapacityStation::orderBy('order_no')->get();
        $this->targets = CapacityTarget::all()->keyBy('aoc');

        $this->target_jt = $this->targets['JT']->target_nsrdi ?? 15;
        $this->target_iu = $this->targets['IU']->target_nsrdi ?? 18;
        $this->target_id = $this->targets['ID']->target_nsrdi ?? 15;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function createStation()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function editStation($id)
    {
        $station = CapacityStation::findOrFail($id);
        $this->station_id = $station->id;
        $this->order_no = $station->order_no;
        $this->kh_region = $station->kh_region;
        $this->group_type = $station->group_type;
        $this->station_code = $station->station_code;
        $this->code_store = $station->code_store;
        $this->working_hours = $station->working_hours;
        $this->tech_day = $station->tech_day;
        $this->tech_night = $station->tech_night;

        $this->isModalOpen = true;
    }

    public function deleteStation($id)
    {
        CapacityStation::findOrFail($id)->delete();
        $this->loadData();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Stasiun berhasil dihapus.']);
    }

    public function saveStation()
    {
        $this->validate([
            'kh_region' => 'required',
            'station_code' => 'required',
        ]);

        CapacityStation::updateOrCreate(
            ['id' => $this->station_id],
            [
                'order_no' => $this->order_no,
                'kh_region' => $this->kh_region,
                'group_type' => $this->group_type,
                'station_code' => $this->station_code,
                'code_store' => $this->code_store,
                'working_hours' => $this->working_hours,
                'tech_day' => $this->tech_day,
                'tech_night' => $this->tech_night,
            ]
        );

        $this->isModalOpen = false;
        $this->resetInputFields();
        $this->loadData();
        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Stasiun berhasil disimpan.']);
    }

    public function resetInputFields()
    {
        $this->station_id = null;
        $this->order_no = null;
        $this->kh_region = '';
        $this->group_type = '';
        $this->station_code = '';
        $this->code_store = '';
        $this->working_hours = '';
        $this->tech_day = null;
        $this->tech_night = null;
    }

    public function updateTargets()
    {
        CapacityTarget::updateOrCreate(['aoc' => 'JT'], ['target_nsrdi' => $this->target_jt]);
        CapacityTarget::updateOrCreate(['aoc' => 'IU'], ['target_nsrdi' => $this->target_iu]);
        CapacityTarget::updateOrCreate(['aoc' => 'ID'], ['target_nsrdi' => $this->target_id]);

        $this->dispatch('notify', ['icon' => 'success', 'message' => 'Targets updated successfully.']);
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.master.capacity-config', [
            'airports' => Airport::orderBy('kode')->get(),
        ])->layout('components.layouts.app', ['title' => 'Master Capacity']);
    }
}
