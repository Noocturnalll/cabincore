<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public $nik = '';

    public $password = '';

    private function throttleKey(): string
    {
        return Str::lower($this->nik).'|'.request()->ip();
    }

    public function login()
    {
        $this->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $this->addError('nik', 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($this->throttleKey()).' detik.');

            return;
        }

        if (Auth::attempt(['nik' => $this->nik, 'password' => $this->password])) {
            // Correct credentials of a disabled account must not start a session
            if (Auth::user()->status === 'inactive') {
                Auth::logout();
                RateLimiter::hit($this->throttleKey());
                $this->addError('nik', 'Akun Anda dinonaktifkan. Hubungi administrator.');

                return;
            }

            RateLimiter::clear($this->throttleKey());
            session()->regenerate();

            return redirect()->route('dashboard');
        }

        RateLimiter::hit($this->throttleKey());
        $this->addError('nik', 'ID Karyawan atau password yang Anda masukkan salah.');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.bare');
    }
}
