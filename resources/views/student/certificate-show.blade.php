<x-dashboard-layout role="student" :title="$certificate->course_title" description="MK Scholars certificate.">
    <section class="bg-slate-50 py-10">
        <div class="mk-container">
            <div class="mb-6 flex flex-col gap-4 print:hidden sm:flex-row sm:items-center sm:justify-between">
                <x-button :href="route('student.certificates')" variant="secondary">Back to Certificates</x-button>
                <div class="flex flex-col gap-3 sm:flex-row">
                    @if ($certificate->status === \App\Models\Certificate::STATUS_ISSUED)
                        <x-button :href="route('student.certificates.download', $certificate)" variant="secondary">Download PDF</x-button>
                    @else
                        <x-badge tone="gray">PDF unavailable</x-badge>
                    @endif
                    <button onclick="window.print()" class="inline-flex items-center justify-center rounded-md bg-mk-gold px-5 py-3 text-sm font-semibold text-mk-navy shadow-sm transition hover:bg-yellow-300">Print</button>
                </div>
            </div>

            @php
                $signatureUrl = $certificate->signatureImageUrl();
            @endphp

            <div class="mx-auto max-w-5xl rounded-[2rem] border border-mk-gold/60 bg-white p-4 shadow-soft print:border print:shadow-none sm:p-6">
                <div class="rounded-[1.5rem] border-4 border-double border-mk-gold bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.16),transparent_28%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-8 sm:p-12">
                <div class="text-center">
                    <x-brand-logo size="lg" class="justify-center" />
                    <p class="mt-5 text-sm font-black uppercase tracking-[0.35em] text-mk-gold">MK Scholars Academy</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-normal text-mk-navy sm:text-5xl">Certificate of Completion</h1>
                    <p class="mx-auto mt-5 h-1 w-32 rounded-full bg-mk-gold"></p>
                    <p class="mt-6 text-sm font-semibold leading-6 text-slate-600">This certificate is proudly presented to</p>
                    <p class="mt-3 break-words text-4xl font-black text-mk-navy">{{ $certificate->student_name }}</p>
                    <p class="mt-6 text-sm leading-6 text-slate-600">for successfully completing</p>
                    <p class="mx-auto mt-3 max-w-3xl break-words text-2xl font-black text-mk-navy">{{ $certificate->course_title }}</p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Certificate No.</p>
                        <p class="mt-2 font-bold text-mk-navy">{{ $certificate->certificate_number }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Issued</p>
                        <p class="mt-2 font-bold text-mk-navy">{{ $certificate->issued_at->format('M j, Y') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Score</p>
                        <p class="mt-2 font-bold text-mk-navy">{{ $certificate->score !== null ? $certificate->score.'%' : 'N/A' }}</p>
                    </div>
                </div>

                <div class="mt-10">
                    <h2 class="text-center text-lg font-extrabold text-mk-navy">Skills Acquired</h2>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @forelse ($certificate->skills as $skill)
                            <x-badge tone="gold">{{ $skill->skill_name }}</x-badge>
                        @empty
                            <x-badge tone="gray">Skills pending</x-badge>
                        @endforelse
                    </div>
                </div>

                <div class="mt-10 flex justify-center">
                    <div class="w-full max-w-xs text-center">
                        @if ($signatureUrl)
                            <img src="{{ $signatureUrl }}" alt="Certificate signature" class="mx-auto max-h-20 max-w-56 object-contain">
                        @endif
                        <div class="mt-3 border-t-2 border-mk-navy pt-3">
                            <p class="text-sm font-bold text-mk-navy">{{ $certificate->signer_name ?: 'Authorized Signature' }}</p>
                            @if ($certificate->signer_title)
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $certificate->signer_title }}</p>
                            @elseif ($certificate->signer_name)
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Authorized Signatory</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-10 rounded-lg bg-mk-goldSoft p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">Verification Link</p>
                    <p class="mt-2 break-all text-sm font-bold text-mk-navy">{{ route('certificates.verify', $certificate->verification_code) }}</p>
                </div>
                </div>
            </div>
        </div>
    </section>
</x-dashboard-layout>
