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
                    @if ($certificate->status === \App\Models\Certificate::STATUS_ISSUED)
                        <button onclick="window.print()" class="inline-flex items-center justify-center rounded-md bg-mk-gold px-5 py-3 text-sm font-semibold text-mk-navy shadow-sm transition hover:bg-yellow-300">Print</button>
                    @endif
                </div>
            </div>

            @php
                $signatureUrl = $certificate->signatureImageUrl();
                $certificateScore = $certificate->displayScore();
                $certificateSettings = \App\Models\CertificateSetting::current();
                $assetService = app(\App\Services\CertificateAssetService::class);
                $qrService = app(\App\Services\CertificateQrCodeService::class);
                $instructor = $certificate->instructor();
            @endphp

            <div class="mx-auto max-w-5xl rounded-[2rem] border border-mk-gold/60 bg-white p-4 shadow-soft print:border print:shadow-none sm:p-6">
                <div class="rounded-[1.5rem] border-4 border-double border-mk-gold bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.16),transparent_28%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-8 sm:p-12">
                <div class="text-center">
                    @if ($certificate->status !== \App\Models\Certificate::STATUS_ISSUED)
                        <x-badge :tone="$certificate->status === \App\Models\Certificate::STATUS_PENDING ? 'gold' : 'gray'">
                            {{ $certificate->status === \App\Models\Certificate::STATUS_PENDING ? 'Pending Approval' : str($certificate->status)->headline() }}
                        </x-badge>
                    @endif
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
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $certificate->status === \App\Models\Certificate::STATUS_ISSUED ? 'Issued' : 'Prepared' }}</p>
                        <p class="mt-2 font-bold text-mk-navy">{{ $certificate->issued_at->format('M j, Y') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Final Test Score</p>
                        <p class="mt-2 font-bold text-mk-navy">{{ $certificateScore !== null ? $certificateScore.'%' : 'Not available' }}</p>
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

                @if ($certificate->status === \App\Models\Certificate::STATUS_ISSUED)
                <div class="mt-10 grid items-end gap-8 sm:grid-cols-3">
                    <div class="text-center">
                        @if ($instructorSignature = $assetService->url($instructor?->signature_path))
                            <img src="{{ $instructorSignature }}" alt="Instructor signature" class="mx-auto max-h-20 max-w-48 object-contain">
                        @endif
                        <div class="mt-3 border-t-2 border-mk-navy pt-3"><p class="text-sm font-bold text-mk-navy">{{ $instructor?->name ?? 'MK Scholars Faculty' }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Course Instructor</p></div>
                    </div>
                    <div class="text-center">
                        @if ($stampUrl = $assetService->url($certificateSettings->stamp_path))
                            <img src="{{ $stampUrl }}" alt="Official organization stamp" class="mx-auto max-h-28 max-w-48 object-contain">
                        @endif
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Official Stamp</p>
                    </div>
                    <div class="text-center">
                        @if ($issuerSignature = $assetService->url($certificateSettings->admin_signature_path) ?? $signatureUrl)
                            <img src="{{ $issuerSignature }}" alt="Issuer signature" class="mx-auto max-h-20 max-w-48 object-contain">
                        @endif
                        <div class="mt-3 border-t-2 border-mk-navy pt-3"><p class="text-sm font-bold text-mk-navy">{{ $certificateSettings->issuer_name ?: ($certificate->signer_name ?: 'Authorized Signature') }}</p><p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $certificateSettings->issuer_title ?: ($certificate->signer_title ?: 'Authorized Signatory') }}</p></div>
                    </div>
                </div>

                <div class="mt-10 rounded-lg bg-mk-goldSoft p-5 text-center">
                    <img src="{{ $qrService->dataUri($certificate) }}" alt="Certificate verification QR code" class="mx-auto h-32 w-32">
                    <p class="mt-3 text-xs font-bold uppercase tracking-wide text-mk-gold">Scan to verify this certificate</p>
                    <p class="mt-2 break-all text-sm font-bold text-mk-navy">{{ $qrService->verificationUrl($certificate) }}</p>
                </div>
                @else
                    <div class="mt-10 rounded-lg border border-amber-200 bg-amber-50 p-5 text-center">
                        <p class="font-bold text-mk-navy">This certificate is not an official issued credential.</p>
                        <p class="mt-2 text-sm text-slate-600">
                            @if ($certificate->status === \App\Models\Certificate::STATUS_PENDING)
                                It is awaiting admin approval. Official signatures, stamp, QR verification, printing, and PDF download will appear after issue.
                            @elseif ($certificate->status === \App\Models\Certificate::STATUS_REJECTED)
                                The certificate request was not approved.
                            @else
                                This certificate has been revoked and is no longer valid.
                            @endif
                        </p>
                    </div>
                @endif
                </div>
            </div>
        </div>
    </section>
</x-dashboard-layout>
