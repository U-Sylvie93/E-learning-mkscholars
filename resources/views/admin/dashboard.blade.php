<x-layouts.app title="Admin" description="MK Scholars protected admin placeholder.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header
                eyebrow="Admin"
                title="MK Scholars admin dashboard"
                description="A protected admin-only placeholder for platform setup, role verification, and future operations."
            />

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <x-card>
                    <x-badge>Platform</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Access verified</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Only users with the admin role can reach this dashboard.</p>
                </x-card>
                <x-card>
                    <x-badge tone="blue">Users</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Role foundation</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Student, instructor, mentor, and admin access paths are separated.</p>
                </x-card>
                <x-card>
                    <x-badge tone="green">Operations</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Future admin area</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Filament and full platform management will be added in a later phase.</p>
                </x-card>
            </div>
        </div>
    </section>
</x-layouts.app>
