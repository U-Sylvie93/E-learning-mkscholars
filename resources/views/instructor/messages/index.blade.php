<x-dashboard-layout role="instructor" title="Messages" description="Chat rooms with your enrolled students.">
    <div class="space-y-6">
        <x-section-header
            eyebrow="Chat rooms"
            title="Student conversations"
            description="Reply to students enrolled in your courses. Threads are grouped by course + student."
        />

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($threads->isEmpty())
            <x-card>
                <x-empty-state
                    title="No student messages yet"
                    description="When a student enrolled in your course starts a chat, it will appear here."
                />
            </x-card>
        @else
            <div class="grid gap-4">
                @foreach ($threads as $thread)
                    @php($last = $thread->messages->first())
                    <a href="{{ route('instructor.messages.show', $thread) }}" class="block">
                        <x-card class="transition hover:border-mk-gold hover:shadow-soft">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $thread->course?->academy?->name ?? 'Course' }}</p>
                                    <h3 class="mt-1 text-lg font-black text-mk-navy">{{ $thread->course?->title }}</h3>
                                    <p class="mt-1 text-sm font-semibold text-slate-600">With {{ $thread->student?->name ?? 'Student' }}</p>
                                </div>
                                @if ($thread->last_message_at)
                                    <span class="text-xs font-bold text-slate-500">{{ $thread->last_message_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            @if ($last)
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $last->body }}</p>
                            @else
                                <p class="mt-3 text-sm italic leading-6 text-slate-500">No messages yet.</p>
                            @endif
                        </x-card>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-dashboard-layout>
