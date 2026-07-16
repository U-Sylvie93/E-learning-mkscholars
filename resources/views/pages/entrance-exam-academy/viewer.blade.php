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

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-pdf-viewer-shell>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-mk-navy">{{ $paper->title }}</h1>
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $paper->institution?->name ?? 'General institution' }} · {{ $paper->program?->name ?? 'General program' }} · {{ $paper->subject?->name ?? 'General subject' }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" title="Dark mode" aria-label="Dark mode" data-pdf-dark class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-mk-navy shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" title="Zoom out" aria-label="Zoom out" data-pdf-zoom-out class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-mk-navy shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M8 11h6"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" title="Zoom in" aria-label="Zoom in" data-pdf-zoom-in class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-mk-navy shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M8 11h6"/><path d="M11 8v6"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
                        </button>
                        <button type="button" title="Reset zoom" aria-label="Reset zoom" data-pdf-zoom-reset class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-mk-navy shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>

                <div class="relative mt-5 overflow-auto rounded-lg border border-slate-200 bg-slate-100" data-testid="entrance-exam-pdf-viewer" data-pdf-frame-wrap>
                    <iframe
                        src="{{ route('entrance-exam-academy.papers.inline', $paper) }}#toolbar=0&navpanes=0"
                        title="{{ $paper->title }} PDF"
                        class="h-[78vh] min-h-[520px] w-full origin-top bg-white transition"
                        data-pdf-frame
                    ></iframe>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-10">
                        <p class="-rotate-12 select-none text-center text-4xl font-black uppercase tracking-wide text-mk-navy sm:text-6xl">{{ $watermark }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const shell = document.querySelector('[data-pdf-viewer-shell]');
            const frame = document.querySelector('[data-pdf-frame]');
            if (! shell || ! frame) return;

            let zoom = 1;
            const applyZoom = () => {
                frame.style.transform = `scale(${zoom})`;
                frame.style.width = `${100 / zoom}%`;
            };

            document.querySelector('[data-pdf-dark]')?.addEventListener('click', () => {
                shell.classList.toggle('bg-slate-950');
                shell.classList.toggle('border-slate-800');
                frame.classList.toggle('invert');
                frame.classList.toggle('hue-rotate-180');
            });

            document.querySelector('[data-pdf-zoom-in]')?.addEventListener('click', () => {
                zoom = Math.min(1.5, +(zoom + 0.1).toFixed(2));
                applyZoom();
            });

            document.querySelector('[data-pdf-zoom-out]')?.addEventListener('click', () => {
                zoom = Math.max(0.8, +(zoom - 0.1).toFixed(2));
                applyZoom();
            });

            document.querySelector('[data-pdf-zoom-reset]')?.addEventListener('click', () => {
                zoom = 1;
                applyZoom();
            });
        });
    </script>
</x-layouts.app>
