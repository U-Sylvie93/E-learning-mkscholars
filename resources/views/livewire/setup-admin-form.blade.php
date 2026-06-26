<x-card>
    @if ($adminExists)
        <div class="rounded-md border border-mk-gold/40 bg-mk-goldSoft px-5 py-4">
            <p class="font-bold text-mk-navy">Admin setup is already complete.</p>
            <p class="mt-2 text-sm leading-6 text-slate-700">
                An administrator account already exists, so this setup page is blocked. Please sign in with an admin account.
            </p>
            <x-button :href="route('login')" class="mt-5" variant="navy">Go to login</x-button>
        </div>
    @else
        <form wire:submit="createAdmin" class="grid gap-5">
            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_name">Admin name</label>
                <input wire:model.blur="name" class="mk-input mt-2" id="admin_name" type="text" autocomplete="name" placeholder="Platform administrator">
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_email">Email address</label>
                <input wire:model.blur="email" class="mk-input mt-2" id="admin_email" type="email" autocomplete="email" placeholder="admin@example.com">
                @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_password">Password</label>
                <input wire:model.blur="password" class="mk-input mt-2" id="admin_password" type="password" autocomplete="new-password" placeholder="At least 8 characters">
                @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-sm font-bold text-mk-navy" for="admin_password_confirmation">Confirm password</label>
                <input wire:model.blur="password_confirmation" class="mk-input mt-2" id="admin_password_confirmation" type="password" autocomplete="new-password" placeholder="Repeat password">
            </div>

            <x-button type="submit" class="w-full" wire:loading.attr="disabled">
                <span wire:loading.remove>Create admin account</span>
                <span wire:loading>Creating admin...</span>
            </x-button>
        </form>
    @endif
</x-card>
