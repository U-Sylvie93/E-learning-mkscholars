<x-dashboard-layout role="student" title="My Certificates" description="MK Scholars certificates.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Student"
                title="My Certificates"
                description="View, print, and verify your MK Scholars credentials."
            />
            <x-badge tone="gray">{{ $certificates->count() }} certificates</x-badge>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container">
            @if ($certificates->isEmpty())
                <x-card>
                    <h2 class="text-xl font-bold text-mk-navy">No certificates yet</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Your issued certificates will appear here when an MK Scholars admin awards them.</p>
                    <x-button :href="route('student.my-courses')" class="mt-6">Back to My Courses</x-button>
                </x-card>
            @else
                <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($certificates as $certificate)
                        @php
                            $certificateScore = $certificate->displayScore();
                        @endphp
                        <x-card class="flex h-full flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :tone="$certificate->status === 'issued' ? 'green' : 'gray'">{{ $certificate->status }}</x-badge>
                                <x-badge tone="blue">{{ $certificate->certificate_number }}</x-badge>
                            </div>
                            <h2 class="mt-5 text-xl font-bold text-mk-navy">{{ $certificate->course_title }}</h2>
                            <p class="mt-2 text-sm font-semibold text-mk-gold">{{ $certificate->student_name }}</p>
                            <p class="mt-4 text-sm leading-6 text-slate-600">Issued {{ $certificate->issued_at->format('M j, Y') }}</p>
                            @if ($certificateScore !== null)
                                <p class="mt-2 text-sm font-bold text-mk-navy">Final Test Score: {{ $certificateScore }}%</p>
                            @endif
                            <div class="mt-6 grid gap-3">
                                <x-button :href="route('student.certificates.show', $certificate)" class="w-full">Open Certificate</x-button>
                                @if ($certificate->status === \App\Models\Certificate::STATUS_ISSUED)
                                    <x-button :href="route('student.certificates.download', $certificate)" variant="secondary" class="w-full">Download PDF</x-button>
                                @else
                                    <p class="text-sm font-semibold text-slate-500">PDF download is available only for issued certificates.</p>
                                @endif
                            </div>
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-dashboard-layout>
