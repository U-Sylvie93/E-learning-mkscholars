<x-layouts.app title="Register" description="Create an MK Scholars account.">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
            <div>
                <x-brand-logo size="lg" class="mb-8" />
                <x-badge>Join MK Scholars</x-badge>
                <h1 class="mt-6 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Create your account.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                    Start with a student, instructor, or mentor profile. Admin access is reserved for platform operators.
                </p>
            </div>

            <livewire:register-form />
        </div>
    </section>
</x-layouts.app>
