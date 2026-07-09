<x-filament-panels::page>
    <style>
        .mk-admin-settings-shell { --mk-navy:#073653; --mk-gold:#ffc40c; --mk-border:#d8e1ea; max-width:1180px; margin:0 auto; display:grid; gap:1rem; color:#0f172a; }
        .mk-settings-card { border:1px solid var(--mk-border); border-radius:18px; background:#fff; box-shadow:0 16px 38px rgba(15,23,42,.08); overflow:hidden; }
        .mk-settings-hero { background:linear-gradient(135deg,#fff 0%,#f8fafc 52%,#fff7d6 100%); padding:1.35rem; }
        .mk-settings-row { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .mk-settings-kicker,.mk-settings-badge { display:inline-flex; width:fit-content; border-radius:999px; padding:.32rem .68rem; font-size:.7rem; font-weight:900; letter-spacing:.05em; text-transform:uppercase; }
        .mk-settings-kicker { border:1px solid #fde68a; background:#fffbeb; color:#92400e; }
        .mk-settings-badge { background:var(--mk-navy); color:#fff; }
        .mk-settings-title { margin:.75rem 0 0; font-size:clamp(1.75rem,3vw,2.35rem); line-height:1.08; font-weight:950; color:#0f172a; }
        .mk-settings-copy { max-width:46rem; margin:.55rem 0 0; color:#475569; font-size:.93rem; line-height:1.65; }
        .mk-settings-link,.mk-settings-button { display:inline-flex; min-height:2.55rem; align-items:center; justify-content:center; border-radius:12px; padding:.68rem 1rem; font-size:.88rem; font-weight:900; text-decoration:none; }
        .mk-settings-link { border:1px solid var(--mk-border); background:#fff; color:#334155; }
        .mk-settings-button { border:1px solid var(--mk-navy); background:var(--mk-navy); color:#fff; box-shadow:0 10px 24px rgba(7,54,83,.18); }
        .mk-settings-button-gold { border-color:#f59e0b; background:var(--mk-gold); color:#111827; }
        .mk-settings-grid { display:grid; grid-template-columns:minmax(0,1.1fr) minmax(320px,.9fr); gap:1rem; align-items:start; }
        .mk-settings-stack,.mk-settings-form,.mk-settings-list { display:grid; gap:1rem; }
        .mk-settings-head { border-bottom:1px solid #e2e8f0; background:#f8fafc; padding:1.1rem 1.25rem; }
        .mk-settings-body { padding:1.25rem; }
        .mk-settings-head h3 { margin:.45rem 0 0; font-size:1.12rem; font-weight:950; color:#0f172a; }
        .mk-settings-head p { margin:.35rem 0 0; color:#64748b; font-size:.88rem; line-height:1.55; }
        .mk-settings-field label { display:block; color:#111827; font-size:.88rem; font-weight:900; }
        .mk-settings-field input { margin-top:.45rem; width:100%; min-height:2.65rem; border:1px solid #cbd5e1; border-radius:12px; background:#fff; color:#0f172a; padding:.58rem .75rem; font-size:.92rem; font-weight:650; }
        .mk-settings-field input:focus { border-color:#f59e0b; outline:2px solid rgba(255,196,12,.32); }
        .mk-settings-help,.mk-settings-note { color:#64748b; font-size:.8rem; line-height:1.5; }
        .mk-settings-error { display:block; margin-top:.38rem; color:#dc2626; font-size:.78rem; font-weight:800; }
        .mk-settings-summary { display:grid; gap:.75rem; }
        .mk-settings-summary div,.mk-settings-note { border:1px solid #e2e8f0; border-radius:14px; background:#f8fafc; padding:.9rem; }
        .mk-settings-summary dt { color:#64748b; font-size:.75rem; font-weight:900; letter-spacing:.04em; text-transform:uppercase; }
        .mk-settings-summary dd { margin:.35rem 0 0; color:#0f172a; font-size:.95rem; font-weight:900; overflow-wrap:anywhere; }
        .mk-settings-alert { border:1px solid #a7f3d0; border-radius:14px; background:#ecfdf5; color:#047857; padding:.95rem 1rem; font-size:.88rem; font-weight:850; }
        @media (max-width:960px) { .mk-settings-grid { grid-template-columns:1fr; } }
        @media (max-width:640px) { .mk-settings-hero,.mk-settings-head,.mk-settings-body { padding:1rem; } .mk-settings-link,.mk-settings-button { width:100%; } }
        .dark .mk-admin-settings-shell,.fi.dark .mk-admin-settings-shell { --mk-border:#334155; color:#e5e7eb; }
        .dark .mk-settings-card,.fi.dark .mk-settings-card { background:#111827; border-color:#334155; }
        .dark .mk-settings-hero,.fi.dark .mk-settings-hero { background:linear-gradient(135deg,#111827 0%,#0f172a 58%,rgba(255,196,12,.12) 100%); }
        .dark .mk-settings-title,.dark .mk-settings-head h3,.dark .mk-settings-field label,.dark .mk-settings-summary dd,.fi.dark .mk-settings-title,.fi.dark .mk-settings-head h3,.fi.dark .mk-settings-field label,.fi.dark .mk-settings-summary dd { color:#f8fafc; }
        .dark .mk-settings-copy,.dark .mk-settings-head p,.dark .mk-settings-help,.dark .mk-settings-note,.fi.dark .mk-settings-copy,.fi.dark .mk-settings-head p,.fi.dark .mk-settings-help,.fi.dark .mk-settings-note { color:#cbd5e1; }
        .dark .mk-settings-head,.dark .mk-settings-summary div,.dark .mk-settings-note,.fi.dark .mk-settings-head,.fi.dark .mk-settings-summary div,.fi.dark .mk-settings-note { background:#0f172a; border-color:#334155; }
        .dark .mk-settings-field input,.fi.dark .mk-settings-field input { background:#0f172a; border-color:#475569; color:#f8fafc; }
    </style>

    <div class="mk-admin-settings-shell" data-testid="admin-account-settings-shell">
        @if (session('profile_status'))
            <div class="mk-settings-alert">{{ session('profile_status') }}</div>
        @endif
        @if (session('password_status'))
            <div class="mk-settings-alert">{{ session('password_status') }}</div>
        @endif

        <section class="mk-settings-card mk-settings-hero" data-testid="admin-settings-hero">
            <div class="mk-settings-row">
                <div>
                    <span class="mk-settings-kicker">MK Scholars Admin</span>
                    <h2 class="mk-settings-title">Account Settings</h2>
                    <p class="mk-settings-copy">Manage your own administrator identity and password from a protected Filament workspace. Role and account status are shown for context only.</p>
                </div>
                <a href="{{ route('home') }}" class="mk-settings-link">Back to Home</a>
            </div>
        </section>

        <div class="mk-settings-grid">
            <section class="mk-settings-card" data-testid="admin-settings-profile-section">
                <div class="mk-settings-head">
                    <span class="mk-settings-badge">Editable</span>
                    <h3>Profile Information</h3>
                    <p>Update the name and email used for your administrator account.</p>
                </div>
                <div class="mk-settings-body">
                    <form method="POST" action="{{ $profileRoute }}" class="mk-settings-form">
                        @csrf
                        <div class="mk-settings-field">
                            <label for="admin-settings-name">Admin name</label>
                            <input id="admin-settings-name" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name">
                            <span class="mk-settings-help">Shown in the admin panel and account emails.</span>
                            @error('name')<span class="mk-settings-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="mk-settings-field">
                            <label for="admin-settings-email">Admin email</label>
                            <input id="admin-settings-email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                            <span class="mk-settings-help">This page updates only your own administrator account.</span>
                            @error('email')<span class="mk-settings-error">{{ $message }}</span>@enderror
                        </div>
                        <div><button type="submit" class="mk-settings-button mk-settings-button-gold">Save Profile</button></div>
                    </form>
                </div>
            </section>

            <div class="mk-settings-stack">
                <section class="mk-settings-card" data-testid="admin-settings-account-section">
                    <div class="mk-settings-head">
                        <span class="mk-settings-badge">Read only</span>
                        <h3>Account Details</h3>
                        <p>Access level and approval status are displayed but cannot be changed here.</p>
                    </div>
                    <div class="mk-settings-body">
                        <dl class="mk-settings-summary">
                            <div><dt>Role / account type</dt><dd>{{ str($user->role)->headline() }}</dd></div>
                            <div><dt>Account status</dt><dd>{{ str($user->approval_status ?? 'approved')->headline() }}</dd></div>
                            <div><dt>Last updated</dt><dd>{{ $user->updated_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd></div>
                        </dl>
                    </div>
                </section>

                <section class="mk-settings-card" data-testid="admin-settings-security-section">
                    <div class="mk-settings-head">
                        <span class="mk-settings-badge">Protected</span>
                        <h3>Security Notes</h3>
                        <p>Personal settings stay separate from role and approval management.</p>
                    </div>
                    <div class="mk-settings-body mk-settings-list">
                        <div class="mk-settings-note">Password changes require your current password.</div>
                        <div class="mk-settings-note">Role and approval status cannot be changed from personal settings.</div>
                        <div class="mk-settings-note">Password hashes are never displayed on this page.</div>
                    </div>
                </section>
            </div>
        </div>

        <section class="mk-settings-card" data-testid="admin-settings-password-section">
            <div class="mk-settings-head">
                <span class="mk-settings-badge">Secure update</span>
                <h3>Change Password</h3>
                <p>Enter your current password before setting a new confirmed password.</p>
            </div>
            <div class="mk-settings-body">
                <form method="POST" action="{{ $passwordRoute }}" class="mk-settings-form">
                    @csrf
                    <div class="mk-settings-grid">
                        <div class="mk-settings-field">
                            <label for="admin-settings-current-password">Current password</label>
                            <input id="admin-settings-current-password" name="current_password" type="password" required autocomplete="current-password">
                            @error('current_password')<span class="mk-settings-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="mk-settings-field">
                            <label for="admin-settings-password">New password</label>
                            <input id="admin-settings-password" name="password" type="password" required autocomplete="new-password">
                            @error('password')<span class="mk-settings-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="mk-settings-field">
                            <label for="admin-settings-password-confirmation">Confirm new password</label>
                            <input id="admin-settings-password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                        </div>
                    </div>
                    <div><button type="submit" class="mk-settings-button">Update Password</button></div>
                </form>
            </div>
        </section>
    </div>
</x-filament-panels::page>