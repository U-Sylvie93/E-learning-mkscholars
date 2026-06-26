@props(['course'])

@php
    $image = $course['image'] ?? 'https://images.unsplash.com/photo-1523580846011-d3a5bc25702b?auto=format&fit=crop&w=1200&q=85';
    $lessonsCount = $course['lessons_count'] ?? null;
    $academyIcon = $course['academy_icon'] ?? \App\Models\Academy::ICON_BOOK_OPEN;
@endphp

<x-card class="group flex h-full flex-col overflow-hidden p-0">
    <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
        <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="{{ $image }}" alt="{{ $course['title'] }}">
        <div class="absolute left-4 top-4 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-mk-navy px-3 py-1 text-xs font-bold text-white shadow-soft">
                <x-academy-icon :name="$academyIcon" class="h-4 w-4 text-mk-gold" />
                {{ $course['academy'] }}
            </span>
        </div>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="blue">{{ $course['level'] }}</x-badge>
            <x-badge tone="gray">{{ $course['duration'] }}</x-badge>
            @if ($lessonsCount)
                <x-badge tone="gray">{{ $lessonsCount }} lessons</x-badge>
            @endif
        </div>
        <h3 class="mt-5 break-words text-xl font-bold text-mk-navy">{{ $course['title'] }}</h3>
        <p class="mt-3 flex-1 text-sm leading-6 text-slate-600">{{ $course['summary'] }}</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <x-badge tone="green">Certificate</x-badge>
            <x-badge tone="gold">{{ $course['price'] }}</x-badge>
        </div>
        <x-button :href="route('courses.show', $course['slug'])" class="mt-6 w-full" variant="secondary" size="sm">View Course</x-button>
    </div>
</x-card>
