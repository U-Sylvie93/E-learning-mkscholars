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
            ['label' => 'Dashboard', 'route' => 'student.dashboard', 'icon' => 'dashboard'],
            ['label' => 'My Courses', 'route' => 'student.my-courses', 'icon' => 'courses'],
            ['label' => 'Assignments', 'route' => 'student.assignments', 'icon' => 'assignments'],
            ['label' => 'Certificates', 'route' => 'student.certificates', 'icon' => 'certificates'],
            ['label' => 'Payments', 'route' => 'student.payments', 'icon' => 'payments'],
            ['label' => 'Documents', 'route' => 'student.documents', 'icon' => 'documents'],
            ['label' => 'Live Classes', 'route' => 'student.live-classes', 'icon' => 'live'],
            ['label' => 'Notifications', 'route' => 'student.notifications', 'icon' => 'notifications'],
            ['label' => 'Settings', 'route' => 'student.settings', 'icon' => 'settings'],
        ],
        'instructor' => [
            ['label' => 'Dashboard', 'route' => 'instructor.dashboard', 'icon' => 'dashboard'],
            ['label' => 'My Courses', 'route' => 'instructor.courses.index', 'icon' => 'courses'],
            ['label' => 'Live Classes', 'route' => 'instructor.live-classes.index', 'icon' => 'live'],
            ['label' => 'Notifications', 'route' => 'instructor.notifications', 'icon' => 'notifications'],
            ['label' => 'Settings', 'route' => 'instructor.settings', 'icon' => 'settings'],
        ],
    ];

    $navItems = collect($navByRole[$role] ?? [])
        ->filter(fn (array $item): bool => \Illuminate\Support\Facades\Route::has($item['route']))
        ->values();

    $notificationRoute = $navItems->firstWhere('label', 'Notifications')['route'] ?? null;
    $unreadNotificationsCount = $user ? app(\App\Services\AppNotificationService::class)->unreadCount($user) : 0;
    $unreadNotificationsDisplay = $unreadNotificationsCount > 99 ? '99+' : (string) $unreadNotificationsCount;
    $latestNotifications = $user && $notificationRoute ? app(\App\Services\AppNotificationService::class)->visibleFor($user)->take(5)->get() : collect();
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
            [data-testid="dashboard-shell"] {
                --mk-sidebar-width: 18rem;
            }

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

            @media (min-width: 1024px) {
                [data-testid="dashboard-shell"] {
                    grid-template-columns: var(--mk-sidebar-width) minmax(0, 1fr);
                    transition: grid-template-columns 180ms ease;
                }

                [data-sidebar-collapsed="true"] {
                    --mk-sidebar-width: 5.25rem;
                }

                [data-sidebar-collapsed="true"] .mk-sidebar-label,
                [data-sidebar-collapsed="true"] .mk-sidebar-brand-text,
                [data-sidebar-collapsed="true"] .mk-sidebar-role,
                [data-sidebar-collapsed="true"] .mk-sidebar-logout-label {
                    display: none;
                }

                [data-sidebar-collapsed="true"] .mk-sidebar-nav-item,
                [data-sidebar-collapsed="true"] .mk-sidebar-logout-button {
                    justify-content: center;
                    padding-left: 0.75rem;
                    padding-right: 0.75rem;
                }
            }

            @media (max-width: 640px) {
                .mk-dashboard-content .bg-white.py-16 {
                    padding: 1rem !important;
                }

                .mk-dashboard-content table {
                    min-width: 40rem;
                }
            }
        </style>
    </head>
    <body class="overflow-x-hidden bg-slate-100 text-slate-800 antialiased">
        <div class="min-h-screen lg:grid" data-testid="dashboard-shell" data-sidebar-collapsed="false">
            <aside class="hidden bg-mk-navy text-white shadow-2xl shadow-mk-navy/20 lg:flex lg:flex-col" data-testid="dashboard-sidebar">
                <div class="border-b border-white/10 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0 mk-sidebar-brand-text">
                            <x-brand-logo text-class="text-white" tagline-class="text-mk-gold" />
                        </div>
                        <button type="button" id="dashboard-sidebar-toggle" data-testid="dashboard-sidebar-toggle" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/15 text-slate-200 transition hover:border-mk-gold hover:bg-white/10 hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold" aria-label="Toggle dashboard sidebar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mk-sidebar-role mt-4 rounded-full border border-mk-gold/40 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $roleLabel }} workspace</p>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="{{ $roleLabel }} navigation">
                    @foreach ($navItems as $item)
                        @php($isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            data-testid="dashboard-nav-item"
                            @if ($isActive) aria-current="page" @endif
                            class="mk-sidebar-nav-item flex items-center justify-between gap-3 rounded-xl px-3 py-3 text-sm font-bold transition {{ $isActive ? 'bg-mk-gold text-mk-navy shadow-sm' : 'text-slate-200 hover:bg-white/10 hover:text-white' }}"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span data-testid="dashboard-sidebar-collapsed-icon" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $isActive ? 'bg-mk-navy/10' : 'bg-white/10' }}">
                                    <x-dashboard-icon :name="$item['icon']" class="h-5 w-5" />
                                </span>
                                <span class="mk-sidebar-label truncate">{{ $item['label'] }}</span>
                            </span>
                            @if ($item['label'] === 'Notifications' && $unreadNotificationsCount > 0)
                                <span data-testid="notification-badge" class="mk-sidebar-label inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-mk-gold px-2 py-0.5 text-xs font-black text-mk-navy ring-1 ring-white/40">{{ $unreadNotificationsDisplay }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-white/10 p-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mk-sidebar-logout-button flex w-full items-center gap-3 rounded-xl border border-white/15 px-3 py-3 text-left text-sm font-bold text-slate-200 transition hover:bg-white/10 hover:text-white">
                            <span data-testid="dashboard-sidebar-collapsed-icon" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="mk-sidebar-logout-label">Logout</span>
                        </button>
                    </form>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" data-testid="dashboard-topbar">
                    <div class="flex min-h-16 items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <details class="relative lg:hidden" data-testid="dashboard-mobile-drawer">
                                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy shadow-sm marker:hidden">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
                                    Menu
                                </summary>
                                <div class="absolute left-0 top-12 z-40 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-3 shadow-xl">
                                    <div class="mb-3 border-b border-slate-100 pb-3">
                                        <x-brand-logo size="sm" />
                                    </div>
                                    <nav class="grid gap-1" aria-label="Mobile {{ $roleLabel }} navigation">
                                        @foreach ($navItems as $item)
                                            @php($isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'))
                                            <a
                                                href="{{ route($item['route']) }}"
                                                data-testid="dashboard-nav-item"
                                                @if ($isActive) aria-current="page" @endif
                                                class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm font-bold {{ $isActive ? 'bg-mk-goldSoft text-mk-navy' : 'text-slate-600 hover:bg-slate-50 hover:text-mk-navy' }}"
                                            >
                                                <span class="flex items-center gap-3">
                                                    <span data-testid="dashboard-sidebar-collapsed-icon" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-mk-navy">
                                                        <x-dashboard-icon :name="$item['icon']" class="h-5 w-5" />
                                                    </span>
                                                    <span>{{ $item['label'] }}</span>
                                                </span>
                                                @if ($item['label'] === 'Notifications' && $unreadNotificationsCount > 0)
                                                    <span data-testid="notification-badge" class="inline-flex min-w-6 shrink-0 items-center justify-center rounded-full bg-mk-gold px-2 py-0.5 text-xs font-black text-mk-navy">{{ $unreadNotificationsDisplay }}</span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </nav>
                                    <form method="POST" action="{{ route('logout') }}" class="mt-3 border-t border-slate-100 pt-3">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-left text-sm font-bold text-mk-navy transition hover:border-mk-gold hover:bg-mk-goldSoft">Logout</button>
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
                                <details class="relative" data-testid="notification-menu">
                                    <summary class="relative inline-flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-mk-navy shadow-sm transition marker:hidden hover:border-mk-gold hover:bg-mk-goldSoft" aria-label="Notifications{{ $unreadNotificationsCount ? ' - '.$unreadNotificationsCount.' unread' : '' }}">
                                        <x-dashboard-icon name="notifications" class="h-5 w-5" />
                                        <span class="hidden sm:inline">Notifications</span>
                                        @if ($unreadNotificationsCount > 0)
                                            <span data-testid="notification-badge" class="absolute -right-2 -top-2 inline-flex min-w-5 items-center justify-center rounded-full bg-mk-gold px-1.5 py-0.5 text-xs font-black text-mk-navy ring-2 ring-white">{{ $unreadNotificationsDisplay }}</span>
                                        @endif
                                    </summary>
                                    <div class="absolute right-0 top-12 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-xl border border-slate-200 bg-white p-3 shadow-2xl shadow-mk-navy/15">
                                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                            <p class="text-sm font-extrabold text-mk-navy">Latest notifications</p>
                                            @if ($unreadNotificationsCount > 0)
                                                <span class="rounded-full bg-mk-goldSoft px-2 py-1 text-xs font-black text-mk-navy">{{ $unreadNotificationsDisplay }} unread</span>
                                            @endif
                                        </div>
                                        <div class="mt-3 grid gap-2">
                                            @forelse ($latestNotifications as $notification)
                                                <a href="{{ $notification->action_url ?: route($notificationRoute) }}" class="block rounded-lg border {{ $notification->isUnread() ? 'border-mk-gold bg-mk-goldSoft/40' : 'border-slate-100 bg-slate-50' }} p-3 transition hover:border-mk-gold hover:bg-white">
                                                    <p class="line-clamp-1 text-sm font-bold text-mk-navy">{{ $notification->title }}</p>
                                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-600">{{ $notification->message }}</p>
                                                    <p class="mt-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $notification->created_at?->diffForHumans() }}</p>
                                                </a>
                                            @empty
                                                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                                                    <p class="text-sm font-bold text-mk-navy">No notifications yet</p>
                                                    <p class="mt-1 text-xs leading-5 text-slate-500">Updates and reminders will appear here.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                        <a href="{{ route($notificationRoute) }}" class="mt-3 flex items-center justify-center rounded-lg bg-mk-navy px-4 py-2 text-sm font-bold text-white transition hover:bg-mk-blue">View all notifications</a>
                                    </div>
                                </details>
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
        <script>
            (() => {
                const shell = document.querySelector('[data-testid="dashboard-shell"]');
                const toggle = document.querySelector('[data-testid="dashboard-sidebar-toggle"]');
                const storageKey = 'mk-dashboard-sidebar-collapsed';

                if (! shell || ! toggle) {
                    return;
                }

                const applyState = (collapsed) => {
                    shell.dataset.sidebarCollapsed = collapsed ? 'true' : 'false';
                    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                };

                applyState(localStorage.getItem(storageKey) === 'true');

                toggle.addEventListener('click', () => {
                    const collapsed = shell.dataset.sidebarCollapsed !== 'true';
                    localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
                    applyState(collapsed);
                });
            })();
        </script>
    </body>
</html>
