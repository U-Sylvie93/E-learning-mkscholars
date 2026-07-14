<x-layouts.app :title="$paper->title" description="Entrance exam past paper details.">
    <section class="bg-slate-50 py-12 sm:py-16">
        <div class="mk-container">
            <div class="mb-6">
                <x-button :href="route('entrance-exam-academy.index')" variant="secondary">Back to Entrance Exam Academy</x-button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <x-card>
                    <div class="flex flex-wrap gap-2">
                        @if ($paper->exam_year)
                            <x-badge tone="blue">{{ $paper->exam_year }}</x-badge>
                        @endif
                        @if ($paper->exam_type)
                            <x-badge tone="gray">{{ $paper->exam_type }}</x-badge>
                        @endif
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">{{ $paper->title }}</h1>
                    @if ($paper->description)
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $paper->description }}</p>
                    @endif

                    <div class="mt-6">
                        <x-button :href="route('entrance-exam-academy.papers.view', $paper)" size="lg">Read Paper</x-button>
                    </div>
                    <p class="mt-4 text-xs font-semibold leading-5 text-slate-500">No direct download button is provided. The protected viewer opens the PDF in the browser for authenticated users.</p>
                </x-card>

                <x-card>
                    <h2 class="text-xl font-extrabold text-mk-navy">Paper details</h2>
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Institution</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->institution?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Program / Faculty</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->program?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Subject</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->subject?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Year</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->exam_year ?? 'Not specified' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Exam type</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->exam_type ?? 'Not specified' }}</dd></div>
                    </dl>
                </x-card>
            </div>

            <p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-mk-navy">Read-only viewing reduces easy downloading, but it cannot fully prevent screenshots, screen recording, browser inspection, or external capture.</p>
        </div>
    </section>
</x-layouts.app>
