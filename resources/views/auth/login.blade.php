<x-layouts.app title="Login" description="Sign in to your MK Scholars learning dashboard.">
    <section class="relative overflow-hidden bg-[#f4f7fb] py-10 sm:py-14 lg:py-16" data-testid="auth-login-page">
        <div class="absolute inset-x-0 top-0 h-48 bg-mk-navy"></div>
        <div class="mk-container relative">
            <div class="grid overflow-hidden rounded-2xl border border-white/60 bg-white shadow-2xl shadow-mk-navy/10 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="relative min-h-[34rem] overflow-hidden bg-mk-navy p-6 text-white sm:p-8 lg:p-10">
                    <div class="absolute inset-0 opacity-25" aria-hidden="true">
                        <div class="absolute -left-20 top-10 h-64 w-64 rounded-full bg-mk-gold blur-3xl"></div>
                        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-sky-500 blur-3xl"></div>
                    </div>

                    <div class="relative z-10 flex h-full flex-col justify-between gap-10">
                        <div>
                            <x-brand-logo size="lg" text-class="text-white" tagline-class="text-mk-gold" />
                            <x-badge tone="gold" class="mt-8">Secure sign in</x-badge>
                            <h1 class="mt-6 max-w-xl text-4xl font-extrabold tracking-normal text-white sm:text-5xl">Welcome back to your learning space</h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-200 sm:text-lg">Continue your courses, live classes, learning support, certificates, and progress from the right MK Scholars workspace.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            @foreach ([
                                ['title' => 'Continue learning', 'text' => 'Return to your lessons and next actions.'],
                                ['title' => 'Track progress', 'text' => 'See completion, quizzes, and certificates.'],
                                ['title' => 'Access dashboard', 'text' => 'Students, instructors, supports, and admins.'],
                            ] as $item)
                                <div class="rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-mk-gold text-mk-navy" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12l4 4L19 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    <p class="mt-3 text-sm font-extrabold text-white">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-300">{{ $item['text'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-3 shadow-xl backdrop-blur">
                            <img src="{{ asset('images/marketing/student-support.webp') }}" alt="MK Scholars student learning support" class="h-52 w-full rounded-xl object-cover sm:h-60">
                        </div>
                    </div>
                </div>

                <div class="flex items-center bg-gradient-to-br from-white via-white to-mk-cream p-5 sm:p-8 lg:p-10">
                    <div class="w-full">
                        <livewire:login-form />
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

