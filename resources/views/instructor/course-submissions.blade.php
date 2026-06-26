<x-dashboard-layout role="instructor" :title="$course->title.' Submissions'" description="MK Scholars instructor submissions preview.">
    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <x-section-header eyebrow="Submissions" :title="$course->title" description="Read-only assignment submission preview." />
                <x-button :href="route('instructor.courses.show', $course)" variant="secondary">Back to Course</x-button>
            </div>

            @include('instructor.partials.nav')

            <div class="mt-10 space-y-5">
                @forelse ($submissions as $submission)
                    @php
                        $fileExists = $submission->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path);
                        $fileUrl = $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->url($submission->file_path) : null;
                        $externalLink = $submission->external_link;
                        $externalScheme = is_string($externalLink) ? parse_url($externalLink, PHP_URL_SCHEME) : null;
                        $externalLinkIsSafe = filled($externalLink) && in_array($externalScheme, ['http', 'https'], true);
                    @endphp

                    <x-card class="overflow-hidden" data-testid="instructor-submission-card">
                        <div class="grid gap-4 border-b border-slate-100 pb-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$submission->status === 'graded' ? 'green' : 'gold'">{{ str_replace('_', ' ', $submission->status) }}</x-badge>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ $submission->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</span>
                                </div>
                                <h2 class="mt-3 break-words text-xl font-extrabold text-mk-navy">{{ $submission->assignment?->title ?? 'Assignment' }}</h2>
                                <p class="mt-1 text-sm font-semibold text-slate-600">Submitted by {{ $submission->user?->name ?? 'Student' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-left lg:text-right">
                                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Grade</p>
                                <p class="mt-1 text-lg font-extrabold text-mk-navy">{{ $submission->score !== null ? $submission->score : 'Pending' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_20rem]">
                            <section>
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Question answers</p>
                                <div class="mt-3">
                                    @include('partials.assignment-question-answers', ['answers' => $submission->questionAnswers, 'compact' => true])
                                </div>
                            </section>

                            <aside class="space-y-4">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4" data-testid="instructor-submission-file">
                                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Submitted file</p>
                                    @if ($fileUrl)
                                        <p class="mt-2 break-words text-sm font-bold text-mk-navy">{{ basename($submission->file_path) }}</p>
                                        <a class="mt-3 inline-flex rounded-md border border-mk-gold/40 bg-white px-3 py-2 text-sm font-bold text-mk-navy transition hover:bg-mk-goldSoft" href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer">Download file</a>
                                    @elseif ($submission->file_path)
                                        <p class="mt-2 text-sm font-semibold text-slate-500">File missing from storage.</p>
                                    @else
                                        <p class="mt-2 text-sm font-semibold text-slate-500">No file submitted.</p>
                                    @endif
                                </div>

                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4" data-testid="instructor-submission-link">
                                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">External link</p>
                                    @if ($externalLinkIsSafe)
                                        <p class="mt-2 break-words text-sm text-slate-600">{{ $externalLink }}</p>
                                        <a class="mt-3 inline-flex rounded-md border border-mk-gold/40 bg-white px-3 py-2 text-sm font-bold text-mk-navy transition hover:bg-mk-goldSoft" href="{{ $externalLink }}" target="_blank" rel="noopener noreferrer">Open link</a>
                                    @elseif (filled($externalLink))
                                        <p class="mt-2 text-sm font-semibold text-slate-500">External link is not available.</p>
                                    @else
                                        <p class="mt-2 text-sm font-semibold text-slate-500">No external link submitted.</p>
                                    @endif
                                </div>
                            </aside>
                        </div>
                    </x-card>
                @empty
                    <x-card>
                        <p class="text-sm text-slate-600">No assignment submissions found for this course.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-dashboard-layout>