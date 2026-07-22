<x-layouts.app :title="$paper->title" description="Read entrance exam past paper.">
    <section class="bg-slate-50 py-5 sm:py-8">
        <div class="mk-container">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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

            <div class="rounded-lg border border-slate-200 bg-white shadow-sm" data-paper-viewer-shell>
                <div class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 p-3 backdrop-blur sm:p-4" data-paper-viewer-toolbar>
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <h1 class="truncate text-xl font-black text-mk-navy sm:text-2xl">{{ $paper->title }}</h1>
                            <p class="mt-1 text-xs font-semibold text-slate-600 sm:text-sm">{{ $paper->institution?->name ?? 'General institution' }} · {{ $paper->program?->name ?? 'General program' }} · {{ $paper->subject?->name ?? 'General subject' }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600" data-pdf-page-status>Pages</span>
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
                </div>

                @if (filled($renderedInstructions))
                    <div class="border-b border-slate-200 p-4 sm:p-6">
                        <h2 class="text-xl font-extrabold text-mk-navy">Instructions</h2>
                        <div class="mk-rich-content mt-4">{!! $renderedInstructions !!}</div>
                    </div>
                @endif

                <div
                    class="mk-paper-viewer min-h-[70vh] bg-slate-100 p-2 sm:p-4"
                    data-testid="entrance-exam-pdf-viewer"
                    data-paper-viewer
                    data-file-kind="{{ $viewerKind }}"
                    @if (in_array($viewerKind, ['pdf', 'pdf-preview', 'image'], true))
                        data-file-url="{{ route('entrance-exam-academy.papers.inline', $paper) }}"
                    @endif
                >
                    @if (in_array($viewerKind, ['pdf', 'pdf-preview'], true))
                        <div class="mx-auto flex w-full max-w-5xl flex-col gap-4" data-pdf-pages>
                            <div class="rounded-lg border border-slate-200 bg-white p-5 text-center text-sm font-semibold text-slate-600" data-pdf-loading>Preparing paper...</div>
                        </div>
                    @elseif ($viewerKind === 'image')
                        <div class="mx-auto w-full max-w-5xl">
                            <img src="{{ route('entrance-exam-academy.papers.inline', $paper) }}" alt="{{ $paper->title }}" class="h-auto w-full rounded-lg border border-slate-200 bg-white object-contain shadow-sm">
                        </div>
                    @else
                        <div class="mx-auto max-w-2xl rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
                            <h2 class="text-xl font-extrabold text-mk-navy">Preview is not available for this file yet.</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">This paper is protected, but an in-page preview has not been uploaded for this Office document.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
