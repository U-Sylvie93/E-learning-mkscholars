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
                            $course = $item['course'];
                            $academy = $course->academy?->name ?? 'MK Scholars';
                            $completion = $item['completion'];
                            $offersCertificate = $course->offersCertificate();
                            $completed = (bool) $completion->completed_at || $completion->is_eligible_for_certificate;
                            $learnHref = route('student.courses.learn', $course);
                            $certificateBadge = $offersCertificate
                                ? ($completion->is_eligible_for_certificate ? 'Certificate eligible' : $completion->lesson_percentage.'% lessons')
                                : $completion->lesson_percentage.'% lessons';
                            $certificateBadgeTone = $offersCertificate && $completion->is_eligible_for_certificate ? 'success' : 'gray';
                        @endphp
                        <x-card class="flex h-full flex-col p-5">
                            <div class="flex flex-1 flex-col">
                                <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $academy }}</p>
                                <h3 class="mt-2 line-clamp-2 break-words text-lg font-black tracking-normal text-mk-navy">
                                    <a href="{{ $learnHref }}" class="mk-focus rounded-sm hover:text-mk-blue">{{ $course->title }}</a>
                                </h3>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <x-badge :tone="$completed ? 'success' : 'green'">{{ $completed ? 'Completed' : $item['access_label'] }}</x-badge>
                                    <x-badge tone="gray">{{ $course->instructor?->name ?? 'MK Scholars' }}</x-badge>
                                    <x-badge :tone="$certificateBadgeTone">{{ $certificateBadge }}</x-badge>
                                </div>
                                <div class="mt-4">
                                    <div class="flex items-center justify-between text-xs font-bold">
                                        <span class="text-slate-500">Progress</span>
                                        <span class="text-mk-navy">{{ $item['progress'] }}%</span>
                                    </div>
                                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-mk-gold transition-[width] duration-500" style="width: {{ $item['progress'] }}%"></div>
                                    </div>
                                </div>
                                <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                    <x-button :href="$learnHref" :variant="$completed ? 'secondary' : 'primary'" size="sm" class="flex-1">
                                        {{ $completed ? 'Completed' : 'Continue Learning' }}
                                    </x-button>
                                </div>
                            </div>
                        </x-card>
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

            @php
                $mergedUnpaid = $unpaidCourses->concat($availablePaidCourses ?? collect());
            @endphp
            @if ($mergedUnpaid->isEmpty())
                <x-empty-state
                    icon="payments"
                    title="No unpaid courses found."
                    description="Courses awaiting payment or renewal will appear here."
                />
            @else
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($mergedUnpaid as $item)
                        @php
                            $course = $item['course'];
                            $academy = $course->academy?->name ?? 'MK Scholars';
                            $isPending = $item['payment'] && in_array($item['payment']->status, [\App\Models\Payment::STATUS_PENDING, \App\Models\Payment::STATUS_SUBMITTED], true);
                            $paymentHref = $item['pay_href'] ?? route('courses.show', $course->slug);
                        @endphp
                        <x-card class="flex h-full flex-col p-5">
                            <div class="flex flex-1 flex-col">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $academy }}</p>
                                    <x-badge :tone="$item['status_tone']">{{ $item['status_label'] }}</x-badge>
                                </div>
                                <h3 class="mt-2 line-clamp-2 break-words text-lg font-black tracking-normal text-mk-navy">
                                    <a href="{{ route('courses.show', $course->slug) }}" class="mk-focus rounded-sm hover:text-mk-blue">{{ $course->title }}</a>
                                </h3>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <x-badge tone="gray">{{ $course->instructor?->name ?? 'MK Scholars' }}</x-badge>
                                    <x-badge tone="blue">{{ $course->priceLabel() }}</x-badge>
                                </div>
                                <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">{{ $item['reason'] }}</p>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <x-button :href="$paymentHref" size="sm" :variant="$isPending ? 'secondary' : 'primary'" class="flex-1">
                                        {{ $item['pay_label'] }}
                                    </x-button>
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
