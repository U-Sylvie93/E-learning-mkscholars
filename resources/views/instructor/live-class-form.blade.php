<x-dashboard-layout role="instructor" :title="$mode === 'create' ? 'Add Live Class' : 'Edit Live Class'" description="MK Scholars instructor live class form.">
    @php
        $platformOptions = [
            \App\Models\LiveClass::PLATFORM_ZOOM => 'Zoom',
            \App\Models\LiveClass::PLATFORM_GOOGLE_MEET => 'Google Meet',
            \App\Models\LiveClass::PLATFORM_TEAMS => 'Microsoft Teams',
            \App\Models\LiveClass::PLATFORM_OTHER => 'Other',
        ];
        $statusOptions = [
            \App\Models\LiveClass::STATUS_SCHEDULED => 'Scheduled',
            \App\Models\LiveClass::STATUS_LIVE => 'Live',
            \App\Models\LiveClass::STATUS_COMPLETED => 'Completed',
            \App\Models\LiveClass::STATUS_CANCELLED => 'Cancelled',
        ];
        $startsAtValue = old('starts_at', $liveClass->starts_at?->format('Y-m-d\TH:i'));
        $endsAtValue = old('ends_at', $liveClass->ends_at?->format('Y-m-d\TH:i'));
    @endphp

    <section class="bg-white py-16">
        <div class="mk-container">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <x-section-header
                    eyebrow="Instructor"
                    :title="$mode === 'create' ? 'Add Live Class' : 'Edit Live Class'"
                    description="Schedule a live teaching session using the same live class structure admins manage in Filament."
                />
                <x-button :href="route('instructor.live-classes.index')" variant="secondary">Class Schedule</x-button>
            </div>

            <div class="mt-8">
                @include('instructor.partials.nav')
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="mk-container max-w-5xl">
            @if (session('status'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-bold">Please fix the highlighted fields.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-card highlighted>
                <form method="POST" action="{{ $mode === 'create' ? route('instructor.live-classes.store') : route('instructor.live-classes.update', $liveClass) }}" class="space-y-6">
                    @csrf
                    @if ($mode === 'edit')
                        @method('PUT')
                    @endif

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block text-sm font-bold text-mk-navy">
                            Course
                            <select name="course_id" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                                @foreach ($courses as $course)
                                    <option value="{{ $course->id }}" @selected((int) old('course_id', $liveClass->course_id) === (int) $course->id)>{{ $course->title }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy">
                            Title
                            <input name="title" value="{{ old('title', $liveClass->title) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy">
                            Platform
                            <select name="platform" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                                @foreach ($platformOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('platform', $liveClass->platform ?? \App\Models\LiveClass::PLATFORM_ZOOM) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy">
                            Status
                            <select name="status" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $liveClass->status ?? \App\Models\LiveClass::STATUS_SCHEDULED) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy">
                            Start Time
                            <input name="starts_at" type="datetime-local" value="{{ $startsAtValue }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy">
                            End Time
                            <input name="ends_at" type="datetime-local" value="{{ $endsAtValue }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy md:col-span-2">
                            Join URL
                            <input name="meeting_url" type="url" value="{{ old('meeting_url', $liveClass->meeting_url) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="https://..." required>
                        </label>

                        <label class="block text-sm font-bold text-mk-navy md:col-span-2">
                            Recording URL
                            <input name="recording_url" type="url" value="{{ old('recording_url', $liveClass->recording_url) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="https://...">
                        </label>
                    </div>

                    <label class="block text-sm font-bold text-mk-navy">
                        Description
                        <textarea name="description" rows="5" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm leading-6 focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">{{ old('description', $liveClass->description) }}</textarea>
                    </label>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm font-semibold text-slate-500">Admin live class management remains available in Filament.</p>
                        <div class="flex flex-wrap gap-3">
                            <x-button :href="route('instructor.live-classes.index')" variant="secondary">Back to Schedule</x-button>
                            <x-button type="submit">Save Live Class</x-button>
                        </div>
                    </div>
                </form>
            </x-card>
        </div>
    </section>
</x-dashboard-layout>
