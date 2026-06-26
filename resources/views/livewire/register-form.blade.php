<x-card>
    <form wire:submit="register" class="grid gap-5">
        <div>
            <label class="text-sm font-bold text-mk-navy" for="name">Full name</label>
            <input wire:model.blur="name" class="mk-input mt-2" id="name" type="text" autocomplete="name" placeholder="Your full name">
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
            <input wire:model.blur="email" class="mk-input mt-2" id="email" type="email" autocomplete="email" placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="role">I am joining as</label>
            <select wire:model.blur="role" class="mk-input mt-2" id="role">
                <option value="student">Student</option>
                <option value="instructor">Instructor</option>
                <option value="mentor">Mentor</option>
            </select>
            @error('role') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="password">Password</label>
            <input wire:model.blur="password" class="mk-input mt-2" id="password" type="password" autocomplete="new-password" placeholder="At least 8 characters">
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="password_confirmation">Confirm password</label>
            <input wire:model.blur="password_confirmation" class="mk-input mt-2" id="password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat your password">
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Create account</span>
            <span wire:loading>Creating account...</span>
        </x-button>

        <p class="text-center text-sm text-slate-600">
            Already have an account?
            <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('login') }}">Sign in</a>
        </p>
    </form>
</x-card>
