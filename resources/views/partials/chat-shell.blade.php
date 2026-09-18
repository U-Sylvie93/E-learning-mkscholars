@php
    /** @var \Illuminate\Support\Collection $rooms */
    /** @var array|null $activeRoom */
    $activeCourseId = $activeRoom['course']['id'] ?? null;
    $chatEditRoute = $chatEditRoute ?? null;
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
                        $bodyHtml = preg_replace(
                            '~(https?://[^\s<]+)~i',
                            '<a href="$1" target="_blank" rel="noopener noreferrer" class="'.$linkClass.' break-all">$1</a>',
                            $escapedBody
                        );
                        $bodyHtml = preg_replace(
                            '~(^|[\s(])((?<!//)www\.[^\s<]+)~i',
                            '$1<a href="https://$2" target="_blank" rel="noopener noreferrer" class="'.$linkClass.' break-all">$2</a>',
                            $bodyHtml
                        );
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

                        $actionPreview = trim($rawBody);
                        if ($actionPreview === '' && $hasAttachment) {
                            $actionPreview = $isImage ? 'Photo' : ($attachmentName ?: 'File');
                        }
                        $actionPreview = \Illuminate\Support\Str::limit($actionPreview, 120);

                        $replyTarget = $message->repliedMessage ?? null;
                        $replyTargetDeleted = $replyTarget ? $replyTarget->isDeleted() : false;
                        $replyTargetSender = $replyTarget?->sender?->name ?? 'User';
                        $replyTargetPreview = '';
                        if ($replyTarget) {
                            if ($replyTargetDeleted) {
                                $replyTargetPreview = 'Original message was deleted';
                            } else {
                                $replyTargetPreview = trim((string) ($replyTarget->body ?? ''));
                                if ($replyTargetPreview === '' && $replyTarget->hasAttachment()) {
                                    $replyTargetPreview = $replyTarget->isImageAttachment()
                                        ? '📷 Photo'
                                        : '📎 '.($replyTarget->attachment_name ?: 'File');
                                }
                                $replyTargetPreview = \Illuminate\Support\Str::limit($replyTargetPreview, 120);
                            }
                        }
                    @endphp

                    @if ($showDayDivider)
                        <div class="my-3 flex justify-center">
                            <span class="rounded-full bg-white/80 px-3 py-1 text-[11px] font-black uppercase tracking-wide text-slate-500 shadow-sm">{{ $dayLabel }}</span>
                        </div>
                    @endif

                    <div class="flex min-w-0 max-w-full {{ $alignClass }}">
                        <div class="group relative min-w-0 w-fit max-w-[min(88%,22rem)] overflow-visible break-words rounded-2xl px-3 py-2 shadow-sm sm:max-w-[78%] {{ $bubbleClass }}">
                            @if (! $isDeleted)
                                <button type="button"
                                    class="absolute right-1 top-1 z-10 inline-flex h-7 w-7 items-center justify-center rounded-full transition {{ $mine ? 'text-white/70 hover:bg-white/10 hover:text-white' : 'text-slate-400 hover:bg-slate-100 hover:text-mk-navy' }} sm:opacity-0 sm:group-hover:opacity-100"
                                    aria-label="Message actions"
                                    title="Message actions"
                                    data-chat-menu-toggle>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="19" cy="12" r="1.7"/></svg>
                                </button>

                                <div class="absolute right-1 top-8 z-30 hidden w-36 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl" data-chat-menu>
                                    <button type="button"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-bold text-mk-navy hover:bg-slate-50"
                                        data-chat-reply
                                        data-message-id="{{ $message->id }}"
                                        data-message-sender="{{ $senderName }}"
                                        data-message-preview="{{ $actionPreview }}">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 17l-5-5 5-5"/><path d="M4 12h10a6 6 0 0 1 6 6v1"/></svg>
                                        Reply
                                    </button>

                                    @if ($mine && $chatEditRoute)
                                        <button type="button"
                                            class="flex w-full items-center gap-2 px-3 py-2 text-xs font-bold text-mk-navy hover:bg-slate-50"
                                            data-chat-edit
                                            data-edit-url="{{ route($chatEditRoute, [$course['id'], $message]) }}"
                                            data-message-body="{{ $rawBody }}"
                                            data-message-has-attachment="{{ $hasAttachment ? '1' : '0' }}">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4z"/></svg>
                                            Edit
                                        </button>
                                    @endif

                                    @if ($mine && $chatDeleteRoute)
                                        <form method="POST" action="{{ route($chatDeleteRoute, [$course['id'], $message]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="flex w-full items-center gap-2 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-50" data-chat-delete-open>
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @if (! $mine)
                                <p class="pr-7 text-[11px] font-black {{ $senderNameClass }}">
                                    {{ $senderName }}
                                    @if ($isInstructor)
                                        <span class="ml-1 rounded bg-mk-gold px-1 text-[10px] text-mk-navy">Instructor</span>
                                    @endif
                                </p>
                            @endif

                            @if ($replyTarget)
                                <div class="mt-1 mr-6 rounded-lg border-l-4 {{ $mine ? 'border-mk-gold bg-white/10' : 'border-mk-gold bg-slate-50' }} px-2.5 py-2">
                                    <p class="truncate text-[10px] font-black {{ $mine ? 'text-mk-gold' : 'text-mk-blue' }}">{{ $replyTargetSender }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-[11px] leading-4 {{ $mine ? 'text-white/75' : 'text-slate-500' }}">{{ $replyTargetPreview }}</p>
                                </div>
                            @endif

                            @if ($isDeleted)
                                <div class="mt-1 text-sm italic leading-6 {{ $mine ? 'text-white/75' : 'text-slate-500' }}">This message was deleted</div>
                            @endif

                            @if ($hasAttachment)
                                @if ($isImage)
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 block pr-6">
                                        <img src="{{ $attachmentUrl }}" alt="{{ $attachmentName }}" class="max-h-64 w-auto rounded-lg border border-black/10 object-contain">
                                    </a>
                                @else
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="mt-1 mr-6 flex items-center gap-2 rounded-lg border {{ $mine ? 'border-white/20 bg-white/10 text-white' : 'border-slate-200 bg-slate-50 text-mk-navy' }} px-3 py-2 text-sm">
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
                                <div class="mt-1 max-w-full overflow-hidden break-words pr-6 text-sm leading-6 [overflow-wrap:anywhere]">{!! $bodyHtml !!}</div>
                            @endif

                            <p class="mt-1 text-right text-[10px] font-bold {{ $timeClass }}">
                                {{ $timeLabel }}
                                @if ($message->edited_at)
                                    <span> · edited</span>
                                @endif
                            </p>
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
                <input type="hidden" name="reply_to_message_id" id="mk-chat-reply-id" value="">
                <input type="hidden" name="_method" id="mk-chat-method" value="PATCH" disabled>

                <div id="mk-chat-context" class="mb-2 hidden items-start justify-between gap-3 rounded-xl border border-mk-gold/30 bg-mk-goldSoft/40 px-3 py-2">
                    <div class="min-w-0 flex-1 border-l-4 border-mk-gold pl-2.5">
                        <p class="text-[11px] font-black text-mk-navy" data-chat-context-title></p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500" data-chat-context-preview></p>
                    </div>
                    <button type="button" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-500 hover:bg-white hover:text-red-600" aria-label="Cancel" title="Cancel" data-chat-context-cancel>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                    </button>
                </div>

                <div id="mk-chat-attachment-preview" class="mb-2 hidden items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                    <span class="min-w-0 flex-1 truncate font-bold text-mk-navy" data-attachment-name></span>
                    <button type="button" class="rounded-md border border-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-500 hover:border-red-400 hover:text-red-600" data-attachment-clear>Remove</button>
                </div>

                <div class="flex min-w-0 items-end gap-2">
                    <label id="mk-chat-attachment-button" class="inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft hover:text-mk-navy sm:h-11 sm:w-11" title="Attach file">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49L12.95 2.56a4 4 0 0 1 5.66 5.66L9.41 17.41a2 2 0 0 1-2.83-2.83L15.07 6.1"/></svg>
                        <span class="sr-only">Attach file</span>
                        <input type="file" name="attachment" id="chat-attachment" class="hidden" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip">
                    </label>
                    <label class="sr-only" for="chat-body">Type a message</label>
                    <textarea id="chat-body" name="body" rows="1" maxlength="4000" placeholder="Type a message" class="max-h-36 min-h-10 min-w-0 flex-1 resize-y rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30 sm:min-h-[42px] sm:px-4"></textarea>
                    <button type="submit" id="mk-chat-submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mk-navy text-white shadow-sm transition hover:bg-mk-blue sm:h-11 sm:w-11" aria-label="Send message">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                    </button>
                </div>

                <div id="mk-chat-link-hint" class="mt-2 hidden items-center gap-2 text-[11px] font-bold text-mk-blue">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"/></svg>
                    <span data-link-count>Link detected — will be clickable once sent</span>
                </div>
            </form>

            <div id="mk-chat-delete-modal" class="fixed inset-0 z-50 hidden items-end justify-center bg-mk-navy/50 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="mk-chat-delete-title">
                <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl shadow-mk-navy/30">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="mk-chat-delete-title" class="text-base font-black text-mk-navy">Delete message?</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600">This will remove the message from this chat.</p>
                        </div>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-mk-navy transition hover:bg-slate-50" data-chat-delete-cancel>Cancel</button>
                        <button type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-700" data-chat-delete-confirm>Delete</button>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var scroll = document.getElementById('mk-chat-scroll');
                    if (scroll) { scroll.scrollTop = scroll.scrollHeight; }

                    var form = document.getElementById('mk-chat-form');
                    var ta = document.getElementById('chat-body');
                    var fileInput = document.getElementById('chat-attachment');
                    var attachmentButton = document.getElementById('mk-chat-attachment-button');
                    var submitButton = document.getElementById('mk-chat-submit');
                    var replyInput = document.getElementById('mk-chat-reply-id');
                    var methodOverride = document.getElementById('mk-chat-method');
                    var contextBar = document.getElementById('mk-chat-context');
                    var contextTitle = contextBar ? contextBar.querySelector('[data-chat-context-title]') : null;
                    var contextPreview = contextBar ? contextBar.querySelector('[data-chat-context-preview]') : null;
                    var contextCancel = contextBar ? contextBar.querySelector('[data-chat-context-cancel]') : null;
                    var originalAction = form ? form.getAttribute('action') : '';
                    var composerMode = 'send';
                    var editingHasAttachment = false;

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

                    var hideContext = function () {
                        if (!contextBar) { return; }
                        contextBar.classList.add('hidden');
                        contextBar.classList.remove('flex');
                        if (contextTitle) { contextTitle.textContent = ''; }
                        if (contextPreview) { contextPreview.textContent = ''; }
                    };

                    var showContext = function (title, previewText) {
                        if (!contextBar) { return; }
                        if (contextTitle) { contextTitle.textContent = title || ''; }
                        if (contextPreview) { contextPreview.textContent = previewText || ''; }
                        contextBar.classList.remove('hidden');
                        contextBar.classList.add('flex');
                    };

                    var clearAttachment = function () {
                        var preview = document.getElementById('mk-chat-attachment-preview');
                        if (fileInput) { fileInput.value = ''; }
                        if (preview) {
                            preview.classList.add('hidden');
                            preview.classList.remove('flex');
                        }
                    };

                    var resetComposer = function (clearText) {
                        composerMode = 'send';
                        editingHasAttachment = false;
                        if (form && originalAction) { form.setAttribute('action', originalAction); }
                        if (methodOverride) {
                            methodOverride.disabled = true;
                            methodOverride.value = 'PATCH';
                        }
                        if (replyInput) { replyInput.value = ''; }
                        if (fileInput) { fileInput.disabled = false; }
                        if (attachmentButton) { attachmentButton.classList.remove('hidden'); }
                        if (submitButton) { submitButton.setAttribute('aria-label', 'Send message'); }
                        hideContext();
                        clearAttachment();
                        if (clearText && ta) {
                            ta.value = '';
                            refreshLinkHint();
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
                        clearBtn.addEventListener('click', clearAttachment);
                    }

                    var closeAllMenus = function (exceptMenu) {
                        document.querySelectorAll('[data-chat-menu]').forEach(function (menu) {
                            if (menu !== exceptMenu) { menu.classList.add('hidden'); }
                        });
                    };

                    document.querySelectorAll('[data-chat-menu-toggle]').forEach(function (button) {
                        button.addEventListener('click', function (e) {
                            e.stopPropagation();
                            var menu = button.parentElement ? button.parentElement.querySelector('[data-chat-menu]') : null;
                            if (!menu) { return; }
                            var wasHidden = menu.classList.contains('hidden');
                            closeAllMenus(menu);
                            menu.classList.toggle('hidden', !wasHidden);
                        });
                    });

                    document.querySelectorAll('[data-chat-menu]').forEach(function (menu) {
                        menu.addEventListener('click', function (e) { e.stopPropagation(); });
                    });

                    document.addEventListener('click', function () { closeAllMenus(null); });

                    document.querySelectorAll('[data-chat-reply]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            resetComposer(true);
                            composerMode = 'reply';
                            if (replyInput) { replyInput.value = button.dataset.messageId || ''; }
                            showContext('Replying to ' + (button.dataset.messageSender || 'User'), button.dataset.messagePreview || 'Message');
                            closeAllMenus(null);
                            if (ta) { ta.focus(); }
                        });
                    });

                    document.querySelectorAll('[data-chat-edit]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            resetComposer(true);
                            composerMode = 'edit';
                            editingHasAttachment = button.dataset.messageHasAttachment === '1';
                            if (form && button.dataset.editUrl) { form.setAttribute('action', button.dataset.editUrl); }
                            if (methodOverride) {
                                methodOverride.disabled = false;
                                methodOverride.value = 'PATCH';
                            }
                            if (replyInput) { replyInput.value = ''; }
                            if (fileInput) { fileInput.disabled = true; }
                            if (attachmentButton) { attachmentButton.classList.add('hidden'); }
                            if (ta) {
                                ta.value = button.dataset.messageBody || '';
                                refreshLinkHint();
                                ta.focus();
                                ta.setSelectionRange(ta.value.length, ta.value.length);
                            }
                            if (submitButton) { submitButton.setAttribute('aria-label', 'Save edited message'); }
                            showContext('Editing message', button.dataset.messageBody || (editingHasAttachment ? 'Attachment message' : 'Message'));
                            closeAllMenus(null);
                        });
                    });

                    if (contextCancel) {
                        contextCancel.addEventListener('click', function () {
                            resetComposer(true);
                            if (ta) { ta.focus(); }
                        });
                    }

                    if (form) {
                        form.addEventListener('submit', function (e) {
                            var hasFile = fileInput && !fileInput.disabled && fileInput.files && fileInput.files[0];
                            var hasBody = ta && ta.value.trim() !== '';
                            var validEdit = composerMode === 'edit' && (hasBody || editingHasAttachment);
                            if (!hasFile && !hasBody && !validEdit) {
                                e.preventDefault();
                            }
                        });
                    }

                    var deleteModal = document.getElementById('mk-chat-delete-modal');
                    var pendingDeleteForm = null;
                    var openDeleteButtons = document.querySelectorAll('[data-chat-delete-open]');
                    var cancelDelete = deleteModal ? deleteModal.querySelector('[data-chat-delete-cancel]') : null;
                    var confirmDelete = deleteModal ? deleteModal.querySelector('[data-chat-delete-confirm]') : null;

                    var closeDeleteModal = function () {
                        if (!deleteModal) { return; }
                        deleteModal.classList.add('hidden');
                        deleteModal.classList.remove('flex');
                        pendingDeleteForm = null;
                    };

                    openDeleteButtons.forEach(function (button) {
                        button.addEventListener('click', function () {
                            pendingDeleteForm = button.closest('form');
                            closeAllMenus(null);
                            if (!deleteModal || !pendingDeleteForm) { return; }
                            deleteModal.classList.remove('hidden');
                            deleteModal.classList.add('flex');
                        });
                    });

                    if (cancelDelete) {
                        cancelDelete.addEventListener('click', closeDeleteModal);
                    }

                    if (deleteModal) {
                        deleteModal.addEventListener('click', function (e) {
                            if (e.target === deleteModal) { closeDeleteModal(); }
                        });
                    }

                    if (confirmDelete) {
                        confirmDelete.addEventListener('click', function () {
                            if (pendingDeleteForm) { pendingDeleteForm.submit(); }
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
