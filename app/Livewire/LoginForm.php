<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LoginForm extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $credentials = $this->validate();

        if (! Auth::attempt($credentials, $this->remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        if (! $user->canAccessProtectedArea()) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => $user->approvalMessage(),
            ]);
        }

        session()->regenerate();

        return $this->redirect($user->dashboardPath(), navigate: false);
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}
