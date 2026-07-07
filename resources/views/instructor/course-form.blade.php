<x-dashboard-layout role="instructor" :title="$mode === 'create' ? 'Create Course' : 'Course Studio'" description="MK Scholars instructor course builder.">
    @php
        $coverImageUrl = $course->exists && $course->featured_image_path ? $course->coverImageUrl() : null;
        $outcomesValue = old('learning_outcomes', collect($course->learning_outcomes ?? [])->implode("\n"));
    @endphp

    <div class="space-y-6">
        <div class="overflow-hidden rounded-mk-lg border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="p-6 sm:p-8">
                    <x-badge tone="gold">Instructor course studio</x-badge>
                    <h1 class="mt-4 text-3xl font-black tracking-normal text-mk-navy sm:text-4xl">
                        {{ $mode === 'create' ? 'Create a professional course' : 'Polish your course and builder' }}
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                        Build a clear course profile first, then continue into modules, lessons, quizzes, and assignments without leaving your instructor workspace.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <x-button :href="route('instructor.courses.index')" variant="secondary">Back to My Courses</x-button>
                        @if ($course->exists)
                            <x-button :href="route('instructor.courses.show', $course)" variant="navy">Open Preview</x-button>
                        @endif
                    </div>
                </div>
                <div class="relative min-h-64 overflow-hidden bg-mk-navy">
                    @if ($coverImageUrl)
                        <img src="{{ $coverImageUrl }}" alt="{{ $course->title }} cover image" class="h-full min-h-64 w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-mk-navy/80 via-transparent to-transparent"></div>
                    @else
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_34%),linear-gradient(135deg,#073653_0%,#0e4a72_56%,#102a3a_100%)]"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="rounded-mk-lg border border-mk-gold/40 bg-white/10 px-5 py-4 text-center text-white backdrop-blur">
                                <p class="text-sm font-black uppercase tracking-wide text-mk-gold">Cover image</p>
                                <p class="mt-2 text-sm font-semibold">Upload JPG, PNG, JPEG, or WebP</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <x-section-header
                eyebrow="Guided setup"
                :title="$mode === 'create' ? 'Course profile' : 'Course profile and builder'"
                description="Use the sections below to shape the public course profile, pricing, image, outcomes, and learning content."
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
            <form method="POST" action="{{ $course->exists ? route('instructor.courses.update', $course) : route('instructor.courses.store') }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @if ($course->exists)
                    @method('PUT')
                @endif

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Section A</p>
                        <h2 class="mt-1 text-xl font-black text-mk-navy">Course identity</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Give students the core facts they need to understand the course quickly.</p>
                    </div>
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
                        <span class="mt-1 block text-xs font-semibold text-slate-500">Use a clear student-facing title.</span>
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
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-[0.85fr_1.15fr]">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Section B</p>
                        <h2 class="mt-1 text-xl font-black text-mk-navy">Course cover image</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">This image appears on public course cards and detail pages. Leave blank to keep the current image or fallback.</p>
                    </div>
                    <div class="rounded-mk-md border border-slate-200 bg-slate-50 p-4">
                        <div class="overflow-hidden rounded-mk-md border border-slate-200 bg-mk-navy">
                            @if ($coverImageUrl)
                                <img src="{{ $coverImageUrl }}" alt="{{ $course->title }} current cover image" class="h-52 w-full object-cover">
                            @else
                                <div class="flex h-52 w-full items-center justify-center bg-[radial-gradient(circle_at_top_left,rgba(255,196,12,0.30),transparent_34%),linear-gradient(135deg,#073653_0%,#0e4a72_56%,#102a3a_100%)]">
                                    <span class="rounded-mk-md border border-mk-gold/40 bg-white/10 px-4 py-3 text-sm font-black text-mk-gold">No image yet</span>
                                </div>
                            @endif
                        </div>
                        <label class="mt-4 block text-sm font-bold text-mk-navy">
                            Upload or replace image
                            <input name="featured_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-mk-gold file:px-4 file:py-2 file:text-sm file:font-bold file:text-mk-navy focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            <span class="mt-1 block text-xs font-semibold text-slate-500">JPG, JPEG, PNG, or WebP. Maximum 4MB. Stored using the same public course image field as admin.</span>
                        </label>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Section C</p>
                        <h2 class="mt-1 text-xl font-black text-mk-navy">Course overview</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Markdown-style headings, paragraphs, lists, links, and tables are rendered safely on public pages.</p>
                    </div>
                    <label class="block text-sm font-bold text-mk-navy">
                        Short description
                        <textarea name="short_description" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>{{ old('short_description', $course->short_description) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Full course overview
                        <textarea name="full_description" rows="8" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm leading-6 focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="# What students will learn&#10;&#10;Describe the course, projects, lessons, and support students receive.">{{ old('full_description', $course->full_description) }}</textarea>
                    </label>
                </section>

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Section D</p>
                        <h2 class="mt-1 text-xl font-black text-mk-navy">Learning outcomes</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Add one outcome per line. These become public course outcomes.</p>
                    </div>
                    <label class="block text-sm font-bold text-mk-navy">
                        Outcomes
                        <textarea name="learning_outcomes" rows="5" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="One outcome per line">{{ $outcomesValue }}</textarea>
                    </label>
                </section>

                <section class="space-y-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Sections E and F</p>
                        <h2 class="mt-1 text-xl font-black text-mk-navy">Pricing, access, and status</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Manual payment behavior remains unchanged. Admins can still supervise courses in Filament.</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
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
                </section>

                <div class="flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-semibold text-slate-500">{{ $course->exists ? 'Save changes, then keep building modules and lessons below.' : 'Create the course draft, then continue to the builder.' }}</p>
                    <div class="flex flex-wrap gap-3">
                        <x-button :href="route('instructor.courses.index')" variant="secondary">Back to My Courses</x-button>
                        <x-button type="submit">{{ $course->exists ? 'Save Course' : 'Save & Continue to Builder' }}</x-button>
                    </div>
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
