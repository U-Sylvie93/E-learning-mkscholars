<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RegisterForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = User::ROLE_STUDENT;

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $redirect = null;

    public function mount(): void
    {
        $this->redirect = request()->query('redirect');
    }

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in([
                User::ROLE_STUDENT,
                User::ROLE_INSTRUCTOR,
            ])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $approvalStatus = in_array($validated['role'], [User::ROLE_INSTRUCTOR], true)
            ? User::APPROVAL_PENDING
            : User::APPROVAL_APPROVED;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'approval_status' => $approvalStatus,
            'approved_at' => $approvalStatus === User::APPROVAL_APPROVED ? now() : null,
        ]);

        if ($user->requiresApproval()) {
            session()->flash('status', 'Your account is pending admin approval. You can sign in after MK Scholars approves your profile.');

            return $this->redirectRoute('login', navigate: false);
        }

        Auth::login($user);
        session()->regenerate();

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
        return view('livewire.register-form');
    }
}
