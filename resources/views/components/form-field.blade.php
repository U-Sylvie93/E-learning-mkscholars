@props([
    'name',
    'label' => null,
    'required' => false,
    'help' => null,
])

<div {{ $attributes->class(['w-full']) }}>
    @if ($label)
        <label for="{{ $name }}" class="text-sm font-bold text-mk-navy">{{ $label }}{{ $required ? ' *' : '' }}</label>
    @endif
    <div class="{{ $label ? 'mt-2' : '' }}">
        {{ $slot }}
    </div>
    @if ($help)
        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
