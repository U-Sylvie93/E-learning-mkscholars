@props(['course'])

@php
    $image = $course['image'] ?? null;
    $lessonsCount = $course['lessons_count'] ?? null;
    $academyIcon = $course['academy_icon'] ?? \App\Models\Academy::ICON_BOOK_OPEN;
@endphp

<x-card class="group flex h-full flex-col overflow-hidden rounded-[1.75rem] border-slate-200 bg-white p-0 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-soft" data-testid="course-card">
    <div class="relative aspect-[16/10] overflow-hidden bg-mk-navy">
        @if ($image)
            <img class="h-full w-full object-cover transition duration-500 group-hover:scale-105" src="{{ $image }}" alt="{{ $course['title'] }}">
            <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/82 via-mk-navy/16 to-transparent"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_34%),linear-gradient(135deg,#073653_0%,#0e4a72_56%,#102a3a_100%)]"></div>
            <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(135deg,rgba(255,255,255,.18)_1px,transparent_1px)] [background-size:18px_18px]"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="flex h-20 w-20 items-center justify-center rounded-[1.5rem] border border-mk-gold/40 bg-white/10 text-mk-gold shadow-soft backdrop-blur">
                    <x-academy-icon :name="$academyIcon" class="h-10 w-10" />
                </span>
            </div>
        @endif
        <div class="absolute left-4 top-4 flex max-w-[calc(100%-2rem)] flex-wrap gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/92 px-3 py-1.5 text-xs font-extrabold text-mk-navy shadow-soft backdrop-blur">
                <x-academy-icon :name="$academyIcon" class="h-4 w-4 text-mk-gold" />
                {{ $course['academy'] }}
            </span>
        </div>
        <div class="absolute bottom-4 left-4 right-4 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-mk-gold px-3 py-1.5 text-xs font-extrabold text-mk-navy shadow-soft"><x-public-icon name="certificate" class="h-3.5 w-3.5" />Certificate</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/92 px-3 py-1.5 text-xs font-extrabold text-mk-navy shadow-soft"><x-public-icon name="book" class="h-3.5 w-3.5" />{{ $course['price'] }}</span>
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
        <h3 class="mt-5 line-clamp-2 break-words text-xl font-black tracking-normal text-mk-navy">{{ $course['title'] }}</h3>
        <p class="mt-3 flex-1 text-sm leading-7 text-slate-600">{{ $course['summary'] }}</p>
        <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-100 pt-5">
            <span class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-wide text-mk-gold"><x-public-icon name="chart" class="h-4 w-4" /> Progress path</span>
            <x-button :href="route('courses.show', $course['slug'])" variant="secondary" size="sm">View Course</x-button>
        </div>
    </div>
</x-card>
