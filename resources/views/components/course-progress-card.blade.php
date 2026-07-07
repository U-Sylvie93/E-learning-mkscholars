@props([
    'course',
    'href',
    'progress' => null,
    'status' => null,
    'academyName' => null,
    'actionLabel' => 'Continue Learning',
    'actionVariant' => 'primary',
])

@php
    $image = $course->coverImageUrl();
    $academy = $academyName ?? ($course->academy?->name ?? 'MK Scholars');
    $academyIcon = $course->academy?->safeIcon() ?? \App\Models\Academy::ICON_BOOK_OPEN;
    $hasProgress = $progress !== null;
@endphp

<x-card {{ $attributes->class(['group flex h-full flex-col overflow-hidden p-0']) }} data-testid="course-progress-card">
    <a href="{{ $href }}" class="relative block aspect-[16/9] overflow-hidden bg-mk-navy mk-focus" aria-label="{{ $course->title }}">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <span class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_38%),linear-gradient(135deg,#073653_0%,#0e4a72_60%,#102a3a_100%)]"></span>
            <span class="absolute inset-0 flex items-center justify-center text-mk-gold">
                <x-academy-icon :name="$academyIcon" class="h-12 w-12" />
            </span>
        @endif
        @if ($status)
            <span class="absolute right-3 top-3"><x-status-badge :status="$status" /></span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-5">
        <p class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-mk-gold">
            <x-academy-icon :name="$academyIcon" class="h-4 w-4" />{{ $academy }}
        </p>
        <h3 class="mt-2 line-clamp-2 break-words text-lg font-black tracking-normal text-mk-navy">
            <a href="{{ $href }}" class="mk-focus rounded-sm hover:text-mk-blue">{{ $course->title }}</a>
        </h3>

        @if (isset($meta))
            <div class="mt-3 flex flex-wrap items-center gap-2">{{ $meta }}</div>
        @endif

        @if ($hasProgress)
            <div class="mt-4">
                <div class="flex items-center justify-between text-xs font-bold">
                    <span class="text-slate-500">Progress</span>
                    <span class="text-mk-navy">{{ $progress }}%</span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-mk-gold transition-[width] duration-500" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @endif

        @isset($stats)
            <div class="mt-4 grid grid-cols-3 gap-2 rounded-mk-md border border-slate-100 bg-slate-50 p-3 text-center">
                {{ $stats }}
            </div>
        @endisset

        <div class="mt-auto flex flex-wrap gap-2 pt-5">
            <x-button :href="$href" :variant="$actionVariant" size="sm" class="flex-1">{{ $actionLabel }}</x-button>
            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    </div>
</x-card>
