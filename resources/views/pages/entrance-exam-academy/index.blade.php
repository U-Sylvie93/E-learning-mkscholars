<x-layouts.app title="Entrance Exam Academy" description="Browse MK Scholars entrance exam past papers.">
    <section class="bg-slate-50 py-12 sm:py-16">
        <div class="mk-container">
            <div class="max-w-3xl">
                <x-badge tone="gold">Entrance Exam Academy</x-badge>
                <h1 class="mt-4 text-4xl font-black tracking-normal text-mk-navy sm:text-5xl">Read past papers for entrance preparation</h1>
                <p class="mt-4 text-sm leading-7 text-slate-600 sm:text-base">Browse published university and institution entrance papers by institution, program, subject, year, and exam type.</p>
            </div>

            <form method="GET" action="{{ route('entrance-exam-academy.index') }}" class="mt-8 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Search papers">
                    <select name="institution" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All institutions</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) ($filters['institution'] ?? '') === (string) $institution->id)>{{ $institution->name }}</option>
                        @endforeach
                    </select>
                    <select name="program" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All programs</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) ($filters['program'] ?? '') === (string) $program->id)>{{ $program->name }}</option>
                        @endforeach
                    </select>
                    <select name="subject" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All subjects</option>
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) ($filters['subject'] ?? '') === (string) $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All years</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    <select name="exam_type" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">All exam types</option>
                        @foreach ($examTypes as $type)
                            <option value="{{ $type }}" @selected(($filters['exam_type'] ?? '') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-button type="submit" size="sm">Apply Filters</x-button>
                    <x-button :href="route('entrance-exam-academy.index')" variant="secondary" size="sm">Clear</x-button>
                </div>
            </form>

            <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($papers as $paper)
                    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap gap-2">
                            @if ($paper->is_featured)
                                <x-badge tone="gold">Featured</x-badge>
                            @endif
                            @if ($paper->exam_year)
                                <x-badge tone="blue">{{ $paper->exam_year }}</x-badge>
                            @endif
                            @if ($paper->exam_type)
                                <x-badge tone="gray">{{ $paper->exam_type }}</x-badge>
                            @endif
                        </div>
                        <h2 class="mt-4 text-xl font-extrabold text-mk-navy">{{ $paper->title }}</h2>
                        <dl class="mt-4 grid gap-2 text-sm text-slate-600">
                            <div><span class="font-bold text-mk-navy">Institution:</span> {{ $paper->institution?->name ?? 'General' }}</div>
                            <div><span class="font-bold text-mk-navy">Program:</span> {{ $paper->program?->name ?? 'General' }}</div>
                            <div><span class="font-bold text-mk-navy">Subject:</span> {{ $paper->subject?->name ?? 'General' }}</div>
                        </dl>
                        <x-button :href="route('entrance-exam-academy.papers.show', $paper)" class="mt-5 w-full" variant="secondary">View Details</x-button>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center md:col-span-2 xl:col-span-3">
                        <h2 class="text-xl font-extrabold text-mk-navy">No papers found</h2>
                        <p class="mt-2 text-sm text-slate-600">Try changing your filters or check back when new published papers are added.</p>
                    </div>
                @endforelse
            </div>

            @if (method_exists($papers, 'links'))
                <div class="mt-8">{{ $papers->links() }}</div>
            @endif

            <p class="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-mk-navy">Read-only viewing reduces easy downloading, but it cannot fully prevent screenshots, screen recording, browser inspection, or external capture.</p>
        </div>
    </section>
</x-layouts.app>
