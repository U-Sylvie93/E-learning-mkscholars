<x-card highlighted class="mx-auto w-full max-w-xl border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8" data-testid="setup-admin-form-card">
    <div class="mb-6">
        <x-badge tone="gold">Protected setup</x-badge>
        <h2 class="mt-4 text-3xl font-extrabold text-mk-navy">First admin account</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Create the first administrator only on a trusted local or deployment environment.</p>
    </div>

    @if ($adminExists)
        <div class="rounded-xl border border-mk-gold/40 bg-mk-goldSoft px-5 py-4">
            <div class="flex gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-mk-navy text-mk-gold" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <div>
                    <p class="font-bold text-mk-navy">Admin setup is already complete.</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">An administrator account already exists, so this setup page is blocked. Please sign in with an admin account.</p>
                </div>
            </div>
            <x-button :href="route('login')" class="mt-5" variant="navy">Go to login</x-button>
        </div>
    @else
        <form wire:submit="createAdmin" class="grid gap-5">
            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_name">Admin name</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input wire:model.blur="name" class="mk-input pl-12" id="admin_name" type="text" autocomplete="name" placeholder="Enter administrator name">
                </div>
                @error('name') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_email">Email address</label>
                <div class="relative mt-2">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <input wire:model.blur="email" class="mk-input pl-12" id="admin_email" type="email" autocomplete="email" placeholder="Enter administrator email">
                </div>
                @error('email') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-bold text-mk-navy" for="admin_password">Password</label>
                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke-linecap="round"/></svg>
                        </span>
                        <input wire:model.blur="password" class="mk-input pl-12" id="admin_password" type="password" autocomplete="new-password" placeholder="Create password">
                    </div>
                    @error('password') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="text-sm font-bold text-mk-navy" for="admin_password_confirmation">Confirm password</label>
                    <div class="relative mt-2">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <input wire:model.blur="password_confirmation" class="mk-input pl-12" id="admin_password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat password">
                    </div>
                </div>
            </div>

            <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Create admin account</span>
                <span wire:loading>Creating admin...</span>
            </x-button>
        </form>
    @endif
</x-card>
