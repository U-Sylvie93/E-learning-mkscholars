<x-dashboard-layout role="student" title="My Payments" description="MK Scholars payment history.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Payments"
                description="Track manual course payments, upload proof, and review admin decisions."
            />
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-button :href="route('student.subscriptions')" variant="secondary">Subscriptions</x-button>
                <x-button :href="route('courses')" variant="secondary">Browse Courses</x-button>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[1fr_0.42fr]">
            <div>
                @if ($payments->isEmpty())
                    <x-card>
                        <h2 class="text-xl font-bold text-mk-navy">No payments yet</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Paid course payment requests will appear here after you choose Pay & Enroll.</p>
                        <x-button :href="route('courses')" class="mt-6">Explore Courses</x-button>
                    </x-card>
                @else
                    <div class="grid gap-5">
                        @foreach ($payments as $payment)
                            @php
                                $tone = match ($payment->status) {
                                    \App\Models\Payment::STATUS_APPROVED => 'green',
                                    \App\Models\Payment::STATUS_REJECTED => 'gold',
                                    \App\Models\Payment::STATUS_SUBMITTED => 'blue',
                                    default => 'gold',
                                };
                            @endphp
                            <x-card>
                                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-badge :tone="$tone">{{ str_replace('_', ' ', $payment->status) }}</x-badge>
                                            <x-badge tone="gray">{{ number_format((float) $payment->amount, 0) }} {{ $payment->currency }}</x-badge>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-mk-navy">
                                            {{ $payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? ($payment->subscription?->subscriptionPlan?->name ?? 'Subscription payment') : ($payment->course?->title ?? 'Course payment') }}
                                        </h2>
                                        <p class="mt-2 text-sm font-semibold text-mk-gold">
                                            {{ $payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? 'Subscription plan' : ($payment->course?->academy?->name ?? 'MK Scholars') }}
                                        </p>
                                        <p class="mt-3 text-sm leading-6 text-slate-600">
                                            {{ $payment->paymentMethod?->name ?? 'No method selected yet' }}
                                            @if ($payment->submitted_at)
                                                <span class="text-slate-400">/</span> Submitted {{ $payment->submitted_at->format('M j, Y') }}
                                            @endif
                                        </p>
                                    </div>
                                    <x-button :href="route('student.payments.show', $payment)" size="sm" class="w-full sm:w-auto">View Payment</x-button>
                                </div>
                            </x-card>
                        @endforeach
                    </div>
                @endif
            </div>

            <x-card>
                <x-badge tone="blue">Payment methods</x-badge>
                <h2 class="mt-4 text-xl font-bold text-mk-navy">Use an active method</h2>
                <div class="mt-6 space-y-4">
                    @forelse ($paymentMethods as $method)
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="font-bold text-mk-navy">{{ $method->name }}</p>
                            <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $method->type }}</p>
                            @if ($method->account_name || $method->account_number)
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $method->account_name }} {{ $method->account_number }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm leading-6 text-slate-600">Payment instructions will appear after an admin adds active methods.</p>
                    @endforelse
                </div>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>

