<x-card>
    @if ($submitted)
        <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            Thanks. Your message has been received.
        </div>
    @endif

    <form wire:submit="submit" class="grid gap-5">
        <div>
            <label class="text-sm font-bold text-mk-navy" for="name">Full name</label>
            <input wire:model.blur="name" class="mk-input mt-2" id="name" type="text" placeholder="Student or parent name">
            @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
            <input wire:model.blur="email" class="mk-input mt-2" id="email" type="email" placeholder="you@example.com">
            @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="interest">Interest</label>
            <select wire:model.blur="interest" class="mk-input mt-2" id="interest">
                <option value="">Select an option</option>
                <option value="Academies">Academies</option>
                <option value="Courses">Courses</option>
                <option value='Learning support'>Learning support</option>
            </select>
            @error('interest') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="text-sm font-bold text-mk-navy" for="message">Message</label>
            <textarea wire:model.blur="message" class="mk-input mt-2 min-h-36" id="message" placeholder="Tell us what the student needs support with."></textarea>
            @error('message') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <x-button type="submit" class="w-full" wire:loading.attr="disabled">
            <span wire:loading.remove>Send message</span>
            <span wire:loading>Sending...</span>
        </x-button>
    </form>
</x-card>

