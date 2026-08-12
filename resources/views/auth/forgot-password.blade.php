<x-layouts.app title="Forgot password" description="Reset your MK Scholars account password.">
    <section class="bg-[#f4f7fb] py-14">
        <div class="mk-container">
            <div class="mx-auto max-w-xl">
                <x-card highlighted class="border-white/80 bg-white/95 p-6 shadow-2xl shadow-mk-navy/10 sm:p-8">
                    <x-badge tone="blue">Account recovery</x-badge>
                    <h1 class="mt-4 text-3xl font-extrabold text-mk-navy">Forgot your password?</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Enter the email on your MK Scholars account. We'll generate a one-time 6-digit code and email it to you.
                        If you don't receive it, an MK Scholars admin can share the code with you.
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

                    <form method="POST" action="{{ route('password.forgot.store') }}" class="mt-6 grid gap-5">
                        @csrf
                        <div>
                            <label class="text-sm font-bold text-mk-navy" for="email">Email address</label>
                            <input class="mk-input mt-2" id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" placeholder="you@example.com">
                        </div>
                        <x-button type="submit" class="w-full">Send reset code</x-button>
                    </form>

                    <div class="mt-6 flex flex-col gap-2 border-t border-slate-100 pt-5 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                        <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('login') }}">← Back to sign in</a>
                        <a class="font-bold text-mk-navy hover:text-mk-blue" href="{{ route('password.reset') }}">I already have a code</a>
                    </div>
                </x-card>
            </div>
        </div>
    </section>
</x-layouts.app>
