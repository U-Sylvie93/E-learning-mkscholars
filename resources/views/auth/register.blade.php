<x-layouts.app title="Register" description="Create an MK Scholars account.">
    <section class="relative overflow-hidden bg-[#f4f7fb] py-10 sm:py-14 lg:py-16" data-testid="auth-register-page">
        <div class="absolute inset-x-0 top-0 h-48 bg-mk-navy"></div>
        <div class="mk-container relative">
            <div class="grid overflow-hidden rounded-2xl border border-white/60 bg-white shadow-2xl shadow-mk-navy/10 lg:grid-cols-[1fr_1fr]">
                <div class="relative overflow-hidden bg-mk-navy p-6 text-white sm:p-8 lg:p-10">
                    <div class="absolute inset-0 opacity-25" aria-hidden="true">
                        <div class="absolute left-1/3 top-0 h-72 w-72 rounded-full bg-mk-gold blur-3xl"></div>
                        <div class="absolute -bottom-20 -left-20 h-72 w-72 rounded-full bg-sky-500 blur-3xl"></div>
                    </div>
                    <div class="relative z-10 flex h-full flex-col justify-between gap-10">
                        <div>
                            <x-brand-logo size="lg" text-class="text-white" tagline-class="text-mk-gold" />
                            <x-badge tone="gold" class="mt-8">Join MK Scholars</x-badge>
                            <h1 class="mt-6 max-w-xl text-4xl font-extrabold tracking-normal text-white sm:text-5xl">Create your MK Scholars account</h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-200 sm:text-lg">Choose your role and enter a guided platform built for academy learning, learning support, assignments, live classes, and verified progress.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ([
                                ['title' => 'Join academies', 'text' => 'Start with clear pathways.'],
                                ['title' => 'Access courses', 'text' => 'Learn with structured modules.'],
                                ['title' => 'Submit assignments', 'text' => 'Get feedback and improve.'],
                                ['title' => 'Earn certificates', 'text' => 'Show verified progress.'],
                            ] as $item)
                                <div class="rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-mk-gold text-mk-navy" aria-hidden="true">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6v12M6 12h12" stroke-linecap="round"/><path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/></svg>
                                    </span>
                                    <p class="mt-3 text-sm font-extrabold text-white">{{ $item['title'] }}</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-300">{{ $item['text'] }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-3 shadow-xl backdrop-blur">
                            <img src="{{ asset('images/marketing/academy-learning.webp') }}" alt="MK Scholars academy learning" class="h-52 w-full rounded-xl object-cover sm:h-60">
                        </div>
                    </div>
                </div>

                <div class="flex items-center bg-gradient-to-br from-white via-white to-mk-cream p-5 sm:p-8 lg:p-10">
                    <div class="w-full">
                        <livewire:register-form />
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

