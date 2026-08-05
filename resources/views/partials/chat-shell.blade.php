@php
    /** @var \Illuminate\Support\Collection $rooms */
    /** @var array|null $activeRoom */
    $activeCourseId = $activeRoom['course']['id'] ?? null;
@endphp

<div class="grid gap-4 rounded-2xl border border-slate-200 bg-white shadow-sm lg:h-[calc(100vh-9rem)] lg:min-h-[500px] lg:grid-cols-[320px_minmax(0,1fr)]">
    {{-- Sidebar: room list --}}
    <aside class="flex min-h-0 flex-col border-slate-200 lg:border-r {{ $activeRoom ? 'hidden lg:flex' : 'flex' }}">
        <div class="border-b border-slate-100 p-4">
            <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Course rooms</p>
            <h2 class="mt-1 text-lg font-black text-mk-navy">Chats</h2>
        </div>
        <div class="flex-1 overflow-y-auto">
            @forelse ($rooms as $room)
                @php
                    $isActive = $room['course_id'] === $activeCourseId;
                    $rowClass = $isActive ? 'bg-mk-goldSoft/50' : 'hover:bg-slate-50';
                    $courseInitial = mb_strtoupper(mb_substr((string) $room['course_title'], 0, 1));
                    $rowTime = $room['last_message_at'] ? $room['last_message_at']->diffForHumans(null, true) : '';
                    $unreadLabel = $room['unread'] > 99 ? '99+' : (string) $room['unread'];
                @endphp
                <a href="{{ route($chatShowRoute, $room['course_id']) }}" class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition {{ $rowClass }}">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-mk-navy text-sm font-black text-mk-gold">
                        {{ $courseInitial }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-black text-mk-navy">{{ $room['course_title'] }}</span>
                            @if ($rowTime !== '')
                                <span class="shrink-0 text-[11px] font-bold text-slate-400">{{ $rowTime }}</span>
                            @endif
                        </span>
                        <span class="mt-1 flex items-center justify-between gap-2">
                            <span class="truncate text-xs text-slate-600">
                                @if ($room['last_message'])
                                    @if ($room['last_message_sender'])
                                        <span class="font-bold text-slate-500">{{ $room['last_message_sender'] }}:</span>
                                    @endif
                                    {{ $room['last_message'] }}
                                @else
                                    <span class="italic text-slate-400">No messages yet</span>
                                @endif
                            </span>
                            @if ($room['unread'] > 0)
                                <span class="inline-flex min-w-5 shrink-0 items-center justify-center rounded-full bg-mk-gold px-1.5 py-0.5 text-[11px] font-black text-mk-navy">{{ $unreadLabel }}</span>
                            @endif
                        </span>
                    </span>
                </a>
            @empty
                <div class="p-6 text-center">
                    <p class="text-sm font-bold text-mk-navy">No course rooms yet</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Rooms appear once you have an active enrollment or an owned course.</p>
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Chat pane --}}
    <section class="flex min-h-0 flex-col {{ $activeRoom ? 'flex' : 'hidden lg:flex' }}">
        @if ($activeRoom)
            @php
                $course = $activeRoom['course'];
                $messages = $activeRoom['messages'];
                $me = $activeRoom['my_id'];
                $lastDay = null;
                $courseInitial = mb_strtoupper(mb_substr((string) $course['title'], 0, 1));
            @endphp
            <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-4 py-3">
                <a href="{{ route($chatBaseRoute) }}" class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-mk-navy hover:bg-white" aria-label="Back to chats">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-mk-navy text-sm font-black text-mk-gold">
                    {{ $courseInitial }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-black text-mk-navy">{{ $course['title'] }}</p>
                    <p class="truncate text-xs font-semibold text-slate-500">{{ $course['academy'] }} · Instructor: {{ $course['instructor_name'] ?? 'Unassigned' }}</p>
                </div>
            </div>

            <div class="flex-1 space-y-2 overflow-y-auto bg-[#f0f2f5] px-4 py-4" id="mk-chat-scroll" style="background-image: radial-gradient(rgba(11,58,90,0.05) 1px, transparent 1px); background-size: 20px 20px;">
                @forelse ($messages as $message)
                    @php
                        $mine = (int) $message->sender_id === (int) $me;
                        $createdAt = $message->created_at;
                        $day = $createdAt ? $createdAt->format('Y-m-d') : null;
                        $senderRole = $message->sender ? $message->sender->role : null;
                        $isInstructor = $senderRole === \App\Models\User::ROLE_INSTRUCTOR;

                        $dayLabel = '';
                        if ($createdAt) {
                            $dayLabel = $createdAt->format('D, M j');
                            if ($createdAt->isYesterday()) { $dayLabel = 'Yesterday'; }
                            if ($createdAt->isToday()) { $dayLabel = 'Today'; }
                        }

                        $alignClass = $mine ? 'justify-end' : 'justify-start';
                        $bubbleClass = $mine ? 'rounded-br-none bg-mk-navy text-white' : 'rounded-bl-none bg-white text-slate-800';
                        $senderNameClass = $isInstructor ? 'text-mk-gold' : 'text-mk-navy';
                        $timeClass = $mine ? 'text-white/70' : 'text-slate-400';
                        $senderName = $message->sender ? $message->sender->name : 'User';
                        $timeLabel = $createdAt ? $createdAt->format('H:i') : '';
                        $showDayDivider = $day && $day !== $lastDay;
                        if ($showDayDivider) { $lastDay = $day; }
                    @endphp
                    @if ($showDayDivider)
                        <div class="my-3 flex justify-center">
                            <span class="rounded-full bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500 shadow-sm">{{ $dayLabel }}</span>
                        </div>
                    @endif
                    <div class="flex {{ $alignClass }}">
                        <div class="max-w-[78%] rounded-2xl px-3 py-2 shadow-sm {{ $bubbleClass }}">
                            @if (! $mine)
                                <p class="text-[11px] font-black {{ $senderNameClass }}">
                                    {{ $senderName }}
                                    @if ($isInstructor)
                                        <span class="ml-1 rounded bg-mk-gold px-1 text-[10px] text-mk-navy">Instructor</span>
                                    @endif
                                </p>
                            @endif
                            <p class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</p>
                            <p class="mt-1 text-right text-[10px] font-bold {{ $timeClass }}">{{ $timeLabel }}</p>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center">
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/80 p-6 text-center">
                            <p class="text-sm font-black text-mk-navy">No messages yet</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Say hi to your classmates and instructor.</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <form method="POST" action="{{ route($chatSendRoute, $course['id']) }}" class="flex items-end gap-2 border-t border-slate-100 bg-white p-3">
                @csrf
                <label class="sr-only" for="chat-body">Type a message</label>
                <textarea id="chat-body" name="body" rows="1" required maxlength="4000" placeholder="Type a message…" class="max-h-36 min-h-[42px] flex-1 resize-y rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30"></textarea>
                <button type="submit" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-mk-navy text-white shadow-sm transition hover:bg-mk-blue" aria-label="Send message">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                </button>
            </form>

            <script>
                (function () {
                    var scroll = document.getElementById('mk-chat-scroll');
                    if (scroll) { scroll.scrollTop = scroll.scrollHeight; }
                    var ta = document.getElementById('chat-body');
                    if (ta) {
                        ta.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                if (ta.form) { ta.form.requestSubmit(); }
                            }
                        });
                    }
                })();
            </script>
        @else
            <div class="flex flex-1 items-center justify-center p-8">
                <div class="text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-mk-goldSoft">
                        <x-dashboard-icon name="messages" class="h-8 w-8 text-mk-navy" />
                    </div>
                    <h3 class="mt-4 text-lg font-black text-mk-navy">Pick a course room</h3>
                    <p class="mt-2 max-w-xs text-sm leading-6 text-slate-500">Choose a course on the left to open its group chat with the instructor and other enrolled students.</p>
                </div>
            </div>
        @endif
    </section>
</div>
