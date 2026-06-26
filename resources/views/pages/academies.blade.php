<x-layouts.app title="Academies">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <x-badge tone="gold">Academies</x-badge>
                <h1 class="mt-5 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Choose a pathway, then build momentum</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">Academies organize MK Scholars into clear student goals: technical skills, language growth, test preparation, careers, and study abroad readiness.</p>
            </div>
            <div class="relative min-h-72 overflow-hidden rounded-lg border border-slate-200 bg-mk-navy shadow-soft">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(245,185,66,0.30),transparent_28%),linear-gradient(135deg,#0B1F3A_0%,#123B63_58%,#0B1F3A_100%)]"></div>
                <div class="absolute inset-0 opacity-20 [background-image:linear-gradient(135deg,rgba(255,255,255,.18)_1px,transparent_1px)] [background-size:20px_20px]"></div>
                <div class="relative grid min-h-72 content-center gap-4 p-6 sm:grid-cols-2">
                    @foreach ([\App\Models\Academy::ICON_CODE, \App\Models\Academy::ICON_LANGUAGE, \App\Models\Academy::ICON_GRADUATION_CAP, \App\Models\Academy::ICON_TARGET] as $icon)
                        <div class="rounded-lg border border-white/10 bg-white/10 p-4 text-white backdrop-blur">
                            <x-academy-icon :name="$icon" class="h-8 w-8 text-mk-gold" />
                            <p class="mt-3 text-sm font-bold">{{ \App\Models\Academy::iconOptions()[$icon] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($academies as $academy)
                <x-academy-card :academy="$academy" />
            @endforeach
        </div>
    </section>
</x-layouts.app>

