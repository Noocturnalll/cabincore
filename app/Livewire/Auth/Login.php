<?php

namespace App\Livewire\Auth;

use App\Models\LoginHistory;
use App\Models\User;
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
        return Str::lower(trim((string) $this->nik)).'|'.request()->ip();
    }

    private function ipThrottleKey(): string
    {
        return 'login_ip|'.request()->ip();
    }

    public function login()
    {
        $this->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        $ipKey = $this->ipThrottleKey();
        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $this->addError('nik', "Terlalu banyak request login dari IP Anda. Coba lagi dalam {$seconds} detik.");

            return;
        }

        $userKey = $this->throttleKey();
        if (RateLimiter::tooManyAttempts($userKey, 5)) {
            $seconds = RateLimiter::availableIn($userKey);
            $this->addError('nik', "Terlalu banyak percobaan login gagal untuk akun ini. Coba lagi dalam {$seconds} detik.");

            return;
        }

        $nikTrimmed = trim((string) $this->nik);
        $userCandidate = User::where('nik', $nikTrimmed)->first();

        if (Auth::attempt(['nik' => $nikTrimmed, 'password' => $this->password])) {
            $user = Auth::user();

            // Correct credentials of a disabled account must not start a session
            if ($user->status === 'inactive') {
                Auth::logout();
                RateLimiter::hit($userKey, 300);
                RateLimiter::hit($ipKey, 60);

                LoginHistory::create([
                    'user_id' => $user->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'status' => 'failed',
                ]);

                $this->addError('nik', 'Akun Anda dinonaktifkan. Hubungi administrator.');

                return;
            }

            RateLimiter::clear($userKey);
            RateLimiter::clear($ipKey);

            session()->regenerate();
            session()->regenerateToken();

            LoginHistory::create([
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'status' => 'success',
            ]);

            return redirect()->route('dashboard');
        }

        RateLimiter::hit($userKey, 300);
        RateLimiter::hit($ipKey, 60);

        LoginHistory::create([
            'user_id' => $userCandidate?->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => 'failed',
        ]);

        $this->addError('nik', 'ID Karyawan atau password yang Anda masukkan salah.');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.bare');
    }
}
