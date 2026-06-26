<?php

namespace App\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;

class ContactForm extends Component
{
    #[Validate('required|string|min:2|max:120')]
    public string $name = '';

    #[Validate('required|email|max:160')]
    public string $email = '';

    #[Validate('required|string|max:80')]
    public string $interest = '';

    #[Validate('required|string|min:10|max:1000')]
    public string $message = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate();

        $this->reset('name', 'email', 'interest', 'message');
        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
