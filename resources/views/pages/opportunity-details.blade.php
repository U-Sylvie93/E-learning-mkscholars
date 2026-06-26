<x-layouts.app :title="$opportunity->title" description="MK Scholars opportunity details.">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-badge tone="green">{{ str_replace('_', ' ', $opportunity->type) }}</x-badge>
                    <x-badge tone="gray">{{ $opportunity->deadline?->format('M j, Y') ?? 'Open deadline' }}</x-badge>
                </div>
                <h1 class="mt-6 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">{{ $opportunity->title }}</h1>
                <p class="mt-4 text-sm font-bold uppercase tracking-wide text-mk-gold">
                    {{ collect([$opportunity->organization, $opportunity->city, $opportunity->country])->filter()->join(' - ') ?: 'MK Scholars opportunity' }}
                </p>
                <p class="mt-6 max-w-3xl whitespace-pre-line text-base leading-8 text-slate-600">{{ $opportunity->description }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($ctaState === 'student_can_apply')
                        <form method="POST" action="{{ route('opportunities.apply', $opportunity) }}">
                            @csrf
                            <x-button type="submit" size="lg">Apply</x-button>
                        </form>
                    @elseif ($ctaState === 'application_started')
                        <x-button :href="route('student.applications.show', $application)" size="lg">Open Application</x-button>
                    @elseif ($ctaState === 'non_student')
                        <x-button :href="route('opportunities.show', $opportunity->slug)" size="lg">View Opportunity</x-button>
                    @else
                        <x-button :href="route('login')" size="lg">Login to Apply</x-button>
                    @endif

                    @if ($opportunity->application_url)
                        <x-button :href="$opportunity->application_url" variant="secondary" size="lg">External Link</x-button>
                    @endif
                </div>
            </div>

            <x-card>
                <x-badge tone="gold">Overview</x-badge>
                <dl class="mt-6 space-y-4">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Organization</dt>
                        <dd class="text-right text-sm font-bold text-mk-navy">{{ $opportunity->organization ?? 'Not specified' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Location</dt>
                        <dd class="text-right text-sm font-bold text-mk-navy">{{ collect([$opportunity->city, $opportunity->country])->filter()->join(', ') ?: 'Global' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Deadline</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $opportunity->deadline?->format('M j, Y') ?? 'Open' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-sm text-slate-500">Status</dt>
                        <dd class="text-sm font-bold capitalize text-mk-navy">{{ $opportunity->status }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-2">
            <x-card>
                <x-section-header eyebrow="Requirements" title="What you may need" />
                <div class="mt-6 space-y-4">
                    @forelse ($opportunity->requirements as $requirement)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <div class="flex items-center gap-3">
                                <h3 class="font-bold text-mk-navy">{{ $requirement->name }}</h3>
                                <x-badge :tone="$requirement->is_required ? 'gold' : 'gray'">{{ $requirement->is_required ? 'Required' : 'Optional' }}</x-badge>
                            </div>
                            @if ($requirement->description)
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $requirement->description }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm leading-6 text-slate-600">{{ $opportunity->requirements ?? 'Detailed requirements will be shared soon.' }}</p>
                    @endforelse
                </div>
            </x-card>

            <x-card>
                <x-section-header eyebrow="Benefits" title="Why it matters" />
                <p class="mt-6 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $opportunity->benefits ?? 'Benefits and award details will be updated by MK Scholars.' }}</p>
            </x-card>
        </div>
    </section>
</x-layouts.app>
