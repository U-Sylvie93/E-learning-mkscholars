<x-card>
    <form wire:submit="login" class="grid gap-5">
        @if (session('status'))
            <div class="rounded-md border border-mk-gold/40 bg-mk-goldSoft px-4 py-3 text-sm font-semibold text-mk-navy">
                {{ session('status') }}
            </div>
        @endif
        <div>
            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
            <input wire:model.blur="email" class="mk-input mt-2" id="email" type="email" autocomplete="email" placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="password">Password</label>
            <input wire:model.blur="password" class="mk-input mt-2" id="password" type="password" autocomplete="current-password" placeholder="Enter your password">
            @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-3 text-sm font-semibold text-slate-600">
            <input wire:model="remember" type="checkbox" class="rounded border-slate-300 text-mk-gold focus:ring-mk-gold">
            <span>Keep me signed in</span>
        </label>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Sign in</span>
            <span wire:loading>Signing in...</span>
        </x-button>

        <p class="text-center text-sm text-slate-600">
            New to MK Scholars?
            <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('register') }}">Create an account</a>
        </p>
    </form>
</x-card>

