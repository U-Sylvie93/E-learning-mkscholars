<x-layouts.app title="Login" description="Sign in to your MK Scholars learning dashboard.">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
            <div>
                <x-brand-logo size="lg" class="mb-8" />
                <x-badge>Welcome back</x-badge>
                <h1 class="mt-6 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Sign in to your learning space.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                    Access the right MK Scholars dashboard for your role, with a focused place for learning, mentoring, or teaching.
                </p>
            </div>

            <livewire:login-form />
        </div>
    </section>
</x-layouts.app>
