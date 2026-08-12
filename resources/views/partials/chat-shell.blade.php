@php
    /** @var \Illuminate\Support\Collection $rooms */
    /** @var array|null $activeRoom */
    $activeCourseId = $activeRoom['course']['id'] ?? null;
    $chatDeleteRoute = $chatDeleteRoute ?? null;
@endphp

<div class="-mx-4 flex h-[calc(100dvh-7.25rem)] min-h-[460px] w-[calc(100%+2rem)] max-w-none flex-col overflow-hidden border-y border-slate-200 bg-white shadow-sm sm:mx-0 sm:h-[calc(100dvh-9rem)] sm:min-h-[520px] sm:w-full sm:rounded-2xl sm:border lg:h-[calc(100vh-9rem)] lg:min-h-[500px] lg:grid lg:grid-cols-[320px_minmax(0,1fr)] lg:flex-row">
    {{-- Sidebar: room list --}}
    <aside class="flex h-full min-h-0 min-w-0 max-w-full flex-1 flex-col overflow-hidden border-slate-200 lg:flex-none lg:border-r {{ $activeRoom ? 'hidden lg:flex' : 'flex' }}">
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
    <section class="flex h-full min-h-0 min-w-0 max-w-full flex-1 flex-col overflow-hidden {{ $activeRoom ? 'flex' : 'hidden lg:flex' }}">
        @if ($activeRoom)
            @php
                $course = $activeRoom['course'];
                $messages = $activeRoom['messages'];
                $me = $activeRoom['my_id'];
                $lastDay = null;
                $courseInitial = mb_strtoupper(mb_substr((string) $course['title'], 0, 1));
            @endphp
            <div class="flex shrink-0 items-center gap-2 border-b border-slate-100 bg-slate-50 px-2 py-2 sm:gap-3 sm:px-4 sm:py-3">
                <a href="{{ route($chatBaseRoute) }}" class="lg:hidden inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-mk-navy hover:bg-white" aria-label="Back to chats">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-mk-navy text-xs font-black text-mk-gold sm:h-11 sm:w-11 sm:text-sm">
                    {{ $courseInitial }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-black text-mk-navy sm:text-sm">{{ $course['title'] }}</p>
                    <p class="truncate text-[11px] font-semibold text-slate-500 sm:text-xs">{{ $course['academy'] }} · Instructor: {{ $course['instructor_name'] ?? 'Unassigned' }}</p>
                </div>
            </div>

            <div class="min-h-0 min-w-0 flex-1 space-y-2 overflow-x-hidden overflow-y-auto bg-[#f0f2f5] px-2 py-3 sm:px-4 sm:py-4" id="mk-chat-scroll" style="background-image: radial-gradient(rgba(11,58,90,0.05) 1px, transparent 1px); background-size: 20px 20px; overscroll-behavior: contain;">
                @forelse ($messages as $message)
                    @php
                        $mine = (int) $message->sender_id === (int) $me;
                        $createdAt = $message->created_at;
                        $day = $createdAt ? $createdAt->format('Y-m-d') : null;
                        $senderRole = $message->sender ? $message->sender->role : null;
                        $isInstructor = $senderRole === \App\Models\User::ROLE_INSTRUCTOR;
                        $isDeleted = method_exists($message, 'isDeleted') ? $message->isDeleted() : false;

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
                        $linkClass = $mine ? 'text-mk-gold underline' : 'text-mk-blue underline';
                        $senderName = $message->sender ? $message->sender->name : 'User';
                        $timeLabel = $createdAt ? $createdAt->format('H:i') : '';
                        $showDayDivider = $day && $day !== $lastDay;
                        if ($showDayDivider) { $lastDay = $day; }

                        // Linkify: escape then wrap URLs / www. / emails with <a>.
                        $rawBody = (string) ($message->body ?? '');
                        $escapedBody = e($rawBody);
                        // http(s):// links
                        $bodyHtml = preg_replace(
                            '~(https?://[^\s<]+)~i',
                            '<a href="$1" target="_blank" rel="noopener noreferrer" class="'.$linkClass.' break-all">$1</a>',
                            $escapedBody
                        );
                        // Bare www. links (add https:// prefix in href)
                        $bodyHtml = preg_replace(
                            '~(^|[\s(])((?<!//)www\.[^\s<]+)~i',
                            '$1<a href="https://$2" target="_blank" rel="noopener noreferrer" class="'.$linkClass.' break-all">$2</a>',
                            $bodyHtml
                        );
                        // Email addresses
                        $bodyHtml = preg_replace(
                            '~([\w.+-]+@[\w-]+\.[\w.-]+)~i',
                            '<a href="mailto:$1" class="'.$linkClass.' break-all">$1</a>',
                            $bodyHtml
                        );
                        $bodyHtml = nl2br($bodyHtml, false);

                        $hasAttachment = ! $isDeleted && method_exists($message, 'hasAttachment') ? $message->hasAttachment() : false;
                        $isImage = $hasAttachment && method_exists($message, 'isImageAttachment') ? $message->isImageAttachment() : false;
                        $attachmentUrl = $hasAttachment ? $message->attachmentUrl() : null;
                        $attachmentName = $hasAttachment ? ($message->attachment_name ?? 'file') : null;
                        $attachmentSize = $hasAttachment && method_exists($message, 'humanAttachmentSize') ? $message->humanAttachmentSize() : '';
                    @endphp
                    @if ($showDayDivider)
                        <div class="my-3 flex justify-center">
                            <span class="rounded-full bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500 shadow-sm">{{ $dayLabel }}</span>
                        </div>
                    @endif
                    <div class="flex min-w-0 max-w-full {{ $alignClass }}">
                        <div class="group relative min-w-0 w-fit max-w-[min(88%,22rem)] overflow-hidden break-words rounded-2xl px-3 py-2 shadow-sm sm:max-w-[78%] {{ $bubbleClass }}">
                            @if ($mine && $chatDeleteRoute && ! $isDeleted)
                                <form method="POST" action="{{ route($chatDeleteRoute, [$course['id'], $message]) }}" class="absolute right-1 top-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white focus:bg-white/10 focus:text-white focus:outline-none" title="Delete message" aria-label="Delete message" onclick="return confirm('Delete this message?')">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5"/><path d="M14 11v5"/></svg>
                                    </button>
                                </form>
                            @endif
                            @if (! $mine)
                                <p class="text-[11px] font-black {{ $senderNameClass }}">
                                    {{ $senderName }}
                                    @if ($isInstructor)
                                        <span class="ml-1 rounded bg-mk-gold px-1 text-[10px] text-mk-navy">Instructor</span>
                                    @endif
                                </p>
                            @endif

                            @if ($isDeleted)
                                <div class="mt-1 text-sm italic leading-6 {{ $mine ? 'pr-7 text-white/75' : 'text-slate-500' }}">This message was deleted</div>
                            @endif

                            @if ($hasAttachment)
                                @if ($isImage)
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block">
                                        <img src="{{ $attachmentUrl }}" alt="{{ $attachmentName }}" class="max-h-64 w-auto rounded-lg border border-black/10 object-contain">
                                    </a>
                                @else
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 flex items-center gap-2 rounded-lg border {{ $mine ? 'border-white/20 bg-white/10 text-white' : 'border-slate-200 bg-slate-50 text-mk-navy' }} px-3 py-2 text-sm">
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-bold">{{ $attachmentName }}</span>
                                            @if ($attachmentSize !== '')
                                                <span class="block text-[10px] {{ $mine ? 'text-white/70' : 'text-slate-500' }}">{{ $attachmentSize }}</span>
                                            @endif
                                        </span>
                                    </a>
                                @endif
                            @endif

                            @if (! $isDeleted && $rawBody !== '')
                                <div class="mt-1 max-w-full overflow-hidden break-words text-sm leading-6 [overflow-wrap:anywhere] {{ $mine && $chatDeleteRoute ? 'pr-7' : '' }}">{!! $bodyHtml !!}</div>
                            @endif

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

            <form method="POST" action="{{ route($chatSendRoute, $course['id']) }}" enctype="multipart/form-data" class="shrink-0 border-t border-slate-100 bg-white p-2 sm:p-3" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));" id="mk-chat-form">
                @csrf
                <div id="mk-chat-attachment-preview" class="mb-2 hidden items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                    <span class="min-w-0 flex-1 truncate font-bold text-mk-navy" data-attachment-name></span>
                    <button type="button" class="rounded-md border border-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-500 hover:border-red-400 hover:text-red-600" data-attachment-clear>Remove</button>
                </div>
                <div class="flex min-w-0 items-end gap-2">
                    <label class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft hover:text-mk-navy sm:h-11 sm:w-11" title="Attach file">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49L12.95 2.56a4 4 0 0 1 5.66 5.66L9.41 17.41a2 2 0 0 1-2.83-2.83L15.07 6.1"/></svg>
                        <span class="sr-only">Attach file</span>
                        <input type="file" name="attachment" id="chat-attachment" class="hidden" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip">
                    </label>
                    <label class="sr-only" for="chat-body">Type a message</label>
                    <textarea id="chat-body" name="body" rows="1" maxlength="4000" placeholder="Type a message" class="max-h-36 min-h-10 min-w-0 flex-1 resize-y rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30 sm:min-h-[42px] sm:px-4"></textarea>
                    <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mk-navy text-white shadow-sm transition hover:bg-mk-blue sm:h-11 sm:w-11" aria-label="Send message">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                    </button>
                </div>
                <div id="mk-chat-link-hint" class="mt-2 hidden items-center gap-2 text-[11px] font-bold text-mk-blue">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/></svg>
                    <span data-link-count>Link detected — will be clickable once sent</span>
                </div>
            </form>

            <script>
                (function () {
                    var scroll = document.getElementById('mk-chat-scroll');
                    if (scroll) { scroll.scrollTop = scroll.scrollHeight; }

                    var ta = document.getElementById('chat-body');
                    var linkHint = document.getElementById('mk-chat-link-hint');
                    var linkCountEl = linkHint ? linkHint.querySelector('[data-link-count]') : null;
                    var urlRe = /(https?:\/\/[^\s]+|(?:^|\s)www\.[^\s]+|[\w.+-]+@[\w-]+\.[\w.-]+)/gi;

                    var refreshLinkHint = function () {
                        if (!ta || !linkHint || !linkCountEl) { return; }
                        var matches = (ta.value.match(urlRe) || []);
                        if (matches.length > 0) {
                            linkCountEl.textContent = matches.length === 1
                                ? 'Link detected — will be clickable once sent'
                                : matches.length + ' links detected — will be clickable once sent';
                            linkHint.classList.remove('hidden');
                            linkHint.classList.add('flex');
                        } else {
                            linkHint.classList.add('hidden');
                            linkHint.classList.remove('flex');
                        }
                    };

                    if (ta) {
                        ta.addEventListener('input', refreshLinkHint);
                        ta.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                if (ta.form) { ta.form.requestSubmit(); }
                            }
                        });
                    }

                    var fileInput = document.getElementById('chat-attachment');
                    var preview = document.getElementById('mk-chat-attachment-preview');
                    var nameEl = preview ? preview.querySelector('[data-attachment-name]') : null;
                    var clearBtn = preview ? preview.querySelector('[data-attachment-clear]') : null;

                    if (fileInput && preview && nameEl && clearBtn) {
                        fileInput.addEventListener('change', function () {
                            if (fileInput.files && fileInput.files[0]) {
                                nameEl.textContent = fileInput.files[0].name;
                                preview.classList.remove('hidden');
                                preview.classList.add('flex');
                            } else {
                                preview.classList.add('hidden');
                                preview.classList.remove('flex');
                            }
                        });
                        clearBtn.addEventListener('click', function () {
                            fileInput.value = '';
                            preview.classList.add('hidden');
                            preview.classList.remove('flex');
                        });
                    }

                    var form = document.getElementById('mk-chat-form');
                    if (form) {
                        form.addEventListener('submit', function (e) {
                            var hasFile = fileInput && fileInput.files && fileInput.files[0];
                            var hasBody = ta && ta.value.trim() !== '';
                            if (!hasFile && !hasBody) {
                                e.preventDefault();
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
