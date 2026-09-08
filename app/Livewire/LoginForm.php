<?php

namespace App\Livewire;

use App\Support\LoginAuthenticator;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LoginForm extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public ?string $redirect = null;

    public function mount(): void
    {
        $this->redirect = request()->query('redirect');
    }

    public function login()
    {
        $credentials = $this->validate();
        $user = app(LoginAuthenticator::class)->attempt($credentials, $this->remember);

        if ($user->role === User::ROLE_STUDENT) {
            $redirect = $this->studentRedirect();

            if ($redirect) {
                return $this->redirect($redirect, navigate: false);
            }

            return $this->redirectRoute('student.my-courses', navigate: false);
        }

        return $this->redirect($user->dashboardPath(), navigate: false);
    }

    private function studentRedirect(): ?string
    {
        if (! $this->redirect || ! Str::startsWith($this->redirect, url('/'))) {
            return null;
        }

        return $this->redirect;
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}
