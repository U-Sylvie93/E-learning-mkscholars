<x-dashboard-layout role="student" :title="$subscription->subscriptionPlan?->name ?? 'Subscription'" description="MK Scholars student subscription details.">
    @php
        $plan = $subscription->subscriptionPlan;
        $payment = $subscription->payment;
        $statusTone = $subscription->statusTone();
        $statusLabel = $subscription->statusLabel();
    @endphp

    <section class="bg-white py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[1fr_0.45fr]">
            <div>
                <div class="flex flex-wrap gap-2">
                    <x-badge :tone="$statusTone">{{ str_replace('_', ' ', $statusLabel) }}</x-badge>
                    @if ($subscription->isExpiringSoon())
                        <x-badge tone="gold">Expiring soon</x-badge>
                    @endif
                    @if ($payment)
                        <x-badge tone="gray">Payment {{ str_replace('_', ' ', $payment->status) }}</x-badge>
                    @endif
                </div>
                <h1 class="mt-5 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">{{ $plan?->name ?? 'Subscription plan' }}</h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">{{ $plan?->description ?? 'Manual subscription access for selected MK Scholars courses.' }}</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if ($payment && in_array($payment->status, [\App\Models\Payment::STATUS_PENDING, \App\Models\Payment::STATUS_SUBMITTED, \App\Models\Payment::STATUS_REJECTED], true))
                        <x-button :href="route('student.payments.show', $payment)">Upload Payment Proof</x-button>
                    @endif
                    @if ($plan && in_array($statusLabel, [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_EXPIRED], true))
                        <form method="POST" action="{{ route('student.subscriptions.renew', $subscription) }}">
                            @csrf
                            <x-button type="submit" variant="secondary">Renew Plan</x-button>
                        </form>
                    @endif
                    <x-button :href="route('student.subscriptions')" variant="secondary">Back to Subscriptions</x-button>
                </div>
            </div>

            <x-card>
                <x-badge tone="blue">Plan summary</x-badge>
                <dl class="mt-6 space-y-4">
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Price</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $plan?->priceLabel() ?? 'Manual' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Billing</dt>
                        <dd class="text-sm font-bold capitalize text-mk-navy">{{ str_replace('_', ' ', $plan?->billing_cycle ?? 'custom') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Starts</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $subscription->starts_at?->format('M j, Y') ?? 'After approval' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-slate-500">Ends</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $subscription->ends_at?->format('M j, Y') ?? 'Pending' }}</dd>
                    </div>
                </dl>
                @if ($subscription->isExpiringSoon())
                    <div class="mt-6 rounded-lg border border-mk-gold/40 bg-mk-goldSoft p-4 text-sm leading-6 text-mk-navy">
                        This subscription expires soon. Renew now to keep access after {{ $subscription->ends_at->format('M j, Y') }}.
                    </div>
                @elseif ($subscription->isExpired())
                    <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                        This subscription has expired. Renew it to restore included course access after admin approval.
                    </div>
                @endif
            </x-card>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <x-card>
                <x-section-header eyebrow="Included courses" title="Courses in this plan" />
                <div class="mt-6 grid gap-4">
                    @forelse ($plan?->courses ?? collect() as $course)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <x-badge tone="blue">{{ $course->academy?->name ?? 'MK Scholars' }}</x-badge>
                                    <h3 class="mt-3 font-bold text-mk-navy">{{ $course->title }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $course->level }} / {{ $course->duration }}</p>
                                </div>
                                @if ($subscription->isActive())
                                    <x-button :href="route('student.courses.learn', $course)" size="sm">Start Learning</x-button>
                                @else
                                    <x-button :href="route('courses.show', $course->slug)" size="sm" variant="secondary">View Course</x-button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm leading-6 text-slate-600">No courses have been attached to this plan yet.</p>
                    @endforelse
                </div>
            </x-card>

            <div class="space-y-6">
                <x-card>
                    <x-section-header eyebrow="Features" title="What is included" />
                    <ul class="mt-6 grid gap-3 text-sm text-slate-600">
                        @forelse ($plan?->features ?? [] as $feature)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-mk-gold"></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @empty
                            <li>Plan features will appear when an admin adds them.</li>
                        @endforelse
                    </ul>
                </x-card>

                <x-card>
                    <x-section-header eyebrow="Payment" title="Manual review" />
                    @if ($payment)
                        <p class="mt-5 text-sm leading-6 text-slate-600">
                            Current payment status:
                            <span class="font-bold text-mk-navy">{{ str_replace('_', ' ', $payment->status) }}</span>
                        </p>
                        <x-button :href="route('student.payments.show', $payment)" class="mt-6 w-full" variant="secondary">Open Payment</x-button>
                    @else
                        <p class="mt-5 text-sm leading-6 text-slate-600">No payment is linked to this subscription yet.</p>
                    @endif
                </x-card>
            </div>
        </div>
    </section>
</x-dashboard-layout>

