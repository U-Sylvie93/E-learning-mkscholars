<x-dashboard-layout role="support" title="Support Dashboard" description="MK Scholars support dashboard.">
    <div class="space-y-6">
            <x-section-header
                eyebrow="Support dashboard"
                title="Guidance and growth"
                description="A protected workspace for support sessions, student goals, and learning support."
            />

            <x-card highlighted class="min-w-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <x-badge tone="gold">Support workspace</x-badge>
                        <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">Welcome, {{ auth()->user()->name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Track assigned learners, prepare weekly check-ins, and keep feedback organized in a focused support dashboard.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                        <x-button :href="route('mentor.students')">View Students</x-button>
                        <x-button :href="route('mentor.notifications')" variant="secondary">Notifications</x-button>
                    </div>
                </div>
            </x-card>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <x-card class="min-w-0">
                    <x-badge>Sessions</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $upcomingCheckIns->count() }} upcoming check-ins</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Review scheduled sessions and add feedback after each weekly check-in.</p>
                    <x-button :href="route('mentor.check-ins')" size="sm" class="mt-5">View Check-ins</x-button>
                </x-card>
                <x-card class="min-w-0">
                    <x-badge tone="blue">Students</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $assignments->count() }} assigned learners</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">See students assigned to you and the course context for each learning support relationship.</p>
                    <x-button :href="route('mentor.students')" size="sm" variant="secondary" class="mt-5">View Students</x-button>
                </x-card>
                <x-card class="min-w-0">
                    <x-badge tone="green">Goals</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Readiness support</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Scholarship, leadership, and career guidance workflows can live here.</p>
                </x-card>
                <x-card class="min-w-0">
                    <x-badge tone="gold">Notifications</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Updates</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Review check-in reminders and platform messages.</p>
                    <x-button :href="route('mentor.notifications')" size="sm" class="mt-5">Open Notifications</x-button>
                </x-card>
            </div>
            </div>
</x-dashboard-layout>



