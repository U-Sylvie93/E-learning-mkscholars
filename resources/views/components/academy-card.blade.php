@props(['academy'])

@php
    $image = $academy['image'] ?? null;
    $icon = $academy['icon'] ?? \App\Models\Academy::ICON_BOOK_OPEN;
    $iconLabel = $academy['level'] ?? $academy['icon_label'] ?? 'Academy';
@endphp

<x-card class="group flex h-full flex-col overflow-hidden p-0 transition hover:-translate-y-1 hover:shadow-soft">
    <div class="relative aspect-[16/9] overflow-hidden bg-mk-navy">
        @if ($image)
            <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="{{ $image }}" alt="{{ $academy['name'] }}">
            <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/75 via-mk-navy/15 to-transparent"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(245,185,66,0.28),transparent_34%),linear-gradient(135deg,#0B1F3A_0%,#123B63_55%,#0B1F3A_100%)]"></div>
            <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(135deg,rgba(255,255,255,.18)_1px,transparent_1px)] [background-size:18px_18px]"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-2xl border border-mk-gold/40 bg-white/10 text-mk-gold shadow-soft backdrop-blur">
                    <x-academy-icon :name="$icon" class="h-10 w-10" />
                </span>
            </div>
        @endif

        <div class="absolute bottom-4 left-4 flex max-w-[calc(100%-2rem)] items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-white/20 bg-white text-mk-navy shadow-soft">
                <x-academy-icon :name="$icon" class="h-6 w-6" />
            </span>
            <x-badge tone="gold">{{ $iconLabel }}</x-badge>
        </div>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <div class="flex items-start justify-between gap-4">
            <h3 class="text-xl font-bold text-mk-navy">{{ $academy['name'] }}</h3>
            <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $academy['students'] }}</span>
        </div>
        <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ $academy['summary'] }}</p>
        <x-button :href="route('courses', ['academy' => $academy['slug'] ?? null])" variant="secondary" size="sm" class="mt-6">View Courses</x-button>
    </div>
</x-card>
