<x-layouts.app title="Reset password" description="Set a new password using your one-time code.">
    <section class="bg-[#f4f7fb] py-14">
        <div class="mk-container">
            <div class="mx-auto max-w-xl">
                <x-card highlighted class="border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8">
                    <x-badge tone="blue">Set new password</x-badge>
                    <h1 class="mt-4 text-3xl font-extrabold text-mk-navy">Enter your reset code</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Paste the 6-digit code from your email (or the one an MK Scholars admin gave you) and choose a new password.
                    </p>

                    @if (session('status'))
                        <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.reset.store') }}" class="mt-6 grid gap-5">
                        @csrf
                        <div>
                            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
                            <input class="mk-input mt-2" id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', request('email')) }}" placeholder="you@example.com">
                        </div>

                        <div>
                            <label class="text-sm font-bold text-mk-navy" for="otp">One-time code</label>
                            <input class="mk-input mt-2 text-center text-2xl font-black tracking-widest text-mk-navy" id="otp" name="otp" required maxlength="6" minlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="••••••" autocomplete="one-time-code">
                            <p class="mt-1 text-xs font-semibold text-slate-500">Codes expire 30 minutes after being generated.</p>
                        </div>

                        <div>
                            <label class="text-sm font-bold text-mk-navy" for="password">New password</label>
                            <input class="mk-input mt-2" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
                        </div>

                        <div>
                            <label class="text-sm font-bold text-mk-navy" for="password_confirmation">Confirm password</label>
                            <input class="mk-input mt-2" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                        </div>

                        <x-button type="submit" class="w-full">Update password</x-button>
                    </form>

                    <div class="mt-6 flex flex-col gap-2 border-t border-slate-100 pt-5 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                        <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('password.forgot') }}">← Request a new code</a>
                        <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('login') }}">Back to sign in</a>
                    </div>
                </x-card>
            </div>
        </div>
    </section>
</x-layouts.app>
