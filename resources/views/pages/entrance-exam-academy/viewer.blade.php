<x-layouts.app :title="$paper->title" description="Read entrance exam past paper.">
    <section class="bg-slate-50 py-8 sm:py-10">
        <div class="mk-container">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <x-button :href="route('entrance-exam-academy.papers.show', $paper)" variant="secondary">Back to Paper Details</x-button>
                <div class="flex flex-wrap gap-2">
                    @if ($paper->exam_year)
                        <x-badge tone="blue">{{ $paper->exam_year }}</x-badge>
                    @endif
                    @if ($paper->exam_type)
                        <x-badge tone="gray">{{ $paper->exam_type }}</x-badge>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-mk-navy">{{ $paper->title }}</h1>
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $paper->institution?->name ?? 'General institution' }} · {{ $paper->program?->name ?? 'General program' }} · {{ $paper->subject?->name ?? 'General subject' }}</p>
                    </div>
                    <x-badge tone="gold">Read-only viewer</x-badge>
                </div>

                <div class="relative mt-5 overflow-hidden rounded-lg border border-slate-200 bg-slate-100" data-testid="entrance-exam-pdf-viewer">
                    <iframe
                        src="{{ route('entrance-exam-academy.papers.inline', $paper) }}#toolbar=0&navpanes=0"
                        title="{{ $paper->title }} PDF"
                        class="h-[78vh] min-h-[520px] w-full bg-white"
                    ></iframe>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-10">
                        <p class="-rotate-12 select-none text-center text-4xl font-black uppercase tracking-wide text-mk-navy sm:text-6xl">{{ $watermark }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
