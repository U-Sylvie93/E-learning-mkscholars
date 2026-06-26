@props([
    'role',
    'title',
    'description' => null,
])

@php
    $user = auth()->user();
    $roleLabel = str($role)->headline()->toString();
    $initial = str($user?->name ?? 'MK')->substr(0, 1)->upper();

    $navByRole = [
        'student' => [
            ['label' => 'Dashboard', 'route' => 'student.dashboard'],
            ['label' => 'My Courses', 'route' => 'student.my-courses'],
            ['label' => 'Subscriptions', 'route' => 'student.subscriptions'],
            ['label' => 'Payments', 'route' => 'student.payments'],
            ['label' => 'Assignments', 'route' => 'student.assignments'],
            ['label' => 'Certificates', 'route' => 'student.certificates'],
            ['label' => 'Opportunities', 'route' => 'student.opportunities'],
            ['label' => 'Applications', 'route' => 'student.applications'],
            ['label' => 'Documents', 'route' => 'student.documents'],
            ['label' => 'Mentorship', 'route' => 'student.mentorship'],
            ['label' => 'Live Classes', 'route' => 'student.live-classes'],
            ['label' => 'Notifications', 'route' => 'student.notifications'],
            ['label' => 'Settings', 'route' => 'student.settings'],
        ],
        'instructor' => [
            ['label' => 'Dashboard', 'route' => 'instructor.dashboard'],
            ['label' => 'My Courses', 'route' => 'instructor.courses.index'],
            ['label' => 'Live Classes', 'route' => 'instructor.live-classes.index'],
            ['label' => 'Notifications', 'route' => 'instructor.notifications'],
            ['label' => 'Settings', 'route' => 'instructor.settings'],
        ],
        'mentor' => [
            ['label' => 'Dashboard', 'route' => 'mentor.dashboard'],
            ['label' => 'Assigned Students', 'route' => 'mentor.students'],
            ['label' => 'Check-ins', 'route' => 'mentor.check-ins'],
            ['label' => 'Notifications', 'route' => 'mentor.notifications'],
            ['label' => 'Settings', 'route' => 'mentor.settings'],
        ],
    ];

    $navItems = collect($navByRole[$role] ?? [])
        ->filter(fn (array $item): bool => \Illuminate\Support\Facades\Route::has($item['route']))
        ->values();

    $notificationRoute = $navItems->firstWhere('label', 'Notifications')['route'] ?? null;
    $unreadNotificationsCount = $user ? app(\App\Services\AppNotificationService::class)->unreadCount($user) : 0;
    $unreadNotificationsDisplay = $unreadNotificationsCount > 99 ? '99+' : (string) $unreadNotificationsCount;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="{{ $description ?? 'MK Scholars dashboard.' }}">
        <title>{{ $title }} | {{ config('app.name', 'MK Scholars') }}</title>
        <link rel="icon" type="image/webp" href="{{ asset('favicon.webp') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/mk-scholars-logo.webp') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>
            .mk-dashboard-content {
                overflow-x: clip;
            }

            .mk-dashboard-content .mk-container {
                width: 100%;
                max-width: none;
                padding-left: 0;
                padding-right: 0;
            }

            .mk-dashboard-content > section + section,
            .mk-dashboard-content > div + section,
            .mk-dashboard-content > section + div {
                margin-top: 1.5rem;
            }

            .mk-dashboard-content .py-16 {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            .mk-dashboard-content .bg-white.py-16 {
                border: 1px solid rgb(226 232 240);
                border-radius: 0.75rem;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
                padding: 1.5rem !important;
            }

            .mk-dashboard-content .overflow-x-auto {
                border-radius: 0.75rem;
            }

            .mk-dashboard-content table {
                min-width: 44rem;
            }

            .mk-dashboard-content h1,
            .mk-dashboard-content h2,
            .mk-dashboard-content h3,
            .mk-dashboard-content p,
            .mk-dashboard-content a,
            .mk-dashboard-content td,
            .mk-dashboard-content th {
                overflow-wrap: anywhere;
            }

            .mk-dashboard-content img,
            .mk-dashboard-content video,
            .mk-dashboard-content iframe {
                max-width: 100%;
            }

            @media (max-width: 640px) {
                .mk-dashboard-content .bg-white.py-16 {
                    padding: 1rem !important;
                }

                .mk-dashboard-content table {
                    min-width: 40rem;
                }
            }
        </style></head>
    <body class="overflow-x-hidden bg-slate-100 text-slate-800 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]" data-testid="dashboard-shell">
            <aside class="hidden bg-mk-navy text-white lg:flex lg:flex-col" data-testid="dashboard-sidebar">
                <div class="border-b border-white/10 p-6">
                    <x-brand-logo text-class="text-white" tagline-class="text-mk-gold" />
                    <p class="mt-5 rounded-full border border-mk-gold/40 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $roleLabel }} workspace</p>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="{{ $roleLabel }} navigation">
                    @foreach ($navItems as $item)
                        @php($isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($isActive) aria-current="page" @endif
                            class="flex items-center justify-between gap-3 rounded-lg px-4 py-3 text-sm font-bold transition {{ $isActive ? 'bg-mk-gold text-mk-navy shadow-sm' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}"
                        >
                            <span>{{ $item['label'] }}</span>
                            @if ($item['label'] === 'Notifications' && $unreadNotificationsCount > 0)
                                <span data-testid="notification-badge" class="inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-mk-gold px-2 py-0.5 text-xs font-black text-mk-navy ring-1 ring-white/40">{{ $unreadNotificationsDisplay }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-white/10 p-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-lg border border-white/15 px-4 py-3 text-left text-sm font-bold text-slate-200 transition hover:bg-white/10 hover:text-white">Logout</button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" data-testid="dashboard-topbar">
                    <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <details class="relative lg:hidden">
                                <summary class="cursor-pointer list-none rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy shadow-sm marker:hidden">Menu</summary>
                                <div class="absolute left-0 top-12 z-40 w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-slate-200 bg-white p-3 shadow-xl">
                                    <div class="mb-3 border-b border-slate-100 pb-3">
                                        <x-brand-logo size="sm" />
                                    </div>
                                    <nav class="grid gap-1" aria-label="Mobile {{ $roleLabel }} navigation">
                                        @foreach ($navItems as $item)
                                            @php($isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                                            <a
                                                href="{{ route($item['route']) }}"
                                                @if ($isActive) aria-current="page" @endif
                                                class="flex items-center justify-between gap-3 rounded-md px-3 py-2 text-sm font-bold {{ $isActive ? 'bg-mk-goldSoft text-mk-navy' : 'text-slate-600 hover:bg-slate-50 hover:text-mk-navy' }}"
                                            >
                                                <span>{{ $item['label'] }}</span>
                                                @if ($item['label'] === 'Notifications' && $unreadNotificationsCount > 0)
                                                    <span data-testid="notification-badge" class="inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-mk-gold px-2 py-0.5 text-xs font-black text-mk-navy">{{ $unreadNotificationsDisplay }}</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </nav>
                                    <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-slate-100 pt-3">
                                        @csrf
                                        <button type="submit" class="w-full rounded-md border border-slate-200 px-3 py-2 text-left text-sm font-bold text-mk-navy transition hover:border-mk-gold hover:bg-mk-goldSoft">Logout</button>
                                    </form>
                                </div>
                            </details>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $roleLabel }} dashboard</p>
                                <h1 class="truncate text-lg font-extrabold text-mk-navy sm:text-xl">{{ $title }}</h1>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                            @if ($notificationRoute)
                                <a href="{{ route($notificationRoute) }}" class="relative inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy transition hover:border-mk-gold hover:bg-mk-goldSoft" aria-label="Notifications{{ $unreadNotificationsCount ? ' - '.$unreadNotificationsCount.' unread' : '' }}">
                                    <span class="hidden sm:inline">Notifications</span>
                                    <span class="sm:hidden">Bell</span>
                                    @if ($unreadNotificationsCount > 0)
                                        <span data-testid="notification-badge" class="absolute -right-2 -top-2 inline-flex min-w-5 items-center justify-center rounded-full bg-mk-gold px-1.5 py-0.5 text-xs font-black text-mk-navy ring-2 ring-white">{{ $unreadNotificationsDisplay }}</span>
                                    @endif
                                </a>
                            @endif
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-mk-navy text-sm font-extrabold text-mk-gold" aria-label="{{ $user?->name ?? 'MK Scholars user' }}">{{ $initial }}</div>
                        </div>
                    </div>
                </header>

                <main class="mk-dashboard-content mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
