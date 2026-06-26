<x-layouts.app title="Pricing">
    <section class="bg-white py-16">
        <div class="mk-container">
            <x-section-header
                align="center"
                eyebrow="Pricing"
                title="Simple plans for future course access"
                description="Flexible access options for students, families, and learning groups."
            />
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-5 lg:grid-cols-3">
            @foreach ($plans as $plan)
                @php
                    $isDatabasePlan = ($usingDatabasePlans ?? false);
                    $name = $isDatabasePlan ? $plan->name : $plan['name'];
                    $price = $isDatabasePlan ? $plan->priceLabel() : $plan['price'];
                    $description = $isDatabasePlan ? ($plan->description ?? 'Flexible MK Scholars access for selected courses.') : $plan['description'];
                    $features = $isDatabasePlan ? ($plan->features ?: []) : $plan['features'];
                    $highlighted = $isDatabasePlan ? $loop->iteration === 2 : ($plan['highlighted'] ?? false);
                @endphp
                <x-card :highlighted="$highlighted" class="flex h-full flex-col">
                    @if ($highlighted)
                        <x-badge>Recommended</x-badge>
                    @else
                        <x-badge tone="gray">Plan</x-badge>
                    @endif
                    <h3 class="mt-5 text-2xl font-extrabold text-mk-navy">{{ $name }}</h3>
                    <p class="mt-2 text-3xl font-extrabold text-mk-navy">{{ $price }}</p>
                    @if ($isDatabasePlan)
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-badge tone="blue">{{ str_replace('_', ' ', $plan->billing_cycle) }}</x-badge>
                            <x-badge tone="gray">{{ $plan->duration_days ? $plan->duration_days.' days' : $plan->durationDays().' days' }}</x-badge>
                            <x-badge tone="gold">{{ $plan->courses_count }} courses</x-badge>
                        </div>
                    @endif
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $description }}</p>
                    <ul class="mt-6 grid flex-1 gap-3 text-sm text-slate-600">
                        @forelse ($features as $feature)
                            <li class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-mk-gold"></span>
                                <span>{{ $feature }}</span>
                            </li>
                        @empty
                            <li class="flex gap-3">
                                <span class="mt-1 h-2 w-2 rounded-full bg-mk-gold"></span>
                                <span>Access included courses after manual payment approval.</span>
                            </li>
                        @endforelse
                    </ul>
                    @if ($isDatabasePlan)
                        @auth
                            @if (auth()->user()->role === \App\Models\User::ROLE_STUDENT)
                                <form method="POST" action="{{ route('subscriptions.choose', $plan) }}" class="mt-8">
                                    @csrf
                                    <x-button type="submit" class="w-full" :variant="$highlighted ? 'primary' : 'secondary'">Choose Plan</x-button>
                                </form>
                            @else
                                <x-button :href="route('pricing')" class="mt-8 w-full" :variant="$highlighted ? 'primary' : 'secondary'">View Plan</x-button>
                            @endif
                        @else
                            <x-button :href="route('login')" class="mt-8 w-full" :variant="$highlighted ? 'primary' : 'secondary'">Login to Choose</x-button>
                        @endauth
                    @else
                        <x-button :href="route('contact')" class="mt-8 w-full" :variant="$highlighted ? 'primary' : 'secondary'">Contact us</x-button>
                    @endif
                </x-card>
            @endforeach
        </div>
    </section>
</x-layouts.app>
