@php
    $links = [
        ['label' => 'Home', 'route' => 'home'],
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Academies', 'route' => 'academies'],
        ['label' => 'Courses', 'route' => 'courses'],
        ['label' => 'Contact', 'route' => 'contact'],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <nav class="mk-container flex min-h-20 items-center justify-between gap-5 py-3" aria-label="Public navigation">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3 rounded-md mk-focus">
            <x-brand-logo />
        </a>

        <div class="hidden items-center gap-1 xl:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="rounded-md px-3 py-2 text-sm font-semibold transition mk-focus {{ request()->routeIs($link['route']) || ($link['route'] === 'courses' && request()->routeIs('courses.*')) ? 'bg-mk-goldSoft text-mk-navy' : 'text-slate-600 hover:bg-slate-100 hover:text-mk-navy' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <div class="hidden shrink-0 items-center gap-3 xl:flex">
            @guest
                <a href="{{ route('login') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-mk-navy transition mk-focus hover:bg-slate-100">Login</a>
                <a href="{{ route('register') }}" class="rounded-md bg-mk-gold px-4 py-2 text-sm font-semibold text-mk-navy transition mk-focus hover:bg-yellow-300">Register</a>
            @else
                <a href="{{ auth()->user()->dashboardPath() }}" class="rounded-md px-4 py-2 text-sm font-semibold text-mk-navy ring-1 ring-slate-200 transition mk-focus hover:bg-slate-50">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-mk-gold px-4 py-2 text-sm font-semibold text-mk-navy transition mk-focus hover:bg-yellow-300">Logout</button>
                </form>
            @endguest
        </div>

        <details class="relative xl:hidden">
            <summary class="list-none rounded-md border border-slate-200 px-3 py-2 text-sm font-semibold text-mk-navy mk-focus">Menu</summary>
            <div class="absolute right-0 mt-3 w-72 rounded-lg border border-slate-200 bg-white p-2 shadow-soft">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" class="block rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs($link['route']) || ($link['route'] === 'courses' && request()->routeIs('courses.*')) ? 'bg-mk-goldSoft text-mk-navy' : 'text-slate-600 hover:bg-slate-100 hover:text-mk-navy' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach

                <div class="mt-2 grid gap-2 border-t border-slate-100 pt-2">
                    @guest
                        <a href="{{ route('login') }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Login</a>
                        <a href="{{ route('register') }}" class="block rounded-md bg-mk-gold px-3 py-2 text-sm font-semibold text-mk-navy hover:bg-yellow-300">Register</a>
                    @else
                        <a href="{{ auth()->user()->dashboardPath() }}" class="block rounded-md px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Dashboard</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-md bg-mk-gold px-3 py-2 text-left text-sm font-semibold text-mk-navy hover:bg-yellow-300">Logout</button>
                        </form>
                    @endguest
                </div>
            </div>
        </details>
    </nav>
</header>