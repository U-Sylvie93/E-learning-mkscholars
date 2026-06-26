@php
    $hasFilters = ! empty($filters);
    $activeFilters = collect(request()->only(['from', 'to', 'course_id', 'status']))->filter(fn ($value) => filled($value));
    $hasActiveFilters = $activeFilters->isNotEmpty();
    $currentUrl = url()->current();
    $cards = $cards ?? [];
    $tables = $tables ?? [];
    $links = $links ?? [];
    $exports = $exports ?? [];

    $statusBadge = function (mixed $value): ?string {
        $normalized = str((string) $value)->lower()->replace(' ', '_')->toString();

        return match ($normalized) {
            'active', 'approved', 'issued', 'published', 'completed', 'passed', 'attended', 'graded' => 'success',
            'pending', 'submitted', 'under_review', 'scheduled', 'live', 'draft' => 'warning',
            'rejected', 'revoked', 'failed', 'missed', 'cancelled', 'suspended' => 'danger',
            'archived', 'inactive', 'withdrawn' => 'gray',
            default => null,
        };
    };
@endphp

<x-filament-panels::page>
    <style>
        .mk-admin-report-shell { --mk-navy:#073653; --mk-gold:#ffc40c; --mk-border:#d8e1ea; --mk-muted:#64748b; max-width:1180px; margin:0 auto; padding:0 0 2rem; display:grid; gap:1.25rem; color:#0f172a; }
        .mk-report-card,.mk-report-content-card,.mk-report-kpi-card { background:#fff; border:1px solid var(--mk-border); border-radius:22px; box-shadow:0 18px 45px rgba(15,23,42,.08); overflow:hidden; }
        .mk-report-header-card { background:linear-gradient(135deg,#fff 0%,#f8fafc 48%,#fff7d6 100%); }
        .mk-report-header-inner,.mk-report-section-inner,.mk-report-filter-card,.mk-report-export-center { padding:1.5rem; }
        .mk-report-row { display:flex; gap:1rem; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; }
        .mk-report-brand-line,.mk-report-pill-row,.mk-report-actions,.mk-report-filter-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.7rem; }
        .mk-report-logo { display:inline-flex; width:2.65rem; height:2.65rem; align-items:center; justify-content:center; border-radius:999px; background:var(--mk-navy); color:var(--mk-gold); font-size:.78rem; font-weight:900; letter-spacing:.04em; box-shadow:inset 0 0 0 1px rgba(255,196,12,.55); }
        .mk-report-kicker,.mk-report-badge { display:inline-flex; width:fit-content; align-items:center; border-radius:999px; padding:.35rem .7rem; font-size:.72rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
        .mk-report-kicker { border:1px solid #fde68a; background:#fffbeb; color:#92400e; }
        .mk-report-title { margin:1rem 0 0; font-size:clamp(1.75rem,3vw,2.45rem); line-height:1.08; font-weight:900; letter-spacing:-.02em; color:#0f172a; }
        .mk-report-description { max-width:46rem; margin:.7rem 0 0; color:#475569; line-height:1.65; font-size:.96rem; }
        .mk-report-badge { margin-top:1rem; background:var(--mk-navy); color:#fff; }
        .mk-report-badge-muted { background:#fef3c7; color:#78350f; }
        .mk-report-button,.mk-report-button-secondary { display:inline-flex; align-items:center; justify-content:center; min-height:2.65rem; border-radius:12px; padding:.7rem 1rem; font-weight:850; font-size:.9rem; text-decoration:none; transition:transform .15s ease,box-shadow .15s ease,background .15s ease; }
        .mk-report-button { border:1px solid var(--mk-navy); background:var(--mk-navy); color:#fff; box-shadow:0 10px 24px rgba(7,54,83,.2); }
        .mk-report-button:hover,.mk-report-button-secondary:hover { transform:translateY(-1px); }
        .mk-report-button-secondary { border:1px solid var(--mk-border); background:#fff; color:#334155; }
        .mk-report-apply-button { border-color:#f59e0b; background:var(--mk-gold); color:#111827; box-shadow:0 10px 24px rgba(245,158,11,.2); }
        .mk-report-sections { border-top:1px solid #e2e8f0; padding:1.25rem 1.5rem 1.5rem; background:rgba(255,255,255,.72); }
        .mk-report-section-heading { margin:0; color:#0f172a; font-size:1.05rem; font-weight:900; }
        .mk-report-section-copy { margin:.25rem 0 0; color:var(--mk-muted); font-size:.9rem; line-height:1.55; }
        .mk-report-link-grid,.mk-report-export-grid,.mk-report-filter-grid,.mk-report-kpi-grid { display:grid; gap:.9rem; margin-top:1rem; }
        .mk-report-link-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .mk-report-export-grid { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .mk-report-filter-grid,.mk-report-kpi-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
        .mk-report-link-card { display:flex; align-items:center; justify-content:space-between; gap:.75rem; border:1px solid #dbe5ef; border-radius:16px; background:#f8fafc; color:#1e293b; padding:.95rem 1rem; font-weight:900; text-decoration:none; }
        .mk-report-link-card:hover,.mk-report-link-card.is-active { border-color:#facc15; background:#fffbeb; color:#0f172a; }
        .mk-report-filter-form { margin-top:1rem; display:grid; gap:1rem; }
        .mk-report-field label { display:block; margin-bottom:.45rem; color:#334155; font-size:.88rem; font-weight:850; }
        .mk-report-field input,.mk-report-field select { width:100%; min-height:2.65rem; border:1px solid #cbd5e1; border-radius:12px; background:#fff; color:#0f172a; padding:.55rem .75rem; font-size:.92rem; }
        .mk-report-kpi-card { position:relative; min-height:9.5rem; padding:1.15rem; overflow:hidden; }
        .mk-report-kpi-card::before { content:""; position:absolute; inset:0 auto 0 0; width:.35rem; background:linear-gradient(180deg,var(--mk-gold),#f59e0b); }
        .mk-report-kpi-card::after { content:""; position:absolute; right:-2.2rem; top:-2.2rem; width:6rem; height:6rem; border-radius:999px; background:rgba(7,54,83,.07); }
        .mk-report-kpi-label,.mk-report-kpi-value,.mk-report-kpi-hint,.mk-report-kpi-index { position:relative; z-index:1; }
        .mk-report-kpi-label { margin:0; color:#64748b; font-size:.86rem; font-weight:850; }
        .mk-report-kpi-value { margin:.75rem 0 0; color:#0f172a; font-size:clamp(1.85rem,3vw,2.35rem); line-height:1.05; font-weight:950; overflow-wrap:anywhere; }
        .mk-report-kpi-hint { margin:.75rem 0 0; color:#64748b; font-size:.88rem; line-height:1.5; }
        .mk-report-kpi-index { position:absolute; right:1rem; top:1rem; display:inline-flex; width:2rem; height:2rem; align-items:center; justify-content:center; border-radius:999px; background:var(--mk-navy); color:var(--mk-gold); font-size:.75rem; font-weight:900; }
        .mk-report-export-center { background:linear-gradient(135deg,#fff 0%,#fff7d6 55%,#fff 100%); border-color:#fde68a; }
        .mk-report-export-card { display:flex; min-height:11rem; flex-direction:column; justify-content:space-between; gap:1rem; border:1px solid #fde68a; border-radius:18px; background:#fff; padding:1rem; }
        .mk-report-export-card h3 { margin:0; color:#0f172a; font-size:1rem; font-weight:900; }
        .mk-report-export-card p { margin:.5rem 0 0; color:#64748b; line-height:1.55; font-size:.9rem; }
        .mk-report-tables { display:grid; gap:1rem; }
        .mk-report-table-head { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; flex-wrap:wrap; border-bottom:1px solid #e2e8f0; background:#f8fafc; padding:1rem 1.25rem; }
        .mk-report-table-title { margin:0; color:#0f172a; font-size:1.02rem; font-weight:950; }
        .mk-report-table-count { margin:.25rem 0 0; color:#64748b; font-size:.88rem; }
        .mk-report-readonly-pill { display:inline-flex; border:1px solid #cbd5e1; border-radius:999px; background:#fff; color:#475569; padding:.35rem .65rem; font-size:.7rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
        .mk-report-table-wrap { overflow-x:auto; }
        .mk-report-table { width:100%; min-width:720px; border-collapse:collapse; font-size:.9rem; }
        .mk-report-table th { background:#fff; color:#64748b; font-size:.75rem; font-weight:950; letter-spacing:.04em; text-align:left; text-transform:uppercase; padding:.9rem 1.25rem; border-bottom:1px solid #e2e8f0; }
        .mk-report-table td { color:#334155; padding:1rem 1.25rem; border-bottom:1px solid #f1f5f9; vertical-align:top; overflow-wrap:anywhere; }
        .mk-report-table tr:hover td { background:#fffbeb; }
        .mk-report-status { display:inline-flex; border-radius:999px; border:1px solid #cbd5e1; background:#f8fafc; padding:.28rem .62rem; color:#475569; font-size:.76rem; font-weight:850; }
        .mk-report-status-success { border-color:#a7f3d0; background:#ecfdf5; color:#047857; }
        .mk-report-status-warning { border-color:#fde68a; background:#fffbeb; color:#92400e; }
        .mk-report-status-danger { border-color:#fecdd3; background:#fff1f2; color:#be123c; }
        .mk-report-status-gray { border-color:#cbd5e1; background:#f8fafc; color:#475569; }
        .mk-report-empty { max-width:28rem; margin:1rem auto; border:1px dashed #cbd5e1; border-radius:18px; background:#f8fafc; padding:1.25rem; text-align:center; }
        .mk-report-empty strong { display:block; color:#0f172a; font-size:.96rem; }
        .mk-report-empty span { display:block; margin-top:.45rem; color:#64748b; line-height:1.55; font-size:.88rem; }
        @media (max-width:1024px) { .mk-report-link-grid,.mk-report-kpi-grid,.mk-report-filter-grid,.mk-report-export-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:640px) { .mk-report-header-inner,.mk-report-sections,.mk-report-filter-card,.mk-report-export-center { padding:1rem; } .mk-report-link-grid,.mk-report-kpi-grid,.mk-report-filter-grid,.mk-report-export-grid { grid-template-columns:1fr; } .mk-report-actions,.mk-report-filter-actions,.mk-report-button,.mk-report-button-secondary { width:100%; } }
        .dark .mk-admin-report-shell,.fi.dark .mk-admin-report-shell { --mk-border:#334155; color:#e5e7eb; }
        .dark .mk-report-card,.dark .mk-report-kpi-card,.dark .mk-report-content-card,.dark .mk-report-export-card,.fi.dark .mk-report-card,.fi.dark .mk-report-kpi-card,.fi.dark .mk-report-content-card,.fi.dark .mk-report-export-card { background:#111827; border-color:#334155; box-shadow:0 18px 45px rgba(0,0,0,.25); }
        .dark .mk-report-header-card,.fi.dark .mk-report-header-card { background:linear-gradient(135deg,#111827 0%,#0f172a 55%,rgba(255,196,12,.12) 100%); }
        .dark .mk-report-title,.dark .mk-report-section-heading,.dark .mk-report-kpi-value,.dark .mk-report-table-title,.dark .mk-report-export-card h3,.dark .mk-report-empty strong,.fi.dark .mk-report-title,.fi.dark .mk-report-section-heading,.fi.dark .mk-report-kpi-value,.fi.dark .mk-report-table-title,.fi.dark .mk-report-export-card h3,.fi.dark .mk-report-empty strong { color:#f8fafc; }
        .dark .mk-report-description,.dark .mk-report-section-copy,.dark .mk-report-kpi-label,.dark .mk-report-kpi-hint,.dark .mk-report-table-count,.dark .mk-report-export-card p,.dark .mk-report-table td,.fi.dark .mk-report-description,.fi.dark .mk-report-section-copy,.fi.dark .mk-report-kpi-label,.fi.dark .mk-report-kpi-hint,.fi.dark .mk-report-table-count,.fi.dark .mk-report-export-card p,.fi.dark .mk-report-table td { color:#cbd5e1; }
        .dark .mk-report-sections,.dark .mk-report-table-head,.dark .mk-report-table th,.fi.dark .mk-report-sections,.fi.dark .mk-report-table-head,.fi.dark .mk-report-table th { background:#0f172a; border-color:#334155; color:#cbd5e1; }
        .dark .mk-report-field input,.dark .mk-report-field select,.fi.dark .mk-report-field input,.fi.dark .mk-report-field select { background:#0f172a; border-color:#475569; color:#f8fafc; }
    </style>

    <div class="mk-admin-report-shell" data-testid="admin-report-shell">
        <section class="mk-report-card mk-report-header-card" data-testid="report-header-card">
            <div class="mk-report-header-inner">
                <div class="mk-report-row">
                    <div>
                        <div class="mk-report-brand-line">
                            <span class="mk-report-logo">MK</span>
                            <span class="mk-report-kicker">MK Scholars analytics</span>
                        </div>
                        <h1 class="mk-report-title">Reports &amp; Analytics</h1>
                        <p class="mk-report-description">{{ $description ?? 'A read-only command center for MK Scholars platform activity.' }}</p>
                        <div class="mk-report-pill-row">
                            <span class="mk-report-badge">{{ $title ?? 'Reports Overview' }}</span>
                            @if ($hasActiveFilters)
                                <span class="mk-report-badge mk-report-badge-muted">{{ $activeFilters->count() }} active {{ $activeFilters->count() === 1 ? 'filter' : 'filters' }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="mk-report-actions">
                        @if ($hasActiveFilters)
                            <a href="{{ $currentUrl }}" class="mk-report-button-secondary">Reset view</a>
                        @endif
                        @if (! empty($exports))
                            <a href="#export-center" class="mk-report-button">Export CSV</a>
                        @endif
                    </div>
                </div>
            </div>

            @if (! empty($links))
                <div class="mk-report-sections">
                    <h2 class="mk-report-section-heading">Report Sections</h2>
                    <p class="mk-report-section-copy">Switch between focused read-only dashboards.</p>
                    <div class="mk-report-link-grid">
                        @foreach ($links as $link)
                            @php($isActiveLink = $currentUrl === url($link['url']))
                            <a href="{{ $link['url'] }}" class="mk-report-link-card {{ $isActiveLink ? 'is-active' : '' }}">
                                <span>{{ $link['label'] }}</span>
                                <span>&rarr;</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        @if ($hasFilters)
            <section class="mk-report-card mk-report-filter-card" data-testid="report-filter-card">
                <div class="mk-report-row">
                    <div>
                        <p class="mk-report-kicker">Filter panel</p>
                        <h2 class="mk-report-section-heading">Refine Report Data</h2>
                        <p class="mk-report-section-copy">Use existing report parameters to narrow this view without changing report calculations.</p>
                    </div>
                    @if ($hasActiveFilters)
                        <span class="mk-report-badge mk-report-badge-muted">Filtered</span>
                    @endif
                </div>

                <form method="GET" class="mk-report-filter-form">
                    <div class="mk-report-filter-grid">
                        @if ($filters['date'] ?? false)
                            <div class="mk-report-field">
                                <label for="from">Date from</label>
                                <input id="from" name="from" type="date" value="{{ request('from') }}">
                            </div>
                            <div class="mk-report-field">
                                <label for="to">Date to</label>
                                <input id="to" name="to" type="date" value="{{ request('to') }}">
                            </div>
                        @endif

                        @if (! empty($filters['courses']))
                            <div class="mk-report-field">
                                <label for="course_id">Course</label>
                                <select id="course_id" name="course_id">
                                    <option value="">All courses</option>
                                    @foreach ($filters['courses'] as $id => $label)
                                        <option value="{{ $id }}" @selected((string) request('course_id') === (string) $id)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (! empty($filters['statuses']))
                            <div class="mk-report-field">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">All statuses</option>
                                    @foreach ($filters['statuses'] as $value => $label)
                                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="mk-report-filter-actions">
                        <button type="submit" class="mk-report-button mk-report-apply-button">Apply Filters</button>
                        <a href="{{ $currentUrl }}" class="mk-report-button-secondary">Reset</a>
                    </div>
                </form>
            </section>
        @endif

        @if (! empty($cards))
            <section aria-label="Report metrics" class="mk-report-kpi-grid" data-testid="report-kpi-grid">
                @foreach ($cards as $index => $card)
                    <article class="mk-report-kpi-card">
                        <span class="mk-report-kpi-index">{{ $index + 1 }}</span>
                        <p class="mk-report-kpi-label">{{ $card['label'] }}</p>
                        <p class="mk-report-kpi-value">{{ $card['value'] }}</p>
                        <p class="mk-report-kpi-hint">{{ $card['hint'] ?? 'Current platform snapshot' }}</p>
                    </article>
                @endforeach
            </section>
        @endif

        @if (! empty($exports))
            <section id="export-center" class="mk-report-card mk-report-export-center" data-testid="report-export-center">
                <div class="mk-report-row">
                    <div>
                        <p class="mk-report-kicker">Export Center</p>
                        <h2 class="mk-report-section-heading">Download CSV Reports</h2>
                        <p class="mk-report-section-copy">Admin-only CSV exports for operational review. Private file paths, password hashes, and provider payloads are not displayed here.</p>
                    </div>
                    <span class="mk-report-badge mk-report-badge-muted">{{ count($exports) }} exports</span>
                </div>

                <div class="mk-report-export-grid">
                    @foreach ($exports as $export)
                        <article class="mk-report-export-card">
                            <div>
                                <h3>{{ $export['label'] }}</h3>
                                <p>{{ $export['description'] }}</p>
                            </div>
                            <a href="{{ $export['url'] }}" class="mk-report-button">Download CSV</a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mk-report-tables" aria-label="Report tables">
            @forelse ($tables as $table)
                <article class="mk-report-content-card" data-testid="report-content-card">
                    <div class="mk-report-table-head">
                        <div>
                            <h2 class="mk-report-table-title">{{ $table['title'] }}</h2>
                            <p class="mk-report-table-count">{{ count($table['rows']) }} {{ count($table['rows']) === 1 ? 'record' : 'records' }}</p>
                        </div>
                        <span class="mk-report-readonly-pill">Read only</span>
                    </div>

                    <div class="mk-report-table-wrap">
                        <table class="mk-report-table">
                            <thead>
                                <tr>
                                    @foreach ($table['columns'] as $column)
                                        <th>{{ $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($table['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            @php($tone = $statusBadge($cell))
                                            <td>
                                                @if ($tone)
                                                    <span class="mk-report-status mk-report-status-{{ $tone }}">{{ $cell }}</span>
                                                @else
                                                    <span>{{ $cell }}</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($table['columns']) }}">
                                            <div class="mk-report-empty">
                                                <strong>No report data found</strong>
                                                <span>Try changing the filters or check again after more platform activity is recorded.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <article class="mk-report-content-card" data-testid="report-content-card">
                    <div class="mk-report-empty">
                        <strong>No report tables configured</strong>
                        <span>This report currently contains summary cards only.</span>
                    </div>
                </article>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
