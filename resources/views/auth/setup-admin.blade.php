<x-layouts.app title="Setup Admin" description="Create the first MK Scholars administrator account.">
    <section class="relative overflow-hidden bg-[#f4f7fb] py-10 sm:py-14 lg:py-16" data-testid="auth-setup-admin-page">
        <div class="absolute inset-x-0 top-0 h-48 bg-mk-navy"></div>
        <div class="mk-container relative">
            <div class="grid overflow-hidden rounded-2xl border border-white/60 bg-white shadow-2xl shadow-mk-navy/10 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="relative overflow-hidden bg-mk-navy p-6 text-white sm:p-8 lg:p-10">
                    <div class="absolute inset-0 opacity-25" aria-hidden="true">
                        <div class="absolute -right-20 top-10 h-72 w-72 rounded-full bg-mk-gold blur-3xl"></div>
                        <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-sky-500 blur-3xl"></div>
                    </div>
                    <div class="relative z-10 flex h-full flex-col justify-between gap-10">
                        <div>
                            <x-brand-logo size="lg" text-class="text-white" tagline-class="text-mk-gold" />
                            <x-badge tone="gold" class="mt-8">First admin setup</x-badge>
                            <h1 class="mt-6 max-w-xl text-4xl font-extrabold tracking-normal text-white sm:text-5xl">Securely create the first administrator</h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-200 sm:text-lg">This one-time setup creates the first platform operator. Once an admin exists, this page is blocked automatically.</p>
                        </div>

                        <div class="space-y-3">
                            @foreach ([
                                ['title' => 'One-time setup', 'text' => 'Available only before an admin account exists.'],
                                ['title' => 'Protected access', 'text' => 'Admin dashboards remain role-restricted.'],
                                ['title' => 'No setup secrets exposed', 'text' => 'Use a private administrator email and strong password.'],
                            ] as $item)
                                <div class="flex gap-3 rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-mk-gold text-mk-navy" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    <div>
                                        <p class="text-sm font-extrabold text-white">{{ $item['title'] }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-300">{{ $item['text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex items-center bg-gradient-to-br from-white via-white to-mk-cream p-5 sm:p-8 lg:p-10">
                    <div class="w-full">
                        <livewire:setup-admin-form />
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
