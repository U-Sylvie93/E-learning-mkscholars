<x-layouts.app title="Opportunities">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
            <div>
                <x-badge tone="gold">Opportunities</x-badge>
                <h1 class="mt-5 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Find scholarships, internships, events, and study abroad openings</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">Explore published opportunities with clear deadlines and application actions. Students can log in to track applications and documents.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                <form method="GET" class="grid gap-3 sm:grid-cols-3">
                    <select name="type" class="mk-input">
                        <option value="">All types</option>
                        @foreach (['scholarship', 'internship', 'job', 'study_abroad', 'competition', 'event'] as $type)
                            <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                    <input name="country" value="{{ request('country') }}" placeholder="Country" class="mk-input">
                    <x-button type="submit">Filter</x-button>
                </form>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($opportunities as $opportunity)
                <x-opportunity-card :opportunity="$opportunity" />
            @empty
                <x-card class="md:col-span-2 lg:col-span-3">
                    <x-badge tone="gray">Coming soon</x-badge>
                    <h2 class="mt-5 text-xl font-bold text-mk-navy">No matching opportunities yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Try another filter or check again later.</p>
                </x-card>
            @endforelse
        </div>
    </section>
</x-layouts.app>
