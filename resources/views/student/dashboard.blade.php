<x-dashboard-layout role="student" title="Student Dashboard" description="MK Scholars student dashboard.">
    <div class="space-y-6">
            <x-section-header
                eyebrow="Student dashboard"
                title="Your learning home"
                description="A protected placeholder for future courses, progress tracking, assignments, and study plans."
            />

            <x-card highlighted data-testid="student-dashboard-card" class="min-w-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <x-badge tone="gold">Welcome back</x-badge>
                        <h2 class="mt-4 text-2xl font-extrabold text-mk-navy">{{ auth()->user()->name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Continue your courses, check payments, review reminders, and keep your MK Scholars progress moving from one focused workspace.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                        <x-button :href="route('student.my-courses')">Continue Learning</x-button>
                        <x-button :href="route('student.notifications')" variant="secondary">Notifications</x-button>
                    </div>
                </div>
            </x-card>

            <div data-testid="student-dashboard-grid" class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge>Courses</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Active courses</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Course enrollment and lesson progress will appear here later.</p>
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="blue">Progress</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Study momentum</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Future performance summaries and goals will live in this area.</p>
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="green">Support</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $mentorAssignment?->mentor?->name ?? 'Mentor guidance' }}</h3>
                    @if ($mentorAssignment)
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Next check-in:
                            <span class="font-bold text-mk-navy">{{ $nextCheckIn?->scheduled_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</span>
                        </p>
                        @if ($latestFeedback?->mentor_feedback)
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $latestFeedback->mentor_feedback }}</p>
                        @endif
                        <x-button :href="route('student.mentorship')" size="sm" class="mt-5">View Mentorship</x-button>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">Your mentor assignment and weekly check-ins will appear here once assigned.</p>
                    @endif
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="gold">Certificates</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $certificateCount }} issued</h3>
                    @if ($latestCertificate)
                        <p class="mt-3 text-sm leading-6 text-slate-600">Latest: {{ $latestCertificate->course_title }}</p>
                        <x-button :href="route('student.certificates.show', $latestCertificate)" size="sm" class="mt-5">View Certificate</x-button>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">Issued certificates will appear here after course completion.</p>
                    @endif
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="blue">Opportunities</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $pendingApplicationsCount }} pending</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $newOpportunitiesCount }} published opportunities.
                        @if ($nearestOpportunityDeadline)
                            Nearest deadline: {{ $nearestOpportunityDeadline->deadline->format('M j, Y') }}.
                        @endif
                    </p>
                    <x-button :href="route('student.applications')" size="sm" class="mt-5">Track Applications</x-button>
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="gold">Payments</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $pendingPaymentsCount }} pending</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $approvedPaymentsCount }} approved payments.
                        @if ($rejectedPaymentsCount)
                            {{ $rejectedPaymentsCount }} need attention.
                        @endif
                    </p>
                    <x-button :href="route('student.payments')" size="sm" class="mt-5">View Payments</x-button>
                </x-card>
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="blue">Subscription</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $activeSubscription?->subscriptionPlan?->name ?? 'No active plan' }}</h3>
                    @if ($activeSubscription)
                        <p class="mt-3 text-sm leading-6 text-slate-600">Expires {{ $activeSubscription->ends_at?->format('M j, Y') ?? 'when cancelled' }}.</p>
                    @elseif ($pendingSubscription)
                        <p class="mt-3 text-sm leading-6 text-slate-600">Pending: {{ $pendingSubscription->subscriptionPlan?->name }}.</p>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">Choose a plan for bundled course access.</p>
                    @endif
                    <x-button :href="route('student.subscriptions')" size="sm" class="mt-5">View Plans</x-button>
                </x-card>
            </div>

            @if ($expiringSubscription || $expiredSubscription)
                <div class="mt-6">
                    <x-card highlighted data-testid="student-dashboard-card" class="min-w-0">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <x-badge tone="gold">{{ $expiredSubscription ? 'Subscription expired' : 'Subscription expiring soon' }}</x-badge>
                                <h3 class="mt-4 text-xl font-bold text-mk-navy">
                                    {{ ($expiredSubscription ?? $expiringSubscription)?->subscriptionPlan?->name ?? 'Subscription plan' }}
                                </h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    @if ($expiredSubscription)
                                        Your subscription has expired. Renew it and upload payment proof to restore included paid course access.
                                    @else
                                        Your subscription expires on {{ $expiringSubscription->ends_at?->format('M j, Y') }}. Renew early to avoid losing included course access.
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
                </div>
            @endif

            @if (($coursesAwaitingReview ?? collect())->isNotEmpty())
                <div class="mt-6">
                    <x-card data-testid="student-dashboard-card" class="min-w-0">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <x-badge tone="gold">Feedback</x-badge>
                                <h3 class="mt-4 text-xl font-bold text-mk-navy">Help improve your courses</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Share a short review for courses you are already learning.</p>
                            </div>
                            <div class="grid gap-3 sm:min-w-72">
                                @foreach ($coursesAwaitingReview as $course)
                                    <a href="{{ route('student.courses.learn', $course) }}" class="rounded-lg border border-slate-100 bg-slate-50 p-3 text-sm font-bold text-mk-navy transition hover:border-mk-gold">
                                        {{ $course->title }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-card>
                </div>
            @endif

            <div class="mt-8 grid gap-5 lg:grid-cols-[0.8fr_1.2fr]">
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-badge tone="blue">Notifications</x-badge>
                            <h3 class="mt-5 text-xl font-bold text-mk-navy">{{ $unreadNotificationsCount }} unread</h3>
                        </div>
                        <x-button :href="route('student.notifications')" size="sm" variant="secondary">View All</x-button>
                    </div>
                    <div class="mt-6 space-y-3">
                        @forelse ($unreadNotifications as $notification)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge tone="gold">{{ str_replace('_', ' ', $notification->category) }}</x-badge>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-3 font-bold text-mk-navy">{{ $notification->title }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">No unread notifications right now.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="gold">Reminder center</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Upcoming priorities</h3>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Live classes</p>
                            @forelse ($upcomingLiveClasses as $liveClass)
                                <p class="mt-3 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $liveClass->title }}</span><br>{{ $liveClass->starts_at->format('M j, Y g:i A') }}</p>
                            @empty
                                <p class="mt-3 text-sm leading-6 text-slate-600">No upcoming live classes.</p>
                            @endforelse
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Assignments</p>
                            @forelse ($upcomingAssignmentDeadlines as $item)
                                <p class="mt-3 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $item['assignment']->title }}</span><br>Due {{ $item['due_at']->format('M j, Y') }}</p>
                            @empty
                                <p class="mt-3 text-sm leading-6 text-slate-600">No assignment deadlines.</p>
                            @endforelse
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Opportunities</p>
                            @if ($nearestOpportunityDeadline)
                                <p class="mt-3 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $nearestOpportunityDeadline->title }}</span><br>Deadline {{ $nearestOpportunityDeadline->deadline->format('M j, Y') }}</p>
                            @else
                                <p class="mt-3 text-sm leading-6 text-slate-600">No upcoming opportunity deadlines.</p>
                            @endif
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Mentorship</p>
                            @forelse ($upcomingMentorCheckIns as $checkIn)
                                <p class="mt-3 text-sm leading-6 text-slate-600"><span class="font-bold text-mk-navy">{{ $checkIn->topic }}</span><br>{{ $checkIn->scheduled_at?->format('M j, Y g:i A') ?? 'To be scheduled' }}</p>
                            @empty
                                <p class="mt-3 text-sm leading-6 text-slate-600">No upcoming mentor check-ins.</p>
                            @endforelse
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="mt-8 grid gap-5 lg:grid-cols-[1fr_0.8fr]">
                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <x-badge tone="gold">Recently published</x-badge>
                            <h3 class="mt-4 text-xl font-bold text-mk-navy">Fresh opportunities</h3>
                        </div>
                        <x-button :href="route('student.opportunities')" size="sm" variant="secondary">Browse</x-button>
                    </div>
                    <div class="mt-6 space-y-4">
                        @forelse ($recentOpportunities as $opportunity)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-badge tone="blue">{{ str_replace('_', ' ', $opportunity->type) }}</x-badge>
                                    <x-badge :tone="$opportunity->deadlineBadgeTone()">{{ $opportunity->deadlineBadge() }}</x-badge>
                                </div>
                                <p class="mt-3 font-bold text-mk-navy">{{ $opportunity->title }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $opportunity->deadline?->format('M j, Y') ?? 'Open deadline' }}</p>
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">Newly published opportunities will appear here.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card data-testid="student-dashboard-card" class="min-w-0">
                    <x-badge tone="gray">Documents</x-badge>
                    <h3 class="mt-5 text-xl font-bold text-mk-navy">Application files</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Upload reusable CVs, transcripts, passport files, letters, and test results for faster applications.</p>
                    <x-button :href="route('student.documents')" size="sm" class="mt-5">Open Document Center</x-button>
                </x-card>
            </div>
            </div>
</x-dashboard-layout>


