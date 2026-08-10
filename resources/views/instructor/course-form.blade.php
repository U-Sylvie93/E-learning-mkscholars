<x-dashboard-layout role="instructor" :title="$mode === 'create' ? 'Create Course' : 'Course Studio'" description="MK Scholars instructor course builder.">
    @php
        $coverImageUrl = $course->exists && $course->featured_image_path ? $course->coverImageUrl() : null;
        $outcomesValue = old('learning_outcomes', collect($course->learning_outcomes ?? [])->implode("\n"));
        $videoLessonsCount = $lessons->where('lesson_type', 'video')->count();
        $readingLessonsCount = $lessons->where('lesson_type', 'text')->count();
        $finalTestStatus = $finalTest ? str($finalTest->status)->headline() : 'Not present';

        $existingTiers = old('price_tiers');
        if (! is_array($existingTiers)) {
            $existingTiers = method_exists($course, 'tierOptions') ? $course->tierOptions() : [];
            if (empty($existingTiers)) {
                $existingTiers = [['name' => '', 'amount' => '']];
            }
        }
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

        @if ($course->exists)
            <x-card highlighted>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <x-badge tone="blue">Completion requirements</x-badge>
                        <h2 class="mt-3 text-xl font-extrabold text-mk-navy">Course completion summary</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Students must complete published lessons and required assessments before certificate eligibility is calculated.</p>
                    </div>
                    <div class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-3xl lg:grid-cols-3">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Videos</p>
                            <p class="mt-2 text-2xl font-black text-mk-navy">{{ $videoLessonsCount }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Reading</p>
                            <p class="mt-2 text-2xl font-black text-mk-navy">{{ $readingLessonsCount }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Quizzes</p>
                            <p class="mt-2 text-2xl font-black text-mk-navy">{{ $quizzes->count() }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Assignments</p>
                            <p class="mt-2 text-2xl font-black text-mk-navy">{{ $assignments->count() }}</p>
                        </div>
                        <div class="rounded-lg border border-mk-gold/30 bg-mk-goldSoft p-4 sm:col-span-2">
                            <p class="text-xs font-bold uppercase tracking-wide text-mk-navy">Final Test</p>
                            <p class="mt-2 text-lg font-black text-mk-navy">{{ $finalTest ? $finalTest->title : 'Not present' }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-600">{{ $finalTestStatus }}</p>
                        </div>
                    </div>
                </div>
            </x-card>
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
                        Academy <span class="text-red-600" aria-hidden="true">*</span>
                        <select name="academy_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            @foreach ($academies as $academy)
                                <option value="{{ $academy->id }}" @selected((int) old('academy_id', $course->academy_id) === (int) $academy->id)>{{ $academy->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Title <span class="text-red-600" aria-hidden="true">*</span>
                        <input id="course-title-input" name="title" value="{{ old('title', $course->title) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                        <span class="mt-1 block text-xs font-semibold text-slate-500">Use a clear student-facing title.</span>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Slug
                        <input id="course-slug-input" name="slug" value="{{ old('slug', $course->slug) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="auto-created if blank">
                        <span class="mt-1 block text-xs font-semibold text-slate-500">Leave blank to generate a unique lowercase hyphenated slug from the title.</span>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Level
                        <input name="level" value="{{ old('level', $course->level) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Optional">
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Duration
                        <input name="duration" value="{{ old('duration', $course->duration) }}" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Optional">
                    </label>
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-black text-mk-navy">Schedule</p>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Optional. Shown to students as a dedicated schedule card on the public course page.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-3">
                            <label class="block text-sm font-bold text-mk-navy">
                                Start date
                                <input type="date" name="start_date" value="{{ old('start_date', optional($course->start_date)->format('Y-m-d')) }}" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            </label>
                            <label class="block text-sm font-bold text-mk-navy">
                                Registration deadline
                                <input type="date" name="registration_deadline" value="{{ old('registration_deadline', optional($course->registration_deadline)->format('Y-m-d')) }}" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            </label>
                            <label class="block text-sm font-bold text-mk-navy">
                                Available seats
                                <input type="number" min="0" name="available_seats" value="{{ old('available_seats', $course->available_seats) }}" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" placeholder="Leave blank for unlimited">
                            </label>
                        </div>
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
                        Short description <span class="text-red-600" aria-hidden="true">*</span>
                        <textarea name="short_description" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>{{ old('short_description', $course->short_description) }}</textarea>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy" for="course-overview-editor">
                        Full course overview
                    </label>
                    <div class="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm focus-within:border-mk-gold focus-within:ring-2 focus-within:ring-mk-gold/30" data-wysiwyg-shell>
                        <div class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50 p-2" data-wysiwyg-toolbar>
                            @php($toolClass = 'inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-mk-gold hover:bg-mk-goldSoft hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/40')
                            <button type="button" class="{{ $toolClass }}" title="Bold" aria-label="Bold" data-command="bold"><x-editor-icon name="bold" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Italic" aria-label="Italic" data-command="italic"><x-editor-icon name="italic" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Underline" aria-label="Underline" data-command="underline"><x-editor-icon name="underline" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Strikethrough" aria-label="Strikethrough" data-command="strikeThrough"><x-editor-icon name="strike" /></button>
                            <span class="mx-1 h-6 w-px bg-slate-300"></span>
                            <button type="button" class="{{ $toolClass }}" title="Heading 1" aria-label="Heading 1" data-command="formatBlock" data-arg="H1"><x-editor-icon name="h1" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Heading 2" aria-label="Heading 2" data-command="formatBlock" data-arg="H2"><x-editor-icon name="h2" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Heading 3" aria-label="Heading 3" data-command="formatBlock" data-arg="H3"><x-editor-icon name="h3" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Paragraph" aria-label="Paragraph" data-command="formatBlock" data-arg="P"><x-editor-icon name="paragraph" /></button>
                            <span class="mx-1 h-6 w-px bg-slate-300"></span>
                            <button type="button" class="{{ $toolClass }}" title="Bulleted list" aria-label="Bulleted list" data-command="insertUnorderedList"><x-editor-icon name="list" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Numbered list" aria-label="Numbered list" data-command="insertOrderedList"><x-editor-icon name="numbered-list" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Quote" aria-label="Quote" data-command="formatBlock" data-arg="BLOCKQUOTE"><x-editor-icon name="quote" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Code block" aria-label="Code block" data-command="formatBlock" data-arg="PRE"><x-editor-icon name="code" /></button>
                            <span class="mx-1 h-6 w-px bg-slate-300"></span>
                            <button type="button" class="{{ $toolClass }}" title="Insert link" aria-label="Insert link" data-command="createLink"><x-editor-icon name="link" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Insert image URL" aria-label="Insert image" data-command="insertImage"><x-editor-icon name="image" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Insert table" aria-label="Insert table" data-action="table"><x-editor-icon name="table" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Clear formatting" aria-label="Clear formatting" data-command="removeFormat"><x-editor-icon name="eraser" /></button>
                            <span class="mx-1 h-6 w-px bg-slate-300"></span>
                            <button type="button" class="{{ $toolClass }}" title="Undo" aria-label="Undo" data-command="undo"><x-editor-icon name="undo" /></button>
                            <button type="button" class="{{ $toolClass }}" title="Redo" aria-label="Redo" data-command="redo"><x-editor-icon name="redo" /></button>
                        </div>
                        <div id="course-overview-editor"
                             class="mk-wysiwyg-editor min-h-[240px] px-4 py-3 text-sm leading-7 text-slate-800 focus:outline-none"
                             contenteditable="true"
                             data-wysiwyg-editor
                             data-target="course-overview-input"
                             data-placeholder="Describe the course, projects, lessons, and support students receive.">{!! old('full_description', $course->full_description) !!}</div>
                        <textarea id="course-overview-input" name="full_description" class="hidden">{{ old('full_description', $course->full_description) }}</textarea>
                    </div>
                    <span class="mt-1 block text-xs font-semibold text-slate-500">Click Bold, lists, or headings to format text. Content is saved as HTML and rendered safely on public pages.</span>

                    <style>
                        .mk-wysiwyg-editor[contenteditable="true"]:empty::before {
                            content: attr(data-placeholder);
                            color: #94a3b8;
                            pointer-events: none;
                        }
                        .mk-wysiwyg-editor h1 { font-size: 1.75rem; font-weight: 800; margin: 0.75rem 0; color: #0b3a5a; }
                        .mk-wysiwyg-editor h2 { font-size: 1.4rem; font-weight: 800; margin: 0.65rem 0; color: #0b3a5a; }
                        .mk-wysiwyg-editor h3 { font-size: 1.15rem; font-weight: 700; margin: 0.55rem 0; color: #0b3a5a; }
                        .mk-wysiwyg-editor p { margin: 0.5rem 0; }
                        .mk-wysiwyg-editor ul { list-style: disc; padding-left: 1.5rem; margin: 0.5rem 0; }
                        .mk-wysiwyg-editor ol { list-style: decimal; padding-left: 1.5rem; margin: 0.5rem 0; }
                        .mk-wysiwyg-editor li { margin: 0.25rem 0; }
                        .mk-wysiwyg-editor blockquote { border-left: 3px solid #FFC40C; padding-left: 0.75rem; color: #475569; margin: 0.5rem 0; }
                        .mk-wysiwyg-editor pre { background: #f1f5f9; padding: 0.75rem; border-radius: 0.5rem; font-family: ui-monospace, monospace; font-size: 0.85rem; overflow-x: auto; }
                        .mk-wysiwyg-editor a { color: #0b3a5a; text-decoration: underline; }
                        .mk-wysiwyg-editor img { max-width: 100%; height: auto; border-radius: 0.5rem; margin: 0.5rem 0; }
                        .mk-wysiwyg-editor table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; }
                        .mk-wysiwyg-editor th, .mk-wysiwyg-editor td { border: 1px solid #e2e8f0; padding: 0.5rem; }
                        .mk-wysiwyg-editor th { background: #f8fafc; font-weight: 700; }
                    </style>
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
                        Status <span class="text-red-600" aria-hidden="true">*</span>
                        <select name="status" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            @foreach (\App\Models\Course::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $course->status ?? \App\Models\Course::STATUS_DRAFT) === $status)>{{ str($status)->headline() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-bold text-mk-navy">
                        Access type <span class="text-red-600" aria-hidden="true">*</span>
                        <select name="access_type" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30" required>
                            <option value="free" @selected(old('access_type', $course->access_type ?? 'free') === 'free')>Free</option>
                            <option value="paid" @selected(old('access_type', $course->access_type ?? 'free') === 'paid')>Paid</option>
                        </select>
                    </label>
                    <label class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white p-4 text-sm font-bold text-mk-navy md:col-span-2">
                        <input name="offers_certificate" type="hidden" value="0">
                        <input name="offers_certificate" type="checkbox" value="1" class="mt-1 rounded border-slate-300 text-mk-gold focus:ring-mk-gold" @checked((bool) old('offers_certificate', $course->offers_certificate))>
                        <span>
                            Course offers certificate
                            <span class="mt-1 block text-xs font-semibold text-slate-500">Enable this only when students should see certificate tags and become eligible for certificate preparation after completion.</span>
                        </span>
                    </label>
                    <div class="md:col-span-2">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-mk-navy">Pricing tiers</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Add any number of tiers with your own names (Standard, Pro, VIP, School Package, ...). Only used when Access type is Paid.</p>
                                </div>
                                <button type="button" data-add-tier-row class="rounded-md border border-mk-gold bg-white px-3 py-1.5 text-xs font-black text-mk-navy hover:bg-mk-goldSoft">+ Add tier</button>
                            </div>
                            <div class="mt-4 space-y-3" data-tier-rows>
                                @foreach ($existingTiers as $i => $tier)
                                    <div class="rounded-lg bg-white p-3" data-tier-row>
                                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                                            <input name="price_tiers[{{ $i }}][name]" value="{{ $tier['name'] ?? '' }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Tier name (e.g. Standard)">
                                            <input name="price_tiers[{{ $i }}][amount]" type="number" min="0" step="0.01" value="{{ $tier['amount'] ?? '' }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Amount">
                                            <button type="button" data-remove-tier-row class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-500 hover:border-red-400 hover:text-red-600">Remove</button>
                                        </div>
                                        <textarea name="price_tiers[{{ $i }}][description]" rows="2" maxlength="400" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="What does this tier include? (shown to students under the price)">{{ $tier['description'] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                            <template data-tier-row-template>
                                <div class="rounded-lg bg-white p-3" data-tier-row>
                                    <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-center">
                                        <input name="price_tiers[__INDEX__][name]" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Tier name">
                                        <input name="price_tiers[__INDEX__][amount]" type="number" min="0" step="0.01" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Amount">
                                        <button type="button" data-remove-tier-row class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-500 hover:border-red-400 hover:text-red-600">Remove</button>
                                    </div>
                                    <textarea name="price_tiers[__INDEX__][description]" rows="2" maxlength="400" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="What does this tier include? (shown to students under the price)"></textarea>
                                </div>
                            </template>
                        </div>
                        <label class="mt-4 block text-sm font-bold text-mk-navy sm:max-w-xs">
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

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const titleInput = document.getElementById('course-title-input');
                const slugInput = document.getElementById('course-slug-input');

                if (titleInput && slugInput) {
                    const slugify = (value) => value
                        .toString()
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');

                    titleInput.addEventListener('input', () => {
                        if (! slugInput.dataset.manuallyEdited) {
                            slugInput.value = slugify(titleInput.value);
                        }
                    });

                    slugInput.addEventListener('input', () => {
                        slugInput.dataset.manuallyEdited = 'true';
                    });
                }

                document.querySelectorAll('[data-wysiwyg-editor]').forEach((editor) => {
                    const shell = editor.closest('[data-wysiwyg-shell]');
                    const toolbar = shell?.querySelector('[data-wysiwyg-toolbar]');
                    const target = document.getElementById(editor.dataset.target);
                    if (! toolbar || ! target) {
                        return;
                    }

                    const sync = () => {
                        const html = editor.innerHTML.trim();
                        target.value = (html === '' || html === '<br>') ? '' : html;
                    };

                    editor.addEventListener('input', sync);
                    editor.addEventListener('blur', sync);

                    editor.closest('form')?.addEventListener('submit', sync);

                    toolbar.querySelectorAll('button').forEach((button) => {
                        button.addEventListener('mousedown', (event) => event.preventDefault());
                        button.addEventListener('click', () => {
                            editor.focus();

                            const action = button.dataset.action;
                            const command = button.dataset.command;
                            let arg = button.dataset.arg || null;

                            if (action === 'table') {
                                const rows = parseInt(prompt('Rows?', '3') || '0', 10);
                                const cols = parseInt(prompt('Columns?', '3') || '0', 10);
                                if (rows > 0 && cols > 0) {
                                    let html = '<table><thead><tr>';
                                    for (let c = 0; c < cols; c += 1) html += '<th>Header ' + (c + 1) + '</th>';
                                    html += '</tr></thead><tbody>';
                                    for (let r = 0; r < rows; r += 1) {
                                        html += '<tr>';
                                        for (let c = 0; c < cols; c += 1) html += '<td>&nbsp;</td>';
                                        html += '</tr>';
                                    }
                                    html += '</tbody></table><p><br></p>';
                                    document.execCommand('insertHTML', false, html);
                                    sync();
                                }
                                return;
                            }

                            if (command === 'createLink') {
                                const url = prompt('Link URL', 'https://');
                                if (!url) return;
                                arg = url;
                            } else if (command === 'insertImage') {
                                const url = prompt('Image URL', 'https://');
                                if (!url) return;
                                arg = url;
                            }

                            if (command) {
                                document.execCommand(command, false, arg);
                                sync();
                            }
                        });
                    });

                    sync();
                });

                document.querySelectorAll('[data-tier-rows]').forEach((container) => {
                    const wrapper = container.closest('.rounded-lg') || container.parentElement;
                    const addBtn = wrapper?.querySelector('[data-add-tier-row]');
                    const template = wrapper?.querySelector('[data-tier-row-template]');

                    const bindRemove = (row) => {
                        row.querySelector('[data-remove-tier-row]')?.addEventListener('click', () => {
                            if (container.querySelectorAll('[data-tier-row]').length > 1) {
                                row.remove();
                            } else {
                                row.querySelectorAll('input').forEach((input) => { input.value = ''; });
                            }
                        });
                    };

                    container.querySelectorAll('[data-tier-row]').forEach(bindRemove);

                    addBtn?.addEventListener('click', () => {
                        if (! template) return;
                        const index = container.querySelectorAll('[data-tier-row]').length;
                        const html = template.innerHTML.replace(/__INDEX__/g, String(index));
                        const wrap = document.createElement('div');
                        wrap.innerHTML = html.trim();
                        const row = wrap.firstElementChild;
                        container.appendChild(row);
                        bindRemove(row);
                    });
                });

                document.querySelectorAll('[data-question-type-select]').forEach((select) => {
                    const form = select.closest('form');
                    const optionPanel = form?.querySelector('[data-option-panel]');
                    const optionInputs = optionPanel ? Array.from(optionPanel.querySelectorAll('input[name*="[option_text]"]')) : [];

                    const updateOptionPanel = () => {
                        const type = select.value;
                        const needsOptions = ['single_choice', 'multiple_choice', 'true_false'].includes(type);

                        if (optionPanel) {
                            optionPanel.classList.toggle('hidden', ! needsOptions);
                        }

                        if (type === 'true_false') {
                            optionInputs.forEach((input, index) => {
                                if (index === 0) {
                                    input.value = 'True';
                                    input.placeholder = 'True';
                                } else if (index === 1) {
                                    input.value = 'False';
                                    input.placeholder = 'False';
                                } else {
                                    input.value = '';
                                    input.placeholder = 'Unused for True/False';
                                }
                            });
                        } else {
                            optionInputs.forEach((input, index) => {
                                if (input.value === 'True' || input.value === 'False') {
                                    input.value = '';
                                }
                                input.placeholder = `Option ${index + 1}`;
                            });
                        }
                    };

                    select.addEventListener('change', updateOptionPanel);
                    updateOptionPanel();
                });
            });
        </script>

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
                            <details class="rounded-lg border border-slate-100 bg-slate-50 open:bg-white open:shadow-sm">
                                <summary class="flex cursor-pointer items-center justify-between gap-3 p-3">
                                    <div>
                                        <p class="font-bold text-mk-navy">{{ $module->title }}</p>
                                        <p class="text-xs text-slate-500">{{ $module->lessons->count() }} lessons - {{ $module->status }}</p>
                                    </div>
                                    <span class="rounded-md border border-mk-gold px-2 py-1 text-xs font-black text-mk-navy">Edit</span>
                                </summary>
                                @if (\Illuminate\Support\Facades\Route::has('instructor.modules.update'))
                                <form method="POST" action="{{ route('instructor.modules.update', $module) }}" class="space-y-3 border-t border-slate-100 p-3">
                                    @csrf
                                    @method('PUT')
                                    <input name="title" value="{{ $module->title }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Module title" required>
                                    <input name="slug" value="{{ $module->slug }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Slug">
                                    <textarea name="summary" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Short summary">{{ $module->summary }}</textarea>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <input name="sort_order" type="number" min="0" value="{{ $module->sort_order }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Sort order">
                                        <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                            @foreach (\App\Models\Course::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($module->status === $status)>{{ str($status)->headline() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex flex-wrap justify-between gap-2">
                                        <x-button type="submit" size="sm">Save Module</x-button>
                                    </div>
                                </form>
                                @endif
                                @if (\Illuminate\Support\Facades\Route::has('instructor.modules.destroy'))
                                <form method="POST" action="{{ route('instructor.modules.destroy', $module) }}" class="border-t border-slate-100 p-3" onsubmit="return confirm('Delete this module and all its lessons?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Delete module</button>
                                </form>
                                @endif
                            </details>
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

                    @if ($lessons->isNotEmpty())
                        <div class="mt-6 space-y-3">
                            <p class="text-sm font-bold text-mk-navy">Existing lessons</p>
                            @foreach ($lessons as $lesson)
                                <details class="rounded-lg border border-slate-100 bg-slate-50 open:bg-white open:shadow-sm">
                                    <summary class="flex cursor-pointer items-center justify-between gap-3 p-3">
                                        <div>
                                            <p class="font-bold text-mk-navy">{{ $lesson->title }}</p>
                                            <p class="text-xs text-slate-500">{{ $lesson->module?->title }} - {{ str($lesson->lesson_type)->headline() }} - {{ $lesson->status }}</p>
                                        </div>
                                        <span class="rounded-md border border-mk-gold px-2 py-1 text-xs font-black text-mk-navy">Edit</span>
                                    </summary>
                                    @if (\Illuminate\Support\Facades\Route::has('instructor.lessons.update'))
                                    <form method="POST" action="{{ route('instructor.lessons.update', $lesson) }}" class="space-y-3 border-t border-slate-100 p-3">
                                        @csrf
                                        @method('PUT')
                                        <select name="module_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                                            @foreach ($modules as $module)
                                                <option value="{{ $module->id }}" @selected($lesson->module_id === $module->id)>{{ $module->title }}</option>
                                            @endforeach
                                        </select>
                                        <input name="title" value="{{ $lesson->title }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Lesson title" required>
                                        <input name="slug" value="{{ $lesson->slug }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Slug">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <select name="lesson_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                @foreach (['video', 'text', 'quiz', 'assignment', 'live'] as $type)
                                                    <option value="{{ $type }}" @selected($lesson->lesson_type === $type)>{{ str($type)->headline() }}</option>
                                                @endforeach
                                            </select>
                                            <input name="duration_minutes" type="number" min="0" value="{{ $lesson->duration_minutes }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Minutes">
                                        </div>
                                        <input name="video_url" value="{{ $lesson->video_url }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Optional YouTube URL">
                                        <textarea name="content" rows="4" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Lesson notes/body">{{ $lesson->content }}</textarea>
                                        <textarea name="summary" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Short summary">{{ $lesson->summary }}</textarea>
                                        <div class="grid gap-3 sm:grid-cols-3">
                                            <input name="sort_order" type="number" min="0" value="{{ $lesson->sort_order }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Sort order">
                                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                @foreach (\App\Models\Course::STATUSES as $status)
                                                    <option value="{{ $status }}" @selected($lesson->status === $status)>{{ str($status)->headline() }}</option>
                                                @endforeach
                                            </select>
                                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-mk-navy">
                                                <input type="checkbox" name="is_free_preview" value="1" @checked($lesson->is_free_preview)> Preview
                                            </label>
                                        </div>
                                        <x-button type="submit" size="sm">Save Lesson</x-button>
                                    </form>
                                    @endif
                                    @if (\Illuminate\Support\Facades\Route::has('instructor.lessons.destroy'))
                                    <form method="POST" action="{{ route('instructor.lessons.destroy', $lesson) }}" class="border-t border-slate-100 p-3" onsubmit="return confirm('Delete this lesson and its materials?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-red-600 hover:underline">Delete lesson</button>
                                    </form>
                                    @endif
                                </details>
                            @endforeach
                        </div>

                        <div class="mt-8 rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <p class="text-sm font-bold text-mk-navy">Upload lesson notes/material</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Allowed: PDF, images, Word, PowerPoint. Max 10MB.</p>
                            <form method="POST" action="{{ route('instructor.lesson-materials.store', $course) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                @csrf
                                <select name="lesson_id" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" required>
                                    <option value="">Attach material to lesson</option>
                                    @foreach ($lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->module?->title }} - {{ $lesson->title }}</option>
                                    @endforeach
                                </select>
                                <input name="title" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Material title" required>
                                <textarea name="instructions" rows="2" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Optional student instructions"></textarea>
                                <input name="material_file" type="file" accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.ppt,.pptx,application/pdf,image/png,image/jpeg,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-mk-gold file:px-4 file:py-2 file:text-sm file:font-bold file:text-mk-navy" required>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <input name="sort_order" type="number" min="0" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="Sort order">
                                    <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                                        @foreach (\App\Models\Course::STATUSES as $status)
                                            <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <x-button type="submit" size="sm">Upload Material</x-button>
                            </form>
                        </div>
                    @endif

                    @if ($lessons->flatMap(fn ($lesson) => $lesson->activities)->isNotEmpty())
                        <div class="mt-6 space-y-3">
                            <p class="text-sm font-bold text-mk-navy">Uploaded materials</p>
                            @foreach ($lessons as $lesson)
                                @foreach ($lesson->activities as $activity)
                                    @if ($activity->hasUploadedResource() || $activity->resource_url)
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-badge tone="gray">{{ $lesson->title }}</x-badge>
                                                <x-badge :tone="$activity->isPdfResource() ? 'gold' : 'blue'">{{ $activity->isPdfResource() ? 'PDF' : str($activity->activity_type)->headline() }}</x-badge>
                                                <x-status-badge :status="$activity->status" />
                                            </div>
                                            <p class="mt-2 text-sm font-bold text-mk-navy">{{ $activity->title }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-card>
                    <x-badge tone="green">Quiz</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add Quiz</h2>
                    <form method="POST" action="{{ route('instructor.quizzes.store', $course) }}" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="text-sm font-bold text-mk-navy">Related lesson
                                <select name="lesson_id" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                                    <option value="">Select lesson</option>
                                    @foreach ($lessons as $lesson)
                                        <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm font-bold text-mk-navy">Quiz title
                                <input name="title" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Quiz title" required>
                            </label>
                        </div>
                        <label class="block text-sm font-bold text-mk-navy">Quiz instructions
                            <textarea name="description" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Instructions students read before starting"></textarea>
                        </label>
                        <div class="grid gap-3 sm:grid-cols-4">
                            <input name="passing_score" type="number" min="0" max="100" value="50" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Passing score">
                            <input name="time_limit_minutes" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Time limit">
                            <input name="max_attempts" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Attempt limit">
                            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach (\App\Models\Quiz::STATUSES as $status)
                                    <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-mk-navy">Add Question</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Optional first question for this quiz.</p>
                                </div>
                                <button type="button" class="rounded-lg border border-mk-gold px-3 py-2 text-xs font-black text-mk-navy">Add Option</button>
                            </div>
                            <textarea name="question_text" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question text"></textarea>
                            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                <select name="question_type" data-question-type-select class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_SINGLE_CHOICE }}">Single choice</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_MULTIPLE_CHOICE }}">Multiple choice</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_TRUE_FALSE }}">True / False</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_SHORT_ANSWER }}">Short answer</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_LONG_ANSWER }}">Long answer</option>
                                </select>
                                <input name="points" type="number" min="1" value="1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Points">
                                <select name="question_status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <option value="{{ \App\Models\QuizQuestion::STATUS_PUBLISHED }}">Published</option>
                                    <option value="{{ \App\Models\QuizQuestion::STATUS_DRAFT }}">Draft</option>
                                </select>
                            </div>
                            <div class="mt-3 space-y-2" data-option-panel>
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Options</p>
                                <p class="text-xs font-semibold text-slate-500">No options yet? Add at least two option rows before saving a question.</p>
                                @foreach (['Option A', 'Option B', 'Option C', 'Option D', 'Option E'] as $index => $label)
                                    <div class="grid gap-2 rounded-lg bg-white p-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                                        <input name="options[{{ $index }}][option_text]" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="{{ $label }}">
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                            <input type="radio" name="correct_option_index" value="{{ $index }}" @checked($index === 0) class="text-mk-gold focus:ring-mk-gold">
                                            Single correct
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                            <input type="checkbox" name="correct_option_indexes[]" value="{{ $index }}" @checked($index === 0) class="rounded text-mk-gold focus:ring-mk-gold">
                                            Multiple correct
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-button type="submit" size="sm">Save Quiz</x-button>
                            <x-button type="submit" name="publish_quiz" value="1" size="sm" variant="secondary">Publish Quiz</x-button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-4">
                        @forelse ($quizzes as $quizItem)
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black text-mk-navy">{{ $quizItem->title }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500">{{ $quizItem->lesson?->title ?? 'Lesson' }} - {{ str($quizItem->status)->headline() }}</p>
                                    </div>
                                    <x-badge tone="blue">{{ $quizItem->questions->count() }} questions</x-badge>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @forelse ($quizItem->questions as $question)
                                        <div class="rounded-lg bg-slate-50 p-3">
                                            <p class="text-sm font-bold text-mk-navy">{{ $question->question_text }}</p>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @if ($question->requiresOptions())
                                                    @forelse ($question->options as $option)
                                                        <x-badge :tone="$option->is_correct ? 'green' : 'gray'">{{ $option->option_text }}</x-badge>
                                                    @empty
                                                        <span class="text-xs font-semibold text-slate-500">No options yet</span>
                                                    @endforelse
                                                @else
                                                    <x-badge tone="gray">{{ $question->acceptsLongTextAnswer() ? 'Long Text Answer' : 'Short Text Answer' }}</x-badge>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                                            <p class="text-sm font-black text-mk-navy">No questions yet</p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500">Add your first question</p>
                                        </div>
                                    @endforelse
                                </div>

                                <form method="POST" action="{{ route('instructor.quizzes.questions.store', $quizItem) }}" class="mt-4 space-y-3 rounded-lg border border-mk-gold/30 bg-mk-goldSoft/40 p-4">
                                    @csrf
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <p class="text-sm font-black text-mk-navy">Add Question</p>
                                        <button type="button" class="rounded-lg border border-mk-gold px-3 py-2 text-xs font-black text-mk-navy">Add Option</button>
                                    </div>
                                    <textarea name="question_text" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question text" required></textarea>
                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <select name="question_type" data-question-type-select class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                            <option value="{{ \App\Models\QuizQuestion::TYPE_SINGLE_CHOICE }}">Single choice</option>
                                            <option value="{{ \App\Models\QuizQuestion::TYPE_MULTIPLE_CHOICE }}">Multiple choice</option>
                                            <option value="{{ \App\Models\QuizQuestion::TYPE_TRUE_FALSE }}">True / False</option>
                                            <option value="{{ \App\Models\QuizQuestion::TYPE_SHORT_ANSWER }}">Short answer</option>
                                            <option value="{{ \App\Models\QuizQuestion::TYPE_LONG_ANSWER }}">Long answer</option>
                                        </select>
                                        <input name="points" type="number" min="1" value="1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Points" required>
                                        <select name="question_status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                            <option value="{{ \App\Models\QuizQuestion::STATUS_PUBLISHED }}">Published</option>
                                            <option value="{{ \App\Models\QuizQuestion::STATUS_DRAFT }}">Draft</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2" data-option-panel>
                                        <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Options</p>
                                        <p class="text-xs font-semibold text-slate-500">No options yet? Fill at least two rows.</p>
                                        @foreach (['Option A', 'Option B', 'Option C', 'Option D', 'Option E'] as $index => $label)
                                            <div class="grid gap-2 rounded-lg bg-white p-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                                                <input name="options[{{ $index }}][option_text]" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="{{ $label }}">
                                                <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                                    <input type="radio" name="correct_option_index" value="{{ $index }}" @checked($index === 0) class="text-mk-gold focus:ring-mk-gold">
                                                    Single correct
                                                </label>
                                                <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                                    <input type="checkbox" name="correct_option_indexes[]" value="{{ $index }}" @checked($index === 0) class="rounded text-mk-gold focus:ring-mk-gold">
                                                    Multiple correct
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <x-button type="submit" size="sm">Save Question</x-button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm font-black text-mk-navy">No questions yet</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Add your first question by saving a quiz above.</p>
                            </div>
                        @endforelse
                    </div>
                </x-card>

                <x-card>
                    <x-badge tone="gold">Final Test</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Final Test</h2>

                    @if ($finalTest)
                        <div class="mt-4 rounded-lg border border-mk-gold/40 bg-mk-goldSoft/40 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-mk-navy">{{ $finalTest->title }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ str($finalTest->status)->headline() }} - Passing score {{ $finalTest->passing_score }}%</p>
                                </div>
                                <x-badge tone="blue">{{ $finalTest->questions->count() }} questions</x-badge>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $finalTest->description ?: 'No instructions added yet.' }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold text-slate-600">
                                <span>Time: {{ $finalTest->time_limit_minutes ? $finalTest->time_limit_minutes.' min' : 'None' }}</span>
                                <span>Attempts: {{ $finalTest->max_attempts ?: 'Unlimited' }}</span>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            @forelse ($finalTest->questions as $question)
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <p class="text-sm font-bold text-mk-navy">{{ $question->question_text }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($question->requiresOptions())
                                            @forelse ($question->options as $option)
                                                <x-badge :tone="$option->is_correct ? 'green' : 'gray'">{{ $option->option_text }}</x-badge>
                                            @empty
                                                <span class="text-xs font-semibold text-slate-500">No options yet</span>
                                            @endforelse
                                        @else
                                            <x-badge tone="gray">{{ $question->acceptsLongTextAnswer() ? 'Long Text Answer' : 'Short Text Answer' }}</x-badge>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-black text-mk-navy">No questions yet</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">Add your first question</p>
                                </div>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('instructor.quizzes.questions.store', $finalTest) }}" class="mt-4 space-y-3 rounded-lg border border-mk-gold/30 bg-white p-4">
                            @csrf
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-black text-mk-navy">Manage Questions</p>
                                <button type="button" class="rounded-lg border border-mk-gold px-3 py-2 text-xs font-black text-mk-navy">Add Option</button>
                            </div>
                            <textarea name="question_text" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question text" required></textarea>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <select name="question_type" data-question-type-select class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_SINGLE_CHOICE }}">Single choice</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_MULTIPLE_CHOICE }}">Multiple choice</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_TRUE_FALSE }}">True / False</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_SHORT_ANSWER }}">Short answer</option>
                                    <option value="{{ \App\Models\QuizQuestion::TYPE_LONG_ANSWER }}">Long answer</option>
                                </select>
                                <input name="points" type="number" min="1" value="1" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Points" required>
                                <select name="question_status" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <option value="{{ \App\Models\QuizQuestion::STATUS_PUBLISHED }}">Published</option>
                                    <option value="{{ \App\Models\QuizQuestion::STATUS_DRAFT }}">Draft</option>
                                </select>
                            </div>
                            <div class="space-y-2" data-option-panel>
                                <p class="text-xs font-black uppercase tracking-wide text-mk-gold">Options</p>
                                <p class="text-xs font-semibold text-slate-500">No options yet? Fill at least two rows.</p>
                                @foreach (['Option A', 'Option B', 'Option C', 'Option D', 'Option E'] as $index => $label)
                                    <div class="grid gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                                        <input name="options[{{ $index }}][option_text]" class="rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="{{ $label }}">
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                            <input type="radio" name="correct_option_index" value="{{ $index }}" @checked($index === 0) class="text-mk-gold focus:ring-mk-gold">
                                            Single correct
                                        </label>
                                        <label class="inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                            <input type="checkbox" name="correct_option_indexes[]" value="{{ $index }}" @checked($index === 0) class="rounded text-mk-gold focus:ring-mk-gold">
                                            Multiple correct
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <x-button type="submit" size="sm">Save Question</x-button>
                        </form>
                    @else
                        <div class="mt-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-black text-mk-navy">No final test yet</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Add a final test to assess students at the end of the course.</p>
                        </div>

                        <form method="POST" action="{{ route('instructor.final-tests.store', $course) }}" class="mt-5 space-y-4">
                            @csrf
                            <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Final Test title" required>
                            <textarea name="description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Final Test instructions"></textarea>
                            <div class="grid gap-3 sm:grid-cols-4">
                                <input name="passing_score" type="number" min="0" max="100" value="50" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Passing score">
                                <input name="time_limit_minutes" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Time limit">
                                <input name="max_attempts" type="number" min="1" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Attempt limit">
                                <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    @foreach (\App\Models\Quiz::STATUSES as $status)
                                        <option value="{{ $status }}">{{ str($status)->headline() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-button type="submit" size="sm">Add Final Test</x-button>
                                <x-button type="submit" name="publish_quiz" value="1" size="sm" variant="secondary">Publish Final Test</x-button>
                            </div>
                        </form>
                    @endif
                </x-card>

                <x-card>
                    <x-badge tone="blue">Assignment</x-badge>
                    <h2 class="mt-4 text-xl font-extrabold text-mk-navy">Add assignment</h2>
                    <form method="POST" action="{{ route('instructor.assignments.store', $course) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                        @csrf
                        <div class="grid gap-3 md:grid-cols-2">
                            <select name="lesson_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" required>
                                <option value="">Attach to lesson</option>
                                @foreach ($lessons as $lesson)
                                    <option value="{{ $lesson->id }}">{{ $lesson->module?->title }} - {{ $lesson->title }}</option>
                                @endforeach
                            </select>
                            <input name="title" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Assignment title" required>
                        </div>
                        <textarea name="instructions" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Instructions" required></textarea>
                        <label class="block">
                            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">Assignment document</span>
                            <input name="instruction_file" type="file" class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span class="mt-1 block text-xs font-semibold text-slate-500">Allowed: pdf, doc, docx, ppt, pptx, txt, zip, png, jpg, jpeg. Max 10MB.</span>
                        </label>
                        <div class="grid gap-3 md:grid-cols-4">
                            <select name="submission_type" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                @foreach ([\App\Models\Assignment::TYPE_TEXT, \App\Models\Assignment::TYPE_FILE, \App\Models\Assignment::TYPE_LINK, \App\Models\Assignment::TYPE_MIXED] as $type)
                                    <option value="{{ $type }}">{{ str($type)->headline() }}</option>
                                @endforeach
                            </select>
                            <input name="max_score" type="number" min="1" value="100" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Max score">
                            <input name="due_days_after_enrollment" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Due days">
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
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm font-bold text-mk-navy">Add Question</p>
                                <label class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500">
                                    <input type="checkbox" name="question_required" value="1" checked> Required
                                </label>
                            </div>
                            <textarea name="question_text" rows="2" class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question prompt"></textarea>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <select name="question_type" data-question-type-select class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <option value="textarea">Long answer</option>
                                    <option value="text">Short answer</option>
                                    <option value="single_choice">Single choice</option>
                                    <option value="multiple_choice">Multiple choice</option>
                                    <option value="true_false">True/False</option>
                                </select>
                                <input name="question_points" type="number" min="0" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Question points">
                            </div>
                            <div class="mt-4 grid gap-3 md:grid-cols-2" data-option-panel>
                                @for ($optionIndex = 0; $optionIndex < 4; $optionIndex++)
                                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                                        <input name="options[{{ $optionIndex }}][option_text]" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Option {{ $optionIndex + 1 }}">
                                        <div class="mt-2 flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
                                            <label class="flex items-center gap-2"><input type="radio" name="correct_option_index" value="{{ $optionIndex }}"> Single/True-False correct</label>
                                            <label class="flex items-center gap-2"><input type="checkbox" name="correct_option_indexes[]" value="{{ $optionIndex }}"> Multiple correct</label>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        <x-button type="submit" size="sm">Add Assignment</x-button>
                    </form>
                </x-card>
            </div>
        @endif
    </div>
</x-dashboard-layout>


