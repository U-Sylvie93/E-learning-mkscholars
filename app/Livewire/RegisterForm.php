<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RegisterForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = User::ROLE_STUDENT;

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in([
                User::ROLE_STUDENT,
                User::ROLE_INSTRUCTOR,
                User::ROLE_MENTOR,
            ])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $approvalStatus = in_array($validated['role'], [User::ROLE_INSTRUCTOR, User::ROLE_MENTOR], true)
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

        return $this->redirect($user->dashboardPath(), navigate: false);
    }

    public function render()
    {
        return view('livewire.register-form');
    }
}
