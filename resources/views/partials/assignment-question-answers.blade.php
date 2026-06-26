@php
    $answers = collect($answers ?? []);
    $compact = (bool) ($compact ?? false);
@endphp

<div class="{{ $compact ? 'space-y-2' : 'space-y-3' }}" data-testid="question-answer-list">
    @forelse ($answers as $answer)
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-testid="question-answer-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Question {{ $loop->iteration }}</p>
                @if ($answer->question?->points !== null)
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $answer->question->points }} pts</span>
                @endif
            </div>
            <p class="mt-2 break-words text-sm font-bold leading-6 text-mk-navy">{{ $answer->question?->question_text ?? 'Assignment question' }}</p>
            <div class="mt-3 rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm leading-6 text-slate-700">
                @if (filled($answer->answer))
                    <p class="whitespace-pre-wrap break-words">{{ $answer->answer }}</p>
                @else
                    <p class="font-semibold text-slate-500">No answer provided</p>
                @endif
            </div>
        </article>
    @empty
        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-500" data-testid="question-answer-empty">
            No question answers submitted.
        </div>
    @endforelse
</div>