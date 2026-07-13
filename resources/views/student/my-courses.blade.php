<x-dashboard-layout role="student" title="My Courses" description="MK Scholars enrolled and payment-pending courses.">
    <div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Courses"
                description="Continue active courses and resolve payments without leaving your workspace."
            />
            <div class="flex flex-wrap gap-2">
                <x-badge tone="green">{{ $activeCourses->count() }} active</x-badge>
                <x-badge tone="warning">{{ $unpaidCourses->count() }} unpaid</x-badge>
            </div>
        </div>

        <section class="space-y-4" aria-labelledby="active-courses-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Paid / Active Courses</p>
                    <h2 id="active-courses-heading" class="mt-1 text-xl font-extrabold text-mk-navy">Ready to learn</h2>
                </div>
                <x-badge tone="gray">{{ $activeCourses->count() }} courses</x-badge>
            </div>

            @if ($activeCourses->isEmpty())
                <x-empty-state
                    icon="courses"
                    title="No active courses yet."
                    description="Paid, free, or subscription courses with active access will appear here."
                    action-label="Browse Courses"
                    :action-href="route('courses')"
                />
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($activeCourses as $item)
                        @php
                            $completion = $item['completion'];
                            $certificate = $item['certificate'];
                            $completed = (bool) $completion->completed_at || $completion->is_eligible_for_certificate;
                            $certificateLabel = match ($certificate?->status) {
                                \App\Models\Certificate::STATUS_PENDING => 'Certificate Pending Approval',
                                \App\Models\Certificate::STATUS_ISSUED => 'Certificate Issued',
                                \App\Models\Certificate::STATUS_REJECTED => 'Certificate Rejected',
                                \App\Models\Certificate::STATUS_REVOKED => 'Certificate Revoked',
                                default => $completed ? 'Certificate Not Requested/Not Available' : null,
                            };
                            $certificateTone = match ($certificate?->status) {
                                \App\Models\Certificate::STATUS_ISSUED => 'success',
                                \App\Models\Certificate::STATUS_REJECTED,
                                \App\Models\Certificate::STATUS_REVOKED => 'danger',
                                \App\Models\Certificate::STATUS_PENDING => 'warning',
                                default => 'gray',
                            };
                        @endphp
                        <x-course-progress-card
                            :course="$item['course']"
                            :href="route('student.courses.learn', $item['course'])"
                            :progress="$item['progress']"
                            :action-label="$completed ? 'Completed' : 'Continue Learning'"
                            :action-variant="$completed ? 'secondary' : 'primary'"
                        >
                            <x-slot:meta>
                                <x-badge tone="blue">{{ $item['course']->level }}</x-badge>
                                <x-badge :tone="$completed ? 'success' : 'green'">{{ $completed ? 'Completed' : $item['access_label'] }}</x-badge>
                                <x-badge tone="gray">{{ $item['course']->instructor?->name ?? 'MK Scholars' }}</x-badge>
                                <x-badge :tone="$completion->is_eligible_for_certificate ? 'success' : 'gray'">
                                    {{ $completion->is_eligible_for_certificate ? 'Certificate eligible' : $completion->lesson_percentage.'% lessons' }}
                                </x-badge>
                                @if ($certificateLabel)
                                    <x-badge :tone="$certificateTone">{{ $certificateLabel }}</x-badge>
                                @endif
                            </x-slot:meta>
                            @if ($certificate?->status === \App\Models\Certificate::STATUS_ISSUED)
                                <x-slot:actions>
                                    <x-button :href="route('student.certificates.show', $certificate)" variant="secondary" size="sm">View Certificate</x-button>
                                </x-slot:actions>
                            @endif
                        </x-course-progress-card>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="space-y-4" aria-labelledby="unpaid-courses-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Unpaid Courses / Courses Awaiting Payment</p>
                    <h2 id="unpaid-courses-heading" class="mt-1 text-xl font-extrabold text-mk-navy">Payment needed</h2>
                </div>
                <x-badge tone="gray">{{ $unpaidCourses->count() }} courses</x-badge>
            </div>

            @if ($unpaidCourses->isEmpty())
                <x-empty-state
                    icon="payments"
                    title="No unpaid courses found."
                    description="Courses awaiting payment or renewal will appear here."
                />
            @else
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($unpaidCourses as $item)
                        @php
                            $course = $item['course'];
                            $image = $course->coverImageUrl();
                            $academy = $course->academy?->name ?? 'MK Scholars';
                            $academyIcon = $course->academy?->safeIcon() ?? \App\Models\Academy::ICON_BOOK_OPEN;
                            $isPending = $item['payment'] && in_array($item['payment']->status, [\App\Models\Payment::STATUS_PENDING, \App\Models\Payment::STATUS_SUBMITTED], true);
                        @endphp
                        <x-card class="group flex h-full flex-col overflow-hidden p-0">
                            <a href="{{ route('courses.show', $course->slug) }}" class="relative block aspect-[16/9] overflow-hidden bg-mk-navy mk-focus" aria-label="{{ $course->title }}">
                                @if ($image)
                                    <img src="{{ $image }}" alt="{{ $course->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <span class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_38%),linear-gradient(135deg,#073653_0%,#0e4a72_60%,#102a3a_100%)]"></span>
                                    <span class="absolute inset-0 flex items-center justify-center text-mk-gold">
                                        <x-academy-icon :name="$academyIcon" class="h-12 w-12" />
                                    </span>
                                @endif
                                <span class="absolute right-3 top-3"><x-badge :tone="$item['status_tone']">{{ $item['status_label'] }}</x-badge></span>
                            </a>

                            <div class="flex flex-1 flex-col p-5">
                                <p class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-mk-gold">
                                    <x-academy-icon :name="$academyIcon" class="h-4 w-4" />{{ $academy }}
                                </p>
                                <h3 class="mt-2 line-clamp-2 break-words text-lg font-black tracking-normal text-mk-navy">
                                    <a href="{{ route('courses.show', $course->slug) }}" class="mk-focus rounded-sm hover:text-mk-blue">{{ $course->title }}</a>
                                </h3>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <x-badge tone="gray">{{ $course->instructor?->name ?? 'MK Scholars' }}</x-badge>
                                    <x-badge tone="blue">{{ $course->priceLabel() }}</x-badge>
                                    @if ($item['subscription']?->subscriptionPlan)
                                        <x-badge tone="gray">{{ $item['subscription']->subscriptionPlan->name }}</x-badge>
                                    @endif
                                </div>

                                <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ $item['reason'] }}</p>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    @if ($item['pay_form_route'])
                                        <form method="POST" action="{{ $item['pay_form_route'] }}" class="flex-1">
                                            @csrf
                                            <x-button type="submit" size="sm" class="w-full">{{ $item['pay_label'] }}</x-button>
                                        </form>
                                    @else
                                        <x-button :href="$item['pay_href']" size="sm" :variant="$isPending ? 'secondary' : 'primary'" class="flex-1">
                                            {{ $item['pay_label'] }}
                                        </x-button>
                                    @endif
                                    <x-button :href="route('courses.show', $course->slug)" variant="secondary" size="sm">View Details</x-button>
                                </div>
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-dashboard-layout>
