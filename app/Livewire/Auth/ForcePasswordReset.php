<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ForcePasswordReset extends Component
{
    public $password = '';

    public $password_confirmation = '';

    public function updatePassword()
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        $user->password = Hash::make($this->password);
        $user->is_default_password = false;
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Password berhasil diubah.');
    }

    public function render()
    {
        return view('livewire.auth.force-password-reset')->layout('components.layouts.guest');
    }
}
