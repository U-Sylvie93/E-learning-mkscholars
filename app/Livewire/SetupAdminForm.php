<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class SetupAdminForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $adminExists = false;

    public function mount(): void
    {
        $this->adminExists = User::where('role', User::ROLE_ADMIN)->exists();
    }

    public function createAdmin()
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            $this->adminExists = true;

            return null;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_ADMIN,
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        Auth::login($admin);
        session()->regenerate();

        return $this->redirect('/admin', navigate: false);
    }

    public function render()
    {
        return view('livewire.setup-admin-form');
    }
}


