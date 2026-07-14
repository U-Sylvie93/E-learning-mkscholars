<x-card highlighted class="mx-auto w-full max-w-xl border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8" data-testid="login-form-card">
    <div class="mb-6">
        <x-badge tone="blue">Account access</x-badge>
        <h2 class="mt-4 text-3xl font-extrabold text-mk-navy">Sign in</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Secure access for students, instructors, mentors, and admins.</p>
    </div>

    <form method="POST" action="{{ route('login.store', absolute: false) }}" wire:submit="login" class="grid gap-5">
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
            <label class="text-sm font-bold text-mk-navy" for="password">Password</label>
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
                </span>
                <input wire:model="password" class="mk-input pl-12" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
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
                <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('register') }}">Create an account</a>
            </p>
        </div>
    </form>
</x-card>
