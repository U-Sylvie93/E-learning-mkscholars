<x-layouts.app title="Setup Admin" description="Create the first MK Scholars administrator account.">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
            <div>
                <x-badge>First admin setup</x-badge>
                <h1 class="mt-6 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Create the first admin account.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">
                    This setup page is only available until one admin user exists. After that, access is blocked to protect the platform.
                </p>
            </div>

            <livewire:setup-admin-form />
        </div>
    </section>
</x-layouts.app>
