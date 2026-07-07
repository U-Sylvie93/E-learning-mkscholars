<x-filament-panels::page>
    <div class="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_1fr]">
        @if (session('profile_status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 lg:col-span-2">{{ session('profile_status') }}</div>
        @endif

        @if (session('password_status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 lg:col-span-2">{{ session('password_status') }}</div>
        @endif

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-wide text-amber-500">Admin profile</p>
            <h2 class="mt-2 text-2xl font-black text-gray-950 dark:text-white">Account Settings</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Update your own admin name and email. Role and approval status are shown for awareness only.</p>

            <dl class="mt-5 grid gap-3 rounded-xl bg-gray-50 p-4 text-sm dark:bg-gray-800">
                <div class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-gray-500 dark:text-gray-400">Role</dt>
                    <dd class="font-bold text-gray-950 dark:text-white">{{ str($user->role)->headline() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="font-bold text-gray-950 dark:text-white">{{ str($user->approval_status ?? 'approved')->headline() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="font-semibold text-gray-500 dark:text-gray-400">Last updated</dt>
                    <dd class="font-bold text-gray-950 dark:text-white">{{ $user->updated_at?->format('M j, Y g:i A') ?? 'N/A' }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ $profileRoute }}" class="mt-6 space-y-4">
                @csrf
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Name
                    <input name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('name')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Email
                    <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('email')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>
                <button type="submit" class="inline-flex rounded-lg bg-amber-400 px-4 py-2 text-sm font-bold text-gray-950 transition hover:bg-amber-300">Save Profile</button>
            </form>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-black uppercase tracking-wide text-amber-500">Security</p>
            <h2 class="mt-2 text-2xl font-black text-gray-950 dark:text-white">Change Password</h2>
            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">Enter your current password before setting a new password. Your role cannot be changed here.</p>

            <form method="POST" action="{{ $passwordRoute }}" class="mt-6 space-y-4">
                @csrf
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Current password
                    <input name="current_password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('current_password')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    New password
                    <input name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('password')<span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-sm font-bold text-gray-950 dark:text-white">
                    Confirm new password
                    <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
                <button type="submit" class="inline-flex rounded-lg bg-gray-950 px-4 py-2 text-sm font-bold text-white transition hover:bg-gray-800 dark:bg-amber-400 dark:text-gray-950 dark:hover:bg-amber-300">Change Password</button>
            </form>
        </section>
    </div>
</x-filament-panels::page>
