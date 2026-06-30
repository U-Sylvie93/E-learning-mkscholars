<x-card highlighted class="mx-auto w-full max-w-xl border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8" data-testid="register-form-card">
    <div class="mb-6">
        <x-badge tone="blue">Account details</x-badge>
        <h2 class="mt-4 text-3xl font-extrabold text-mk-navy">Create your account</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Choose the workspace that matches how you will use MK Scholars.</p>
    </div>

    <form wire:submit="register" class="grid gap-5">
        <div>
            <label class="text-sm font-bold text-mk-navy" for="name">Full name</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <input wire:model.blur="name" class="mk-input pl-12" id="name" type="text" autocomplete="name" placeholder="Enter your full name">
            </div>
            @error('name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input wire:model.blur="email" class="mk-input pl-12" id="email" type="email" autocomplete="email" placeholder="Enter your email address">
            </div>
            @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="role">I am joining as</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M8 6v14"/><path d="M16 6v14"/><path d="M3 20h18"/><path d="M6 10h4M14 10h4" stroke-linecap="round"/></svg>
                </span>
                <select wire:model.blur="role" class="mk-input pl-12" id="role">
                    <option value="student">Student</option>
                    <option value="instructor">Instructor</option>
                </select>
            </div>
            @error('role') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="text-sm font-bold text-mk-navy" for="password">Password</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
                    </span>
                    <input wire:model.blur="password" class="mk-input pl-12" id="password" type="password" autocomplete="new-password" placeholder="Create a password">
                </div>
                @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-bold text-mk-navy" for="password_confirmation">Confirm password</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input wire:model.blur="password_confirmation" class="mk-input pl-12" id="password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat password">
                </div>
            </div>
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Create account</span>
            <span wire:loading>Creating account...</span>
        </x-button>

        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
            <p class="text-sm text-slate-600">
                Already have an account?
                <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </form>
</x-card>

