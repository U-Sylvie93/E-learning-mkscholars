<x-dashboard-layout role="student" title="Student Dashboard" description="MK Scholars student dashboard.">
    <div class="space-y-6">
        <x-section-header
            eyebrow="Student dashboard"
            title="Welcome back, {{ auth()->user()->name }}"
            description="Pick up where you left off, track progress, and stay on top of payments and reminders."
        />

        {{-- Metric row --}}
        <div data-testid="student-dashboard-grid" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card data-testid="student-dashboard-card" tone="gold" label="Certificates" :value="$certificateCount" description="Issued certificates." />
            <x-stat-card data-testid="student-dashboard-card" tone="warning" label="Pending payments" :value="$pendingPaymentsCount" description="Awaiting approval." />
            <x-stat-card data-testid="student-dashboard-card" tone="success" label="Approved payments" :value="$approvedPaymentsCount" description="Cleared payments." />
            <x-stat-card data-testid="student-dashboard-card" tone="blue" label="Subscription" :value="$activeSubscription?->subscriptionPlan?->name ?? 'None'" :description="$activeSubscription ? 'Active plan.' : 'No active plan.'" />
        </div>

        {{-- Subscription expiry alert --}}
        @if ($expiringSubscription || $expiredSubscription)
            <x-card highlighted data-testid="student-dashboard-card" class="min-w-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <x-badge :tone="$expiredSubscription ? 'danger' : 'warning'">{{ $expiredSubscription ? 'Subscription expired' : 'Expiring soon' }}</x-badge>
                        <h3 class="mt-3 text-lg font-bold text-mk-navy">{{ ($expiredSubscription ?? $expiringSubscription)?->subscriptionPlan?->name ?? 'Subscription plan' }}</h3>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            @if ($expiredSubscription)
                                Renew to restore included paid course access.
                            @else
                                Expires {{ $expiringSubscription->ends_at?->format('M j, Y') }}. Renew early to keep access.
                            @endif
                        </p>
                    </div>
                    @if (($expiredSubscription ?? $expiringSubscription)?->subscriptionPlan)
                        <form method="POST" action="{{ route('student.subscriptions.renew', $expiredSubscription ?? $expiringSubscription) }}">
                            @csrf
                            <x-button type="submit">Renew Plan</x-button>
                        </form>
                    @endif
                </div>
            </x-card>
        @endif

        {{-- Continue learning --}}
        <div class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Keep going</p>
                    <h2 class="mt-1 text-xl font-extrabold text-mk-navy">Continue learning</h2>
                </div>
                <x-button :href="route('student.my-courses')" variant="secondary" size="sm">All courses</x-button>
            </div>

            @if ($enrolledCourses->isEmpty())
                <x-empty-state
                    icon="courses"
                    title="No active courses yet"
                    description="Browse the catalog and enroll to start your first course."
                    action-label="Browse Courses"
                    :action-href="route('courses')"
                />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($enrolledCourses as $item)
                        <x-course-progress-card
                            :course="$item['course']"
                            :href="route('student.courses.learn', $item['course'])"
                            :progress="$item['progress']"
                            action-label="Resume"
                        >
                            <x-slot:meta>
                                <x-badge tone="blue">{{ $item['course']->level }}</x-badge>
                            </x-slot:meta>
                        </x-course-progress-card>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Notifications + reminders --}}
        <div class="grid gap-5 lg:grid-cols-2">
            <x-card data-testid="student-dashboard-card" class="min-w-0">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <x-badge tone="blue">Notifications</x-badge>
                        <h3 class="mt-3 text-lg font-bold text-mk-navy">{{ $unreadNotificationsCount }} unread</h3>
                    </div>
                    <x-button :href="route('student.notifications')" size="sm" variant="secondary">View all</x-button>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse ($unreadNotifications as $notification)
                        <div class="rounded-mk-md border border-slate-100 bg-slate-50 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge tone="gold">{{ str_replace('_', ' ', $notification->category) }}</x-badge>
                                <span class="text-xs font-semibold text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 font-bold text-mk-navy">{{ $notification->title }}</p>
                            <p class="mt-0.5 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                        </div>
                    @empty
                        <x-empty-state
                            icon="notifications"
                            title="No unread notifications"
                            description="Updates and reminders will appear here as soon as there's something new."
                        />
                    @endforelse
                </div>
            </x-card>

            <x-card data-testid="student-dashboard-card" class="min-w-0">
                <x-badge tone="gold">Reminders</x-badge>
                <h3 class="mt-3 text-lg font-bold text-mk-navy">Upcoming priorities</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Live classes</p>
                        @forelse ($upcomingLiveClasses as $liveClass)
                            <p class="mt-2 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $liveClass->title }}</span><br>{{ $liveClass->starts_at->format('M j, Y g:i A') }}</p>
                        @empty
                            <p class="mt-2 text-sm leading-6 text-slate-600">None scheduled.</p>
                        @endforelse
                    </div>
                    <div class="rounded-mk-md bg-slate-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Assignments</p>
                        @forelse ($upcomingAssignmentDeadlines as $item)
                            <p class="mt-2 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $item['assignment']->title }}</span><br>Due {{ $item['due_at']->format('M j, Y') }}</p>
                        @empty
                            <p class="mt-2 text-sm leading-6 text-slate-600">No deadlines.</p>
                        @endforelse
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-dashboard-layout>
