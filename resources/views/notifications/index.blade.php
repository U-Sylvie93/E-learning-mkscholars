<x-dashboard-layout :role="auth()->user()->role" :title="$title" description="MK Scholars in-app notifications.">
    <div class="space-y-6">
        <div class="flex flex-col gap-5 rounded-lg border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Notification center"
                :title="$title"
                description="Review updates, reminders, and action items from MK Scholars."
            />
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-badge tone="gold">{{ $unreadCount }} unread</x-badge>
                <x-button :href="route($dashboardRoute)" variant="secondary">Back to Dashboard</x-button>
            </div>
        </div>

        <x-card>
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-semibold text-slate-600">{{ $notifications->total() }} notifications</p>
                <form method="POST" action="{{ route($readAllRoute) }}">
                    @csrf
                    <x-button type="submit" size="sm" variant="secondary">Mark All Read</x-button>
                </form>
            </div>

            <div class="grid gap-4">
                @forelse ($notifications as $notification)
                    @php
                        $tone = match ($notification->type) {
                            \App\Models\AppNotification::TYPE_SUCCESS => 'green',
                            \App\Models\AppNotification::TYPE_WARNING,
                            \App\Models\AppNotification::TYPE_DANGER,
                            \App\Models\AppNotification::TYPE_REMINDER => 'gold',
                            default => 'blue',
                        };
                    @endphp
                    <div class="rounded-lg border p-4 {{ $notification->isUnread() ? 'border-mk-gold bg-mk-goldSoft/40' : 'border-slate-100 bg-slate-50' }}">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$tone">{{ str_replace('_', ' ', $notification->type) }}</x-badge>
                                    <x-badge tone="gray">{{ str_replace('_', ' ', $notification->category) }}</x-badge>
                                    @if ($notification->isUnread())
                                        <x-badge tone="green">Unread</x-badge>
                                    @endif
                                </div>
                                <h2 class="mt-4 break-words text-xl font-bold text-mk-navy">{{ $notification->title }}</h2>
                                <p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex w-full shrink-0 flex-col gap-3 sm:w-auto sm:flex-row md:flex-col">
                                @if ($notification->action_url)
                                    <x-button :href="$notification->action_url" size="sm" class="w-full sm:w-auto">Open</x-button>
                                @endif
                                @if ($notification->isUnread())
                                    <form method="POST" action="{{ route($readRoute, $notification) }}" class="w-full sm:w-auto">
                                        @csrf
                                        <x-button type="submit" size="sm" variant="secondary" class="w-full sm:w-auto">Mark Read</x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-6">
                        <h2 class="text-xl font-bold text-mk-navy">No notifications yet</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Updates and reminders will appear here when there is something new.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        </x-card>
    </div>
</x-dashboard-layout>
