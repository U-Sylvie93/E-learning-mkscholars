@php
    $roleLabel = str($role)->headline()->toString();
    $approvalTone = match ($user->approval_status) {
        \App\Models\User::APPROVAL_APPROVED => 'green',
        \App\Models\User::APPROVAL_PENDING => 'gold',
        default => 'gray',
    };
@endphp

<x-dashboard-layout :role="$role" title="Settings" description="Manage your MK Scholars profile and password.">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-mk-gold">{{ $roleLabel }} account</p>
                <h2 class="mt-2 text-2xl font-extrabold text-mk-navy sm:text-3xl">Profile settings</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Keep your visible profile name and account password up to date. Your email and role are protected for account safety.</p>
            </div>
            <x-button :href="route($dashboardRoute)" variant="secondary">Back to Dashboard</x-button>
        </div>

        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <x-badge tone="blue">{{ $roleLabel }}</x-badge>
                        <h3 class="mt-4 text-xl font-extrabold text-mk-navy">{{ $user->name }}</h3>
                        <p class="mt-1 break-all text-sm font-semibold text-slate-600">{{ $user->email }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-mk-navy text-lg font-extrabold text-mk-gold">
                        {{ str($user->name)->substr(0, 1)->upper() }}
                    </div>
                </div>

                <dl class="mt-6 grid gap-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Role</dt>
                        <dd class="mt-1 font-bold text-mk-navy">{{ $roleLabel }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Approval status</dt>
                        <dd class="mt-2"><x-badge :tone="$approvalTone">{{ str($user->approval_status ?? 'approved')->headline() }}</x-badge></dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Account created</dt>
                        <dd class="mt-1 font-bold text-mk-navy">{{ $user->created_at?->format('M j, Y') ?? 'N/A' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 rounded-lg border border-mk-gold/40 bg-mk-goldSoft p-4 text-sm leading-6 text-mk-navy">
                    Email changes are not enabled in this phase. Contact MK Scholars support if your account email needs to be corrected.
                </div>
            </x-card>

            <div class="space-y-6">
                <x-card>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-xl font-extrabold text-mk-navy">Profile</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Update the name shown in your dashboard and records.</p>
                        </div>
                        @if (session('profile_status'))
                            <x-badge tone="green">{{ session('profile_status') }}</x-badge>
                        @endif
                    </div>

                    <form method="POST" action="{{ route($profileRoute) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="name" class="text-sm font-bold text-mk-navy">Name</label>
                            <input id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-mk-navy shadow-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            @error('name')
                                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="text-sm font-bold text-mk-navy">Email</label>
                            <input id="email" value="{{ $user->email }}" disabled class="mt-2 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-500 shadow-sm">
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-mk-gold px-5 py-3 text-sm font-bold text-mk-navy shadow-sm transition hover:bg-yellow-300 sm:w-auto">Save Profile</button>
                    </form>
                </x-card>

                <x-card>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-xl font-extrabold text-mk-navy">Password</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Use a strong password you do not use on other websites.</p>
                        </div>
                        @if (session('password_status'))
                            <x-badge tone="green">{{ session('password_status') }}</x-badge>
                        @endif
                    </div>

                    <form method="POST" action="{{ route($passwordRoute) }}" class="mt-6 space-y-5">
                        @csrf
                        <div>
                            <label for="current_password" class="text-sm font-bold text-mk-navy">Current password</label>
                            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-mk-navy shadow-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            @error('current_password')
                                <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="text-sm font-bold text-mk-navy">New password</label>
                                <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-mk-navy shadow-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                                @error('password')
                                    <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="text-sm font-bold text-mk-navy">Confirm password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm font-semibold text-mk-navy shadow-sm focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/30">
                            </div>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-mk-navy px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-mk-blue sm:w-auto">Update Password</button>
                    </form>
                </x-card>
            </div>
        </div>
    </div>
</x-dashboard-layout>
