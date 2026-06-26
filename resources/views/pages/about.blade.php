<x-layouts.app title="About">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <x-section-header
                    eyebrow="About"
                    title="Built for disciplined, hopeful learning"
                    description="MK Scholars is positioned as a student-focused platform where academic support, digital learning, and opportunity guidance work together."
                />
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <x-button :href="route('courses')">Browse courses</x-button>
                    <x-button :href="route('contact')" variant="secondary">Talk to us</x-button>
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-card>
                    <p class="text-3xl font-extrabold text-mk-navy">01</p>
                    <h3 class="mt-4 font-bold text-mk-navy">Academic clarity</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Clear paths, steady practice, and visible goals for every learner.</p>
                </x-card>
                <x-card>
                    <p class="text-3xl font-extrabold text-mk-navy">02</p>
                    <h3 class="mt-4 font-bold text-mk-navy">Mentor energy</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">A foundation for coaching, feedback, and accountable progress.</p>
                </x-card>
                <x-card class="sm:col-span-2">
                    <p class="text-3xl font-extrabold text-mk-navy">03</p>
                    <h3 class="mt-4 font-bold text-mk-navy">Opportunity mindset</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">A platform structure that can grow into scholarships, events, and student communities.</p>
                </x-card>
            </div>
        </div>
    </section>
</x-layouts.app>
