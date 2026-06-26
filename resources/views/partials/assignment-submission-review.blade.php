@php
    $submission?->loadMissing(['assignment', 'user', 'questionAnswers.question']);
    $fileExists = $submission?->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($submission->file_path);
    $fileUrl = $fileExists ? \Illuminate\Support\Facades\Storage::disk('public')->url($submission->file_path) : null;
    $fileName = $submission?->file_path ? basename($submission->file_path) : null;
    $externalLink = $submission?->external_link;
    $externalScheme = is_string($externalLink) ? parse_url($externalLink, PHP_URL_SCHEME) : null;
    $externalLinkIsSafe = filled($externalLink) && in_array($externalScheme, ['http', 'https'], true);
@endphp

<div class="space-y-5" data-testid="assignment-submission-review-panel">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Assignment</p>
            <p class="mt-2 break-words text-sm font-bold text-mk-navy">{{ $submission?->assignment?->title ?? 'Assignment' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Student</p>
            <p class="mt-2 break-words text-sm font-bold text-mk-navy">{{ $submission?->user?->name ?? 'Student' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Submitted</p>
            <p class="mt-2 text-sm font-bold text-mk-navy">{{ $submission?->submitted_at?->format('M j, Y g:i A') ?? 'Not submitted' }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Status</p>
            <p class="mt-2 text-sm font-bold capitalize text-mk-navy">{{ str_replace('_', ' ', $submission?->status ?? 'submitted') }}</p>
        </div>
    </div>

    @if (filled($submission?->text_answer))
        <section class="rounded-lg border border-slate-200 bg-white p-4" data-testid="submission-text-answer">
            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Text answer</p>
            <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-slate-700">{{ $submission->text_answer }}</p>
        </section>
    @endif

    <section class="rounded-lg border border-slate-200 bg-white p-4" data-testid="submission-question-answers">
        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Question answers</p>
        <div class="mt-4">
            @include('partials.assignment-question-answers', ['answers' => $submission?->questionAnswers ?? collect()])
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-slate-200 bg-white p-4" data-testid="submission-file-panel">
            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Submitted file</p>
            @if ($fileUrl)
                <p class="mt-3 break-words text-sm font-bold text-mk-navy">{{ $fileName }}</p>
                <a href="{{ $fileUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex rounded-md border border-mk-gold/40 bg-mk-goldSoft px-4 py-2 text-sm font-bold text-mk-navy transition hover:border-mk-gold">
                    Download file
                </a>
            @elseif ($fileName)
                <p class="mt-3 text-sm font-semibold text-slate-500">File missing from storage.</p>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-500">No file submitted.</p>
            @endif
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-4" data-testid="submission-external-link-panel">
            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">External link</p>
            @if ($externalLinkIsSafe)
                <p class="mt-3 break-words text-sm text-slate-600">{{ $externalLink }}</p>
                <a href="{{ $externalLink }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex rounded-md border border-mk-gold/40 bg-mk-goldSoft px-4 py-2 text-sm font-bold text-mk-navy transition hover:border-mk-gold">
                    Open link
                </a>
            @elseif (filled($externalLink))
                <p class="mt-3 text-sm font-semibold text-slate-500">External link is not available.</p>
            @else
                <p class="mt-3 text-sm font-semibold text-slate-500">No external link submitted.</p>
            @endif
        </section>
    </div>
</div>