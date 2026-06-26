@props(['opportunity'])

<x-card class="flex h-full flex-col">
    <div class="flex flex-wrap items-center gap-3">
        <x-badge tone="green">{{ $opportunity['type'] }}</x-badge>
        <x-badge :tone="$opportunity['deadline_tone'] ?? 'gray'">{{ $opportunity['deadline_badge'] ?? $opportunity['deadline'] }}</x-badge>
    </div>
    <h3 class="mt-5 break-words text-xl font-bold text-mk-navy">{{ $opportunity['title'] }}</h3>
    @if (! empty($opportunity['organization']) || ! empty($opportunity['country']))
        <p class="mt-2 text-xs font-bold uppercase tracking-wide text-mk-gold">
            {{ collect([$opportunity['organization'] ?? null, $opportunity['city'] ?? null, $opportunity['country'] ?? null])->filter()->join(' - ') }}
        </p>
    @endif
    <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ $opportunity['summary'] }}</p>
    <p class="mt-5 text-sm font-bold text-mk-navy">Deadline: {{ $opportunity['deadline'] }}</p>
    @if (! empty($opportunity['slug']))
        <x-button :href="route('opportunities.show', $opportunity['slug'])" variant="secondary" size="sm" class="mt-6 w-full">View Details</x-button>
    @endif
</x-card>
