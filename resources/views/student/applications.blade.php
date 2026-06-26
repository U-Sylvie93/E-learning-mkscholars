<x-dashboard-layout role="student" title="Application Tracker" description="MK Scholars student application tracker.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Applications"
                title="Track every opportunity"
                description="Follow your application status, documents, feedback, and next steps."
            />
            <div class="flex flex-wrap gap-3">
                <x-button :href="route('student.documents')" variant="secondary">Document Center</x-button>
                <x-button :href="route('student.opportunities')" variant="secondary">Browse Opportunities</x-button>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($statuses as $status)
                    <x-card>
                        <x-badge tone="gray">{{ str_replace('_', ' ', $status) }}</x-badge>
                        <p class="mt-4 text-3xl font-extrabold text-mk-navy">{{ $applications->where('status', $status)->count() }}</p>
                    </x-card>
                @endforeach
            </div>

            <div class="mt-8 space-y-10">
                @if ($applications->isEmpty())
                    <x-card>
                        <x-badge tone="gray">Empty</x-badge>
                        <h2 class="mt-5 text-xl font-bold text-mk-navy">No applications yet</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Start from an opportunity and your tracker will appear here.</p>
                        <x-button :href="route('student.opportunities')" class="mt-6">Browse Opportunities</x-button>
                    </x-card>
                @endif

                @foreach ($statuses as $status)
                    @php($statusApplications = $applications->where('status', $status))
                    @continue($statusApplications->isEmpty())

                    <section>
                        <h2 class="text-xl font-extrabold capitalize text-mk-navy">{{ str_replace('_', ' ', $status) }}</h2>
                        <div class="mt-4 grid gap-5">
                            @foreach ($statusApplications as $application)
                                <x-card>
                                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <x-badge tone="blue">{{ str_replace('_', ' ', $application->status) }}</x-badge>
                                                <x-badge tone="gray">{{ $application->documents->count() }} documents</x-badge>
                                                @if ($application->opportunity?->deadline)
                                                    <x-badge :tone="$application->opportunity->deadlineBadgeTone()">{{ $application->opportunity->deadlineBadge() }}</x-badge>
                                                @endif
                                            </div>
                                            <h3 class="mt-4 text-xl font-bold text-mk-navy">{{ $application->opportunity?->title ?? 'Opportunity' }}</h3>
                                            <p class="mt-2 text-sm text-slate-600">Updated {{ $application->updated_at->format('M j, Y g:i A') }}</p>
                                        </div>
                                        <x-button :href="route('student.applications.show', $application)" class="w-full sm:w-auto">Open Tracker</x-button>
                                    </div>
                                </x-card>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </section>
</x-dashboard-layout>

