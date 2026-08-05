<x-dashboard-layout role="instructor" :title="'Chat: '.$thread->student?->name" description="Course chat with your student.">
    @php($me = auth()->id())
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">{{ $thread->course?->academy?->name }} · {{ $thread->course?->title }}</p>
                <h1 class="mt-1 text-2xl font-black text-mk-navy">{{ $thread->student?->name ?? 'Student' }}</h1>
                <p class="mt-1 text-sm font-semibold text-slate-600">{{ $thread->student?->email }}</p>
            </div>
            <x-button :href="route('instructor.messages')" variant="secondary" size="sm">All chats</x-button>
        </div>

        <x-card class="p-0">
            <div class="max-h-[520px] space-y-3 overflow-y-auto p-5" id="mk-chat-scroll">
                @forelse ($thread->messages as $message)
                    @php($mine = $message->sender_id === $me)
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-4 py-3 text-sm leading-6 shadow-sm {{ $mine ? 'bg-mk-navy text-white' : 'bg-slate-100 text-slate-800' }}">
                            <p class="text-xs font-bold uppercase tracking-wide {{ $mine ? 'text-mk-gold' : 'text-slate-500' }}">{{ $mine ? 'You' : ($message->sender?->name ?? 'Student') }} · {{ $message->created_at->diffForHumans() }}</p>
                            <p class="mt-1 whitespace-pre-wrap break-words">{{ $message->body }}</p>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm font-semibold text-slate-500">No messages yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('instructor.messages.send', $thread) }}" class="border-t border-slate-100 p-4">
                @csrf
                <label class="sr-only" for="chat-body">Reply</label>
                <div class="flex gap-2">
                    <textarea id="chat-body" name="body" rows="2" required maxlength="4000" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Type a reply..."></textarea>
                    <x-button type="submit">Send</x-button>
                </div>
                @error('body')
                    <p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>
                @enderror
            </form>
        </x-card>
    </div>
    <script>
        (() => {
            const scroll = document.getElementById('mk-chat-scroll');
            if (scroll) scroll.scrollTop = scroll.scrollHeight;
        })();
    </script>
</x-dashboard-layout>
