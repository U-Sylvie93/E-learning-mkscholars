<x-dashboard-layout role="student" title="My Subscriptions" description="MK Scholars student subscriptions.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Subscriptions"
                description="Track manual subscription plans, payments, included courses, and expiry dates."
            />
            <x-button :href="route('pricing')" variant="secondary">Choose Plan</x-button>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            <div class="mb-8 flex flex-wrap gap-3">
                <x-button :href="route('student.subscriptions')" size="sm" :variant="empty($activeStatus) ? 'primary' : 'secondary'">All</x-button>
                @foreach ($statuses as $status)
                    <x-button
                        :href="route('student.subscriptions', ['status' => $status])"
                        size="sm"
                        :variant="$activeStatus === $status ? 'primary' : 'secondary'"
                    >
                        {{ str_replace('_', ' ', $status) }}
                    </x-button>
                @endforeach
            </div>

            @if ($subscriptions->isEmpty())
                <x-card>
                    <x-badge tone="gray">Empty</x-badge>
                    <h2 class="mt-5 text-xl font-bold text-mk-navy">No subscriptions yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Choose a subscription plan from pricing to create a manual payment request.</p>
                    <x-button :href="route('pricing')" class="mt-6">View Pricing</x-button>
                </x-card>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($subscriptions as $subscription)
                        @php
                            $tone = $subscription->statusTone();
                            $label = $subscription->statusLabel();
                        @endphp
                        <x-card class="flex h-full flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$tone">{{ str_replace('_', ' ', $label) }}</x-badge>
                                @if ($subscription->isExpiringSoon())
                                    <x-badge tone="gold">Expiring soon</x-badge>
                                @endif
                                @if ($subscription->payment)
                                    <x-badge tone="gray">Payment {{ str_replace('_', ' ', $subscription->payment->status) }}</x-badge>
                                @endif
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $subscription->subscriptionPlan?->name ?? 'Subscription plan' }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">
                                {{ $subscription->subscriptionPlan?->priceLabel() ?? 'Manual payment' }}
                            </p>
                            <p class="mt-4 flex-1 text-sm leading-6 text-slate-600">
                                {{ $subscription->subscriptionPlan?->courses->count() ?? 0 }} included courses.
                                @if ($subscription->ends_at)
                                    Expires {{ $subscription->ends_at->format('M j, Y') }}.
                                @endif
                            </p>
                            <x-button :href="route('student.subscriptions.show', $subscription)" class="mt-6 w-full">Open Subscription</x-button>
                            @if ($subscription->subscriptionPlan && in_array($label, [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_EXPIRED], true))
                                <form method="POST" action="{{ route('student.subscriptions.renew', $subscription) }}" class="mt-3">
                                    @csrf
                                    <x-button type="submit" variant="secondary" class="w-full">Renew Plan</x-button>
                                </form>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>

