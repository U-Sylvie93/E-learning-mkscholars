<x-dashboard-layout role="student" title="Student Opportunities" description="MK Scholars student opportunities.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Opportunities"
                title="Find your next application"
                description="Browse published scholarships, internships, jobs, competitions, events, and study abroad openings."
            />
            <x-button :href="route('student.applications')" variant="secondary">My Applications</x-button>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            <form method="GET" class="grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-4">
                <select name="type" class="mk-input">
                    <option value="">All types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                    @endforeach
                </select>
                <select name="country" class="mk-input">
                    <option value="">All countries</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>{{ $country }}</option>
                    @endforeach
                </select>
                <select name="deadline" class="mk-input">
                    <option value="">Any deadline</option>
                    <option value="upcoming" @selected(($filters['deadline'] ?? '') === 'upcoming')>Upcoming deadlines</option>
                </select>
                <x-button type="submit">Filter</x-button>
            </form>

            <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($opportunities as $opportunity)
                    <x-card class="flex h-full flex-col">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-badge tone="green">{{ str_replace('_', ' ', $opportunity->type) }}</x-badge>
                            <x-badge :tone="$opportunity->deadlineBadgeTone()">{{ $opportunity->deadlineBadge() }}</x-badge>
                            @if ($opportunity->is_featured)
                                <x-badge tone="gold">Featured</x-badge>
                            @endif
                        </div>
                        <h2 class="mt-5 break-words text-xl font-bold text-mk-navy">{{ $opportunity->title }}</h2>
                        <p class="mt-2 text-xs font-bold uppercase tracking-wide text-mk-gold">
                            {{ collect([$opportunity->organization, $opportunity->city, $opportunity->country])->filter()->join(' - ') ?: 'MK Scholars' }}
                        </p>
                        <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ str($opportunity->description)->limit(130) }}</p>
                        <p class="mt-4 text-sm font-bold text-mk-navy">Deadline: {{ $opportunity->deadline?->format('M j, Y') ?? 'Open' }}</p>
                        <x-button :href="route('opportunities.show', $opportunity->slug)" class="mt-6 w-full">View Opportunity</x-button>
                    </x-card>
                @empty
                    <x-card class="md:col-span-2 lg:col-span-3">
                        <x-badge tone="gray">Empty</x-badge>
                        <h2 class="mt-5 text-xl font-bold text-mk-navy">No matching opportunities yet</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Try a different filter or check back soon.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-dashboard-layout>

