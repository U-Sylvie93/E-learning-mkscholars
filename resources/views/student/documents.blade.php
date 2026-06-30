<x-dashboard-layout role="student" title="Document Center" description="MK Scholars student document center.">
    <section class="bg-white py-16">
        <div class="mk-container flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
            <x-section-header
                eyebrow="Documents"
                title="Reusable application files"
                description="Keep reusable study documents ready for your courses, profile, and MK Scholars support."
            />
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <x-card>
                <x-section-header eyebrow="Upload" title="Add document" />
                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                <form method="POST" action="{{ route('student.documents.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <select name="document_type" required class="mk-input">
                        @foreach ($documentTypes as $type)
                            <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                    <input name="title" type="text" required placeholder="Document title" class="mk-input">
                    <input name="document_file" type="file" required class="mk-input">
                    <p class="text-xs font-semibold leading-5 text-slate-500">Allowed: pdf, doc, docx, txt, png, jpg, jpeg. Max 10MB.</p>
                    <x-button type="submit" class="w-full">Upload Document</x-button>
                </form>
            </x-card>

            <div class="grid gap-5">
                @forelse ($documents as $document)
                    <x-card>
                        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <x-badge tone="blue">{{ str_replace('_', ' ', $document->document_type) }}</x-badge>
                                    <x-badge tone="gray">{{ $document->created_at->format('M j, Y') }}</x-badge>
                                </div>
                                <h2 class="mt-4 text-xl font-bold text-mk-navy">{{ $document->title }}</h2>
                                <p class="mt-2 break-all text-sm text-slate-600">{{ basename($document->file_path) }}</p>
                            </div>
                            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap">
                                @if (\Illuminate\Support\Facades\Storage::disk('public')->exists($document->file_path))
                                    <x-button :href="route('student.documents.download', $document)" size="sm" variant="secondary" class="w-full sm:w-auto">Download</x-button>
                                @else
                                    <x-badge tone="gray">File missing</x-badge>
                                @endif
                                <form method="POST" action="{{ route('student.documents.destroy', $document) }}" class="w-full sm:w-auto">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" size="sm" variant="ghost" class="w-full sm:w-auto">Delete</x-button>
                                </form>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card>
                        <x-badge tone="gray">Empty</x-badge>
                        <h2 class="mt-5 text-xl font-bold text-mk-navy">No reusable documents yet</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Upload your CV, passport, transcripts, and letters once, then attach them to applications.</p>
                    </x-card>
                @endforelse
            </div>
        </div>
    </section>
</x-dashboard-layout>

