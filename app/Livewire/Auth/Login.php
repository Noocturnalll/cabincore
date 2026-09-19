<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $nik = '';

    public $password = '';

    public function login()
    {
        $this->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['nik' => $this->nik, 'password' => $this->password])) {
            session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $this->addError('nik', 'ID Karyawan atau password yang Anda masukkan salah.');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.guest');
    }
}
