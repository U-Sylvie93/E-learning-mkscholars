<x-layouts.app :title="$paper->title" description="Entrance exam past paper details.">
    <section class="bg-slate-50 py-12 sm:py-16">
        <div class="mk-container">
            <div class="mb-6">
                <x-button :href="route('entrance-exam-academy.index')" variant="secondary">Back to Entrance Exam Academy</x-button>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <x-card>
                    <div class="flex flex-wrap gap-2">
                        @if ($paper->exam_year)
                            <x-badge tone="blue">{{ $paper->exam_year }}</x-badge>
                        @endif
                        @if ($paper->exam_type)
                            <x-badge tone="gray">{{ $paper->exam_type }}</x-badge>
                        @endif
                        <x-badge :tone="$paper->isFree() ? 'green' : 'gold'">{{ $paper->priceLabel() }}</x-badge>
                    </div>
                    <h1 class="mt-4 text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">{{ $paper->title }}</h1>
                    @if ($paper->description)
                        <p class="mt-4 text-sm leading-7 text-slate-600">{{ $paper->description }}</p>
                    @endif
                    @if (filled($renderedInstructions))
                        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <h2 class="text-xl font-extrabold text-mk-navy">Before you read</h2>
                            <div class="mk-rich-content mt-4">{!! $renderedInstructions !!}</div>
                        </div>
                    @endif
                    @if ($errors->has('payment'))
                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">{{ $errors->first('payment') }}</div>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        @guest
                            <x-button :href="route('register')" size="lg">Register to Continue</x-button>
                        @else
                            @if ($hasAccess)
                                <x-button :href="route('entrance-exam-academy.papers.view', $paper)" size="lg">Read Paper</x-button>
                            @elseif ($payment && in_array($payment->status, [\App\Models\Payment::STATUS_PENDING, \App\Models\Payment::STATUS_SUBMITTED], true))
                                <x-button :href="route('student.payments.show', $payment)" size="lg" variant="secondary">Payment Pending</x-button>
                            @elseif ($payment && $payment->status === \App\Models\Payment::STATUS_REJECTED)
                                <x-button :href="route('student.payments.show', $payment)" size="lg">Pay Again</x-button>
                            @else
                                <form method="POST" action="{{ route('entrance-exam-academy.papers.pay', $paper) }}">
                                    @csrf
                                    <x-button type="submit" size="lg">Pay Now</x-button>
                                </form>
                            @endif
                        @endguest
                    </div>
                </x-card>

                <x-card>
                    <h2 class="text-xl font-extrabold text-mk-navy">Paper details</h2>
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Institution</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->institution?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Program / Faculty</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->program?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Subject</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->subject?->name ?? 'General' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Price</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->priceLabel() }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Year</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->exam_year ?? 'Not specified' }}</dd></div>
                        <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Exam type</dt><dd class="mt-1 font-bold text-mk-navy">{{ $paper->exam_type ?? 'Not specified' }}</dd></div>
                    </dl>
                </x-card>
            </div>
        </div>
    </section>
</x-layouts.app>
