<x-card highlighted class="mx-auto w-full max-w-xl border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8" data-testid="login-form-card">
    @php
        $redirect = request('redirect');
        $redirectQuery = $redirect ? ['redirect' => $redirect] : [];
    @endphp

    <div class="mb-6">
        <x-badge tone="blue">Account access</x-badge>
        <h2 class="mt-4 text-3xl font-extrabold text-mk-navy">Sign in</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Secure access for students, instructors, mentors, and admins.</p>
    </div>

    <form method="POST" action="{{ route('login.store', $redirectQuery, false) }}" wire:submit="login" class="grid gap-5">
        @csrf
        @if (session('status'))
            <div class="rounded-lg border border-mk-gold/40 bg-mk-goldSoft px-4 py-3 text-sm font-semibold text-mk-navy">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <input wire:model="email" class="mk-input pl-12" id="email" name="email" type="email" autocomplete="email" placeholder="Enter your email address" required>
            </div>
            @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label class="text-sm font-bold text-mk-navy" for="password">Password</label>
                @if (\Illuminate\Support\Facades\Route::has('password.forgot'))
                    <a href="{{ route('password.forgot') }}" class="text-xs font-bold text-mk-blue hover:text-mk-navy">Forgot password?</a>
                @endif
            </div>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
                </span>
                <input wire:model="password" class="mk-input pl-12 pr-12" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/30"
                    aria-label="Show password"
                    onclick="const input = document.getElementById('password'); const showing = input.type === 'text'; input.type = showing ? 'password' : 'text'; this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password'); this.querySelectorAll('svg').forEach((icon) => icon.classList.toggle('hidden'));"
                >
                    <svg class="h-5 w-5" data-eye-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="hidden h-5 w-5" data-eye-closed viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6"/><path d="M9.9 4.3A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-3.1 4.3"/><path d="M6.6 6.6A16.2 16.2 0 0 0 2 12s3.5 8 10 8a10.8 10.8 0 0 0 4.1-.8"/></svg>
                </button>
            </div>
            @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
            <input wire:model="remember" name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-mk-gold focus:ring-mk-gold">
            <span>Keep me signed in</span>
        </label>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in...</span>
        </x-button>

        <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 text-center">
            <p class="text-sm text-slate-600">
                New to MK Scholars?
                <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('register', $redirectQuery) }}">Create an account</a>
            </p>
        </div>
    </form>
</x-card>
