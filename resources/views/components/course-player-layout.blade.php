@props([
    'title',
    'description' => null,
    'course',
    'completedLessonIds' => [],
    'currentLesson' => null,
    'progress' => 0,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'MK Scholars course player.' }}">
        <title>{{ $title }} | {{ config('app.name', 'MK Scholars') }}</title>
        <link rel="icon" type="image/webp" href="{{ asset('favicon.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            [data-testid="player-shell"] { --mk-outline-width: 20rem; }
            @media (min-width: 1024px) {
                [data-testid="player-shell"] {
                    grid-template-columns: var(--mk-outline-width) minmax(0, 1fr);
                }
            }
        </style>
    </head>
    <body class="min-h-screen bg-mk-surface-muted text-slate-800 antialiased">
        <div class="min-h-screen lg:grid" data-testid="player-shell">
            {{-- Desktop outline sidebar: independent scroll, sticky full height --}}
            <aside class="hidden border-r border-slate-200 bg-white lg:sticky lg:top-0 lg:block lg:h-screen" data-testid="learning-sidebar">
                <x-course-outline
                    :course="$course"
                    :completed-lesson-ids="$completedLessonIds"
                    :current-lesson="$currentLesson"
                    :progress="$progress"
                />
            </aside>

            <div class="min-w-0">
                {{-- Top bar --}}
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex min-h-14 items-center justify-between gap-3 px-4 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            {{-- Mobile outline drawer --}}
                            <details class="relative lg:hidden" data-testid="learning-sidebar-toggle">
                                <summary class="mk-focus inline-flex cursor-pointer list-none items-center gap-2 rounded-mk-sm border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy shadow-sm marker:hidden">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                                    Contents
                                </summary>
                                <div class="absolute left-0 top-12 z-40 max-h-[80vh] w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-mk-lg border border-slate-200 bg-white shadow-xl">
                                    <div class="max-h-[80vh] overflow-y-auto">
                                        <x-course-outline
                                            :course="$course"
                                            :completed-lesson-ids="$completedLessonIds"
                                            :current-lesson="$currentLesson"
                                            :progress="$progress"
                                        />
                                    </div>
                                </div>
                            </details>
                            <div class="min-w-0">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-mk-gold">Student dashboard</p>
                                <h1 class="truncate text-sm font-extrabold text-mk-navy sm:text-base">{{ $title }}</h1>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <div class="hidden items-center gap-2 sm:flex">
                                <div class="h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-mk-gold" style="width: {{ $progress }}%"></div>
                                </div>
                                <span class="text-xs font-black text-mk-navy">{{ $progress }}%</span>
                            </div>
                            <a href="{{ route('student.my-courses') }}" class="mk-focus inline-flex items-center gap-2 rounded-mk-sm border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy transition hover:border-mk-gold hover:bg-mk-goldSoft">
                                <span class="hidden sm:inline">Exit player</span>
                                <svg class="h-4 w-4 sm:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/></svg>
                            </a>
                        </div>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6" data-testid="learning-main-content">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
