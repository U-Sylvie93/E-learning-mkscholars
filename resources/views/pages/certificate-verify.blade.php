<x-layouts.app title="Verify Certificate" description="Verify an MK Scholars certificate.">
    <section class="bg-white py-16">
        <div class="mk-container">
            @if ($isValid)
                @php
                    $signatureUrl = $certificate->signatureImageUrl();
                    $certificateScore = $certificate->displayScore();
                @endphp
                <div class="mx-auto max-w-4xl">
                    <div class="rounded-[2rem] border border-mk-gold/60 bg-white p-4 shadow-soft">
                    <div class="rounded-[1.5rem] border-4 border-double border-mk-gold bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.16),transparent_30%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] p-6 sm:p-8">
                        <div class="text-center">
                            <x-badge tone="green">Valid certificate</x-badge>
                            <h1 class="mt-5 text-4xl font-extrabold tracking-normal text-mk-navy">Certificate Verified</h1>
                            <p class="mt-4 text-sm leading-6 text-slate-600">This credential was issued by MK Scholars and is currently valid.</p>
                        </div>

                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Student</p>
                                <p class="mt-2 font-bold text-mk-navy">{{ $certificate->student_name }}</p>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Course</p>
                                <p class="mt-2 font-bold text-mk-navy">{{ $certificate->course_title }}</p>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Issued</p>
                                <p class="mt-2 font-bold text-mk-navy">{{ $certificate->issued_at->format('M j, Y') }}</p>
                            </div>
                            <div class="rounded-lg bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Status</p>
                                <p class="mt-2 font-bold text-mk-navy">{{ $certificate->status }}</p>
                            </div>
                            @if ($certificateScore !== null)
                                <div class="rounded-lg bg-white p-4">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Final Test Score</p>
                                    <p class="mt-2 font-bold text-mk-navy">{{ $certificateScore }}%</p>
                                </div>
                            @endif
                        </div>

                        @if ($certificate->signer_name || $signatureUrl)
                            <div class="mt-8 rounded-lg bg-slate-50 p-5 text-center">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Authorized by</p>
                                @if ($signatureUrl)
                                    <img src="{{ $signatureUrl }}" alt="Certificate signature" class="mx-auto mt-3 max-h-16 max-w-48 object-contain">
                                @endif
                                @if ($certificate->signer_name)
                                    <p class="mt-3 font-bold text-mk-navy">{{ $certificate->signer_name }}</p>
                                @endif
                                @if ($certificate->signer_title)
                                    <p class="mt-1 text-sm font-semibold text-slate-600">{{ $certificate->signer_title }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="mt-8">
                            <h2 class="text-lg font-extrabold text-mk-navy">Skills</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($certificate->skills as $skill)
                                    <x-badge tone="gold">{{ $skill->skill_name }}</x-badge>
                                @empty
                                    <x-badge tone="gray">No skills listed</x-badge>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            @else
                <div class="mx-auto max-w-2xl text-center">
                    <x-card>
                        <x-badge tone="gray">Invalid certificate</x-badge>
                        <h1 class="mt-5 text-3xl font-extrabold tracking-normal text-mk-navy">Certificate Not Valid</h1>
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            This certificate could not be found or has been revoked. Please check the verification code and try again.
                        </p>
                    </x-card>
                </div>
            @endif
        </div>
    </section>
</x-layouts.app>
