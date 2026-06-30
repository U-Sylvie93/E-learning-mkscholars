<x-dashboard-layout role="instructor" :title="$mode === 'create' ? 'Create Course' : 'Course Builder'" description="MK Scholars instructor course builder.">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-section-header
                eyebrow="Instructor studio"
                :title="$mode === 'create' ? 'Create a course draft' : 'Build course content'"
                description="Create and polish your own MK Scholars courses. Admins can still supervise, publish, and review everything from Filament."
            />
            <div class="flex flex-wrap gap-3">
                <x-button :href="route('instructor.courses.index')" variant="secondary">My Courses</x-button>
                @if ($course->exists)
                    <x-button :href="route('instructor.courses.show', $course)" variant="navy">Preview</x-button>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold">Please fix the highlighted fields.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card highlighted>
            <form method="POST" action="{{ $course->exists ? route('instructor.courses.update', $course) : route('instructor.courses.store') }}" class="space-y-5">
                @csrf
                @if ($course->exists)
                    @method('PUT')
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-bold text-mk-navy">
                        Academy
                        <select name="academy_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            @foreach ($academies as $academy)
                                <option value="{{ $academy->id }}" @selected((int) old('academy_id', $course->academy_id) === (int) $academy->id)>{{ $academy->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Title
                        <input name="title" value="{{ old('title', $course->title) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Slug
                        <input name="slug" value="{{ old('slug', $course->slug) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="auto-created if blank on create">
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Level
                        <input name="level" value="{{ old('level', $course->level ?? 'Beginner') }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Duration
                        <input name="duration" value="{{ old('duration', $course->duration ?? '4 weeks') }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Status
                        <select name="status" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            @foreach (\App\Models\Course::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $course->status ?? \App\Models\Course::STATUS_DRAFT) === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Access type
                        <select name="access_type" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            <option value="free" @selected(old('access_type', $course->access_type ?? 'free') === 'free')>Free</option>
                            <option value="paid" @selected(old('access_type', $course->access_type ?? 'free') === 'paid')>Paid</option>
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-bold text-mk-navy">
                            Price
                            <input name="price_amount" type="number" min="0" step="0.01" value="{{ old('price_amount', $course->price_amount) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                        </label>
                        <label class="block text-sm font-bold text-mk-navy">
                            Currency
                            <input name="currency" value="{{ old('currency', $course->currency ?? 'RWF') }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                        </label>
                    </div>
                </div>

                <label class="block text-sm font-bold text-mk-navy">
                    Short description
                    <textarea name="short_description" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>{{ old('short_description', $course->short_description) }}</textarea>
                </label>
                <label class="block text-sm font-bold text-mk-navy">
                    Full description
                    <textarea name="full_description" rows="5" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">{{ old('full_description', $course->full_description) }}</textarea>
                </label>
                <label class="block text-sm font-bold text-mk-navy">
                    Learning outcomes
                    <textarea name="learning_outcomes" rows="4" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="One outcome per line">{{ old('learning_outcomes', collect($course->learning_outcomes ?? [])->implode("\n")) }}</textarea>
                </label>
                <div class="flex justify-end">
                    <x-button type="submit">{{ $course->exists ? 'Save Course' : 'Create Course' }}</x-button>
                </div>
            </form>
        </x-card>

        @if ($course->exists)
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card>
                    <x-badge tone="blue">Modules</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add module</h2>
                    <form method="POST" action="{{ route('instructor.modules.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Module title" required>
                        <input name="slug" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional slug">
                        <textarea name="summary" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Short summary"></textarea>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <input name="sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Sort order">
                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (\App\Models\Course::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-button type="submit" size="sm">Add Module</x-button>
                    </form>
                    <div class="mt-6 space-y-3">
                        @forelse ($modules as $module)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                <p class="font-bold text-mk-navy">{{ $module->title }}</p>
                                <p class="text-xs text-slate-500">{{ $module->lessons->count() }} lessons - {{ $module->status }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-600">No modules yet.</p>
                        @endforelse
                    </div>
                </x-card>

                <x-card>
                    <x-badge tone="gold">Lessons</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add lesson</h2>
                    <form method="POST" action="{{ route('instructor.lessons.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <select name="module_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                            <option value="">Select module</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                        <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Lesson title" required>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <select name="lesson_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (['video', 'text', 'quiz', 'assignment', 'live'] as $type)
                                    <option value="{{ $type }}">{{ str($type)->headline() }}</option>
                                @endforeach
                            </select>
                            <input name="duration_minutes" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Minutes">
                        </div>
                        <input name="video_url" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional YouTube URL">
                        <textarea name="content" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Lesson notes/body"></textarea>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <input name="sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Sort order">
                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (\App\Models\Course::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                @endforeach
                            </select>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-mk-navy">
                                <input type="checkbox" name="is_free_preview" value="1"> Preview
                            </label>
                        </div>
                        <x-button type="submit" size="sm">Add Lesson</x-button>
                    </form>
                </x-card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-card>
                    <x-badge tone="green">Quiz</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add quiz</h2>
                    <form method="POST" action="{{ route('instructor.quizzes.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <select name="lesson_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                            <option value="">Select lesson</option>
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                            @endforeach
                        </select>
                        <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Quiz title" required>
                        <textarea name="description" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Description"></textarea>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <input name="passing_score" type="number" min="0" max="100" value="50" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Pass %">
                            <input name="max_attempts" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Attempts">
                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (\App\Models\Quiz::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-mk-navy">Optional first question</p>
                            <textarea name="question_text" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question text"></textarea>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <input name="option_a" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Option A">
                                <input name="option_b" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Option B">
                            </div>
                            <select name="correct_option" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="a">Option A is correct</option>
                                <option value="b">Option B is correct</option>
                            </select>
                        </div>
                        <x-button type="submit" size="sm">Add Quiz</x-button>
                    </form>
                </x-card>

                <x-card>
                    <x-badge tone="blue">Assignment</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add assignment</h2>
                    <form method="POST" action="{{ route('instructor.assignments.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <select name="lesson_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                            <option value="">Select lesson</option>
                            @foreach ($lessons as $lesson)
                                <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                            @endforeach
                        </select>
                        <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Assignment title" required>
                        <textarea name="instructions" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Instructions" required></textarea>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <select name="submission_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (['text', 'file', 'link', 'mixed'] as $type)
                                    <option value="{{ $type }}">{{ str($type)->headline() }}</option>
                                @endforeach
                            </select>
                            <input name="max_score" type="number" min="1" value="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Max score">
                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach ([\App\Models\Assignment::STATUS_DRAFT, \App\Models\Assignment::STATUS_PUBLISHED, \App\Models\Assignment::STATUS_ARCHIVED] as $status)
                                    <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="flex items-center gap-2 text-sm font-semibold text-mk-navy">
                            <input type="checkbox" name="allow_late_submission" value="1" checked> Allow late submission
                        </label>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-mk-navy">Optional first question</p>
                            <textarea name="question_text" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question prompt"></textarea>
                            <select name="question_type" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="textarea">Long answer</option>
                                <option value="text">Short answer</option>
                            </select>
                        </div>
                        <x-button type="submit" size="sm">Add Assignment</x-button>
                    </form>
                </x-card>
            </div>
        @endif
    </div>
</x-dashboard-layout>
