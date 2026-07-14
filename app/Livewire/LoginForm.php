<?php

namespace App\Livewire;

use App\Support\LoginAuthenticator;
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
        $user = app(LoginAuthenticator::class)->attempt($credentials, $this->remember);

        return $this->redirect($user->dashboardPath(), navigate: false);
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}
