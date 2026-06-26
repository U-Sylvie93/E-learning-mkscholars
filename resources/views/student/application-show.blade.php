<x-dashboard-layout role="student" :title="$application->opportunity->title" description="MK Scholars student application.">
    <section class="bg-white py-16">
        <div class="mk-container grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
            <div>
                <x-badge tone="blue">{{ str_replace('_', ' ', $application->status) }}</x-badge>
                @if ($application->opportunity->deadline)
                    <x-badge :tone="$application->opportunity->deadlineBadgeTone()">{{ $application->opportunity->deadlineBadge() }}</x-badge>
                @endif
                <h1 class="mt-4 text-3xl font-extrabold tracking-normal text-mk-navy sm:text-4xl">{{ $application->opportunity->title }}</h1>
                <p class="mt-3 text-sm font-bold uppercase tracking-wide text-mk-gold">
                    {{ collect([$application->opportunity->organization, $application->opportunity->city, $application->opportunity->country])->filter()->join(' - ') ?: 'MK Scholars' }}
                </p>
                <p class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $application->opportunity->description }}</p>
            </div>

            <x-card>
                <x-badge tone="gold">Application</x-badge>
                <dl class="mt-6 space-y-4">
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Deadline</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $application->opportunity->deadline?->format('M j, Y') ?? 'Open' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                        <dt class="text-sm text-slate-500">Submitted</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $application->submitted_at?->format('M j, Y') ?? 'Not submitted' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-sm text-slate-500">Reviewed</dt>
                        <dd class="text-sm font-bold text-mk-navy">{{ $application->reviewed_at?->format('M j, Y') ?? 'Pending' }}</dd>
                    </div>
                </dl>
                <x-button :href="route('student.documents')" variant="secondary" class="mt-6 w-full">Open Document Center</x-button>

                @if ($application->status === 'draft')
                    <form method="POST" action="{{ route('student.applications.submit', $application) }}" class="mt-6">
                        @csrf
                        <x-button type="submit" class="w-full">Submit Application</x-button>
                    </form>
                @endif
            </x-card>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-5">
                <x-card>
                    <x-section-header eyebrow="Documents" title="Upload requirements" />
                    @if ($errors->any())
                        <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mt-6 space-y-4">
                        @foreach ($application->documents as $document)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h3 class="font-bold text-mk-navy">{{ $document->document_name }}</h3>
                                            <x-badge :tone="$document->status === 'approved' ? 'green' : 'gray'">{{ $document->status }}</x-badge>
                                        </div>
                                        <p class="mt-2 text-sm text-slate-600">
                                            {{ $document->uploaded_at?->format('M j, Y g:i A') ?? 'Not uploaded yet' }}
                                            @if ($document->studentDocument)
                                                <span class="font-semibold text-mk-navy"> - attached from {{ $document->studentDocument->title }}</span>
                                            @endif
                                        </p>
                                        @if ($document->admin_feedback)
                                            <div class="mt-3 rounded-lg border border-mk-gold/30 bg-mk-goldSoft/40 p-3">
                                                <p class="text-xs font-bold uppercase tracking-wide text-mk-navy">Admin feedback</p>
                                                <p class="mt-1 text-sm leading-6 text-slate-700">{{ $document->admin_feedback }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        @if ($document->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path))
                                            <x-button :href="route('student.applications.documents.download', [$application, $document])" size="sm" variant="secondary">Download</x-button>
                                        @elseif ($document->file_path)
                                            <x-badge tone="gray">File missing</x-badge>
                                        @endif
                                        @if ($document->external_link)
                                            <x-button :href="$document->external_link" size="sm" variant="secondary">Open Link</x-button>
                                        @endif
                                    </div>
                                </div>

                                @if (in_array($application->status, ['draft', 'submitted'], true))
                                    <form method="POST" action="{{ route('student.applications.documents.store', $application) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                                        @csrf
                                        <input type="hidden" name="application_document_id" value="{{ $document->id }}">
                                        <input type="hidden" name="document_name" value="{{ $document->document_name }}">
                                        <select name="student_document_id" class="mk-input">
                                            <option value="">Attach from Document Center</option>
                                            @foreach ($studentDocuments as $studentDocument)
                                                <option value="{{ $studentDocument->id }}">{{ $studentDocument->title }}</option>
                                            @endforeach
                                        </select>
                                        <input name="document_file" type="file" class="mk-input">
                                        <input name="external_link" type="url" value="{{ $document->external_link }}" placeholder="Optional link" class="mk-input">
                                        <p class="text-xs font-semibold leading-5 text-slate-500 md:col-span-3">Allowed: pdf, doc, docx, txt, png, jpg, jpeg. Max 10MB.</p>
                                        <x-button type="submit">Save</x-button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if (in_array($application->status, ['draft', 'submitted'], true))
                        <form method="POST" action="{{ route('student.applications.documents.store', $application) }}" enctype="multipart/form-data" class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-4">
                            @csrf
                            <h3 class="font-bold text-mk-navy">Add another document</h3>
                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <input name="document_name" type="text" required placeholder="Document name" class="mk-input">
                                <select name="student_document_id" class="mk-input">
                                    <option value="">Attach from Document Center</option>
                                    @foreach ($studentDocuments as $studentDocument)
                                        <option value="{{ $studentDocument->id }}">{{ $studentDocument->title }}</option>
                                    @endforeach
                                </select>
                                <input name="external_link" type="url" placeholder="Optional link" class="mk-input">
                                <input name="document_file" type="file" class="mk-input md:col-span-2">
                            </div>
                            <p class="mt-3 text-xs font-semibold leading-5 text-slate-500">Allowed: pdf, doc, docx, txt, png, jpg, jpeg. Max 10MB.</p>
                            <x-button type="submit" class="mt-4">Upload Document</x-button>
                        </form>
                    @endif
                </x-card>
            </div>

            <div class="space-y-5">
                <x-card>
                    <x-section-header eyebrow="Requirements" title="Checklist" />
                    @if ($missingRequirements->isNotEmpty())
                        <div class="mt-5 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <p class="text-sm font-bold text-mk-navy">Missing required items</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $missingRequirements->pluck('name')->join(', ') }}</p>
                        </div>
                    @endif
                    <div class="mt-6 space-y-4">
                        @forelse ($application->opportunity->requirements as $requirement)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-center gap-3">
                                    <h3 class="font-bold text-mk-navy">{{ $requirement->name }}</h3>
                                    <x-badge :tone="$requirement->is_required ? 'gold' : 'gray'">{{ $requirement->is_required ? 'Required' : 'Optional' }}</x-badge>
                                </div>
                                @if ($requirement->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $requirement->description }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">No specific checklist has been added yet.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card>
                    <x-section-header eyebrow="History" title="Status updates" />
                    <div class="mt-6 space-y-4">
                        @forelse ($application->statusHistories->sortByDesc('created_at') as $history)
                            <div class="border-l-2 border-mk-gold pl-4">
                                <p class="text-sm font-bold capitalize text-mk-navy">{{ str_replace('_', ' ', $history->new_status) }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $history->created_at->format('M j, Y g:i A') }}
                                    @if ($history->changedBy)
                                        by {{ $history->changedBy->name }}
                                    @endif
                                </p>
                                @if ($history->note)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $history->note }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm leading-6 text-slate-600">No status history yet.</p>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>
    </section>
</x-dashboard-layout>

