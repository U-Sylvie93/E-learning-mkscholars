@php
    $paymentTitle = $payment->payableTitle();
    $canUpload = in_array($payment->status, [
        \App\Models\Payment::STATUS_PENDING,
        \App\Models\Payment::STATUS_SUBMITTED,
        \App\Models\Payment::STATUS_REJECTED,
    ], true);
    $statusTone = match ($payment->status) {
        \App\Models\Payment::STATUS_APPROVED => 'green',
        \App\Models\Payment::STATUS_SUBMITTED => 'blue',
        default => 'gold',
    };
@endphp

<x-dashboard-layout role="student" :title="'Payment - '.$paymentTitle" description="MK Scholars manual payment proof upload.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Manual payment"
                :title="$paymentTitle"
                :description="$payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? 'Upload payment proof so an MK Scholars admin can review and activate your subscription.' : ($payment->purpose === \App\Models\Payment::PURPOSE_ENTRANCE_EXAM ? 'Upload your payment proof so an MK Scholars admin can review and unlock this entrance exam paper.' : 'Upload your payment proof so an MK Scholars admin can review and activate course access.')"
            />
            <x-badge :tone="$statusTone">{{ str_replace('_', ' ', $payment->status) }}</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[0.95fr_0.55fr]">
            <div class="space-y-6">
                <x-card>
                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Amount</p>
                            <p class="mt-2 text-2xl font-extrabold text-mk-navy">{{ number_format((float) $payment->amount, 0) }} {{ $payment->currency }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Provider</p>
                            <p class="mt-2 font-bold text-mk-navy">{{ $payment->providerLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $payment->purpose === \App\Models\Payment::PURPOSE_ENTRANCE_EXAM ? 'Past Paper' : ($payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? 'Subscription' : 'Course') }}</p>
                            <p class="mt-2 font-bold text-mk-navy">{{ $paymentTitle }}</p>
                        </div>
                    </div>

                    @if ($payment->reference)
                        <div class="mt-6 rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Legacy Reference</p>
                            <p class="mt-2 break-words text-sm font-bold text-mk-navy">{{ $payment->reference }}</p>
                        </div>
                    @endif

                    @if ($payment->admin_notes)
                        <div class="mt-6 rounded-lg border border-mk-gold/30 bg-mk-goldSoft p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-navy">Admin notes</p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $payment->admin_notes }}</p>
                        </div>
                    @endif

                    @if ($payment->proof_path)
                        <p class="mt-6 text-sm leading-6 text-slate-600">
                            Proof uploaded. Admin review status:
                            <span class="font-bold text-mk-navy">{{ str_replace('_', ' ', $payment->status) }}</span>
                        </p>
                    @endif
                </x-card>

                @if ($canUpload)
                    <x-card>
                        <x-badge tone="blue">Upload proof</x-badge>
                        <h2 class="mt-4 text-xl font-bold text-mk-navy">Submit payment proof</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Accepted files: PDF, PNG, JPG, or JPEG up to 10MB.</p>

                        <form method="POST" action="{{ route('student.payments.submit', $payment) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                            @csrf
                            <div>
                                <label for="payment_method_id" class="text-sm font-bold text-mk-navy">Payment method</label>
                                <select id="payment_method_id" name="payment_method_id" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                                    <option value="">Choose method</option>
                                    @foreach ($paymentMethods as $method)
                                        <option value="{{ $method->id }}" @selected(old('payment_method_id', $payment->payment_method_id) == $method->id)>{{ $method->name }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method_id')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="proof_file" class="text-sm font-bold text-mk-navy">Proof file</label>
                                <input id="proof_file" name="proof_file" type="file" class="mt-2 w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm" required>
                                @error('proof_file')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <x-button type="submit">{{ $payment->status === \App\Models\Payment::STATUS_REJECTED ? 'Resubmit Proof' : 'Submit Proof' }}</x-button>
                        </form>
                    </x-card>
                @else
                    <x-card>
                        <x-badge :tone="$payment->status === \App\Models\Payment::STATUS_APPROVED ? 'green' : 'gray'">Reviewed</x-badge>
                        <h2 class="mt-4 text-xl font-bold text-mk-navy">{{ $payment->status === \App\Models\Payment::STATUS_APPROVED ? 'Payment approved' : 'Payment closed' }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $payment->status === \App\Models\Payment::STATUS_APPROVED ? ($payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? 'Your subscription is active. Open subscriptions to view included courses.' : ($payment->purpose === \App\Models\Payment::PURPOSE_ENTRANCE_EXAM ? 'Your entrance exam paper access is active.' : 'Your course access is active. Continue learning when you are ready.')) : 'This payment is no longer accepting proof uploads.' }}
                        </p>
                        @if ($payment->course && $payment->status === \App\Models\Payment::STATUS_APPROVED)
                            <x-button :href="route('student.courses.learn', $payment->course)" class="mt-6">Continue Learning</x-button>
                        @elseif ($payment->purpose === \App\Models\Payment::PURPOSE_ENTRANCE_EXAM && $payment->entranceExamPastPaper)
                            <x-button :href="route('entrance-exam-academy.papers.show', $payment->entranceExamPastPaper)" class="mt-6">Open Past Paper</x-button>
                        @elseif ($payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION && $payment->subscription)
                            <x-button :href="route('student.subscriptions.show', $payment->subscription)" class="mt-6">View Subscription</x-button>
                        @endif
                    </x-card>
                @endif
            </div>

            <div class="space-y-6">
                <x-card>
                    <x-badge tone="gray">Instructions</x-badge>
                    <div class="mt-6 space-y-4">
                        @forelse ($paymentMethods as $method)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <p class="font-bold text-mk-navy">{{ $method->name }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $method->type }}</p>
                                @if ($method->account_name || $method->account_number)
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $method->account_name }} {{ $method->account_number }}</p>
                                @endif
                                @if ($method->instructions)
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $method->instructions }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">Payment instructions are not available yet. Please check back after an admin adds a method.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card>
                    <x-badge tone="gold">Next step</x-badge>
                    <p class="mt-4 text-sm leading-6 text-slate-600">
                        {{ $payment->purpose === \App\Models\Payment::PURPOSE_SUBSCRIPTION ? 'After your proof is approved, MK Scholars activates your subscription and included course access.' : ($payment->purpose === \App\Models\Payment::PURPOSE_ENTRANCE_EXAM ? 'After your proof is approved, MK Scholars unlocks this entrance exam past paper.' : 'After your proof is approved, MK Scholars automatically activates your course enrollment.') }}
                    </p>
                    <x-button :href="route('student.payments')" variant="secondary" size="sm" class="mt-5">Back to Payments</x-button>
                </x-card>
            </div>
        </div>
    </section>
</x-dashboard-layout>
