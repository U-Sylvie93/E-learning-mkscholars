<x-layouts.app title="Contact">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <x-section-header
                eyebrow="Contact"
                title="Start a learning conversation"
                description="Tell us what kind of academic support, course guidance, or student opportunity you are looking for."
            />
                <div class="mt-8 grid gap-4 text-sm text-slate-600">
                    <p><span class="font-bold text-mk-navy">Email:</span> hello@mkscholars.example</p>
                    <p><span class="font-bold text-mk-navy">Support:</span> Monday to Friday</p>
                    <p><span class="font-bold text-mk-navy">Focus:</span> Academies, courses, mentorship, and student opportunities.</p>
                </div>
            </div>

            <livewire:contact-form />
        </div>
    </section>
</x-layouts.app>
