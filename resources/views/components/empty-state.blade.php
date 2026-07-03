@props([
    'icon' => 'dashboard',
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-8 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-mk-gold ring-1 ring-slate-200">
        <x-dashboard-icon :name="$icon" class="h-6 w-6" />
    </span>
    <div>
        <p class="text-sm font-bold text-mk-navy">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 max-w-sm text-sm leading-6 text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if ($actionLabel && $actionHref)
        <x-button :href="$actionHref" size="sm" variant="secondary" class="mt-2">{{ $actionLabel }}</x-button>
    @endif
</div>
