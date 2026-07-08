<x-filament-panels::page>
    <div class="mx-auto max-w-6xl space-y-6">
        @if (session('profile_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm dark:border-emerald-800/70 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('profile_status') }}</div>
        @endif

        @if (session('password_status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm dark:border-emerald-800/70 dark:bg-emerald-950/40 dark:text-emerald-200">{{ session('password_status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-amber-200 bg-gradient-to-r from-amber-50 via-white to-slate-50 p-6 shadow-sm dark:border-amber-900/50 dark:from-amber-950/20 dark:via-gray-900 dark:to-slate-950">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-amber-600 dark:text-amber-300">MK Scholars Admin</p>
                    <h2 class="mt-2 text-2xl font-black text-gray-950 dark:text-white">Account Settings</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">Manage your own administrator profile and password. Role and account status are read-only on this page.</p>
                </div>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-lg bg-gray-950 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-gray-800 dark:bg-amber-400 dark:text-gray-950 dark:hover:bg-amber-300">
                    Back to Home
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-amber-500">Profile Information</p>
                        <h3 class="mt-2 text-xl font-black text-gray-950 dark:text-white">Personal details</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Update the name and email used for your administrator account.</p>
                    </div>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-400/10 dark:text-amber-200">Editable</span>
                </div>

                <form method="POST" action="{{ $profileRoute }}" class="mt-6 space-y-5">
                    @csrf
                    <label class="block text-sm font-bold text-gray-950 dark:text-white">
                        Admin name
                        <input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <span class="mt-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Shown in the admin panel and account emails.</span>
                        @error('name')<span class="mt-1 block text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-sm font-bold text-gray-950 dark:text-white">
                        Admin email
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="email" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <span class="mt-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Must be unique and valid. This page updates only your own account.</span>
                        @error('email')<span class="mt-1 block text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-gray-950 shadow-sm transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 dark:focus:ring-offset-gray-900">Save Profile</button>
                </form>
            </section>

            <div class="space-y-6">
                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-500">Account Details</p>
                    <h3 class="mt-2 text-xl font-black text-gray-950 dark:text-white">Read-only access summary</h3>
                    <dl class="mt-5 grid gap-3 text-sm">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400">Role / account type</dt>
                            <dd class="mt-1 font-bold text-gray-950 dark:text-white">{{ str($user->role)->headline() }}</dd>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400">Account status</dt>
                            <dd class="mt-1 font-bold text-gray-950 dark:text-white">{{ str($user->approval_status ?? 'approved')->headline() }}</dd>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400">Last updated</dt>
                            <dd class="mt-1 font-bold text-gray-950 dark:text-white">{{ $user->updated_at?->format('M j, Y g:i A') ?? 'Not available' }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-black uppercase tracking-wide text-amber-500">Security Notes</p>
                    <h3 class="mt-2 text-xl font-black text-gray-950 dark:text-white">Protected changes</h3>
                    <ul class="mt-4 space-y-3 text-sm font-medium leading-6 text-gray-600 dark:text-gray-300">
                        <li class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">Password changes require your current password.</li>
                        <li class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">Role and approval status cannot be changed from personal settings.</li>
                        <li class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">Password hashes are never displayed on this page.</li>
                    </ul>
                </section>
            </div>
        </div>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="max-w-3xl">
                <p class="text-xs font-black uppercase tracking-wide text-amber-500">Change Password</p>
                <h3 class="mt-2 text-xl font-black text-gray-950 dark:text-white">Update your sign-in password</h3>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Enter your current password before setting a new confirmed password.</p>
            </div>

            <form method="POST" action="{{ $passwordRoute }}" class="mt-6 grid gap-5 lg:grid-cols-3">
                @csrf
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Current password
                    <input name="current_password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('current_password')<span class="mt-1 block text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    New password
                    <input name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('password')<span class="mt-1 block text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Confirm new password
                    <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
                <div class="lg:col-span-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-gray-950 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 dark:bg-amber-400 dark:text-gray-950 dark:hover:bg-amber-300 dark:focus:ring-amber-300 dark:focus:ring-offset-gray-900">Update Password</button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
