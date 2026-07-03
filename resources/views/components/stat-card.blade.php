@props([
    'label',
    'tone' => 'gold',
    'value',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<x-card {{ $attributes->class(['min-w-0']) }}>
    <x-badge :tone="$tone">{{ $label }}</x-badge>
    <div class="mt-5 text-3xl font-extrabold text-mk-navy">{{ $value }}</div>
    @if ($description)
        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $description }}</p>
    @endif
    {{ $slot }}
    @if ($actionLabel && $actionHref)
        <x-button :href="$actionHref" size="sm" class="mt-5">{{ $actionLabel }}</x-button>
    @endif
</x-card>
