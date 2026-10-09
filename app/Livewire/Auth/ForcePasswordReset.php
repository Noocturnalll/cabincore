<?php

namespace App\Livewire\Auth;

use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ForcePasswordReset extends Component
{
    public $password = '';

    public $password_confirmation = '';

    public function updatePassword()
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if (defined(UsersIndex::class.'::DEFAULT_PASSWORD') && $this->password === UsersIndex::DEFAULT_PASSWORD) {
            $this->addError('password', 'Password baru tidak boleh sama dengan password default ('.UsersIndex::DEFAULT_PASSWORD.').');

            return;
        }

        $user = auth()->user();
        $user->password = Hash::make($this->password);
        $user->is_default_password = false;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Password berhasil diperbarui. Selamat datang di Cabin Core!');
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.force-password-reset')
            ->layout('components.layouts.bare', ['title' => 'Cabin Core — Ganti Password Default']);
    }
}
