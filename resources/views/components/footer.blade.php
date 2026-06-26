<footer class="bg-mk-navy text-white">
    <div class="mk-container grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-[1.4fr_0.8fr_0.8fr_0.8fr]">
        <div>
            <x-brand-logo text-class="text-white" tagline-class="text-mk-gold" />
            <p class="mt-5 max-w-md text-sm leading-7 text-slate-200">
                MK Scholars helps students learn practical skills, stay coached, and earn verifiable certificates through a focused digital learning platform.
            </p>
            <div class="mt-6 inline-flex rounded-full border border-mk-gold/40 bg-white/5 px-4 py-2 text-xs font-bold uppercase tracking-wide text-mk-gold">
                Premium learning support
            </div>
        </div>

        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-mk-gold">Quick links</p>
            <div class="mt-4 grid gap-3 text-sm text-slate-200">
                <a class="transition hover:text-mk-gold" href="{{ route('home') }}">Home</a>
                <a class="transition hover:text-mk-gold" href="{{ route('academies') }}">Academies</a>
                <a class="transition hover:text-mk-gold" href="{{ route('courses') }}">Courses</a>
                <a class="transition hover:text-mk-gold" href="{{ route('pricing') }}">Pricing</a>
            </div>
        </div>

        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-mk-gold">Learning</p>
            <div class="mt-4 grid gap-3 text-sm text-slate-200">
                <a class="transition hover:text-mk-gold" href="{{ route('courses') }}">Coding & Tech</a>
                <a class="transition hover:text-mk-gold" href="{{ route('courses') }}">Language Academy</a>
                <a class="transition hover:text-mk-gold" href="{{ route('courses') }}">Test Preparation</a>
                <a class="transition hover:text-mk-gold" href="{{ route('courses') }}">Career Skills</a>
            </div>
        </div>

        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-mk-gold">Support</p>
            <div class="mt-4 grid gap-3 text-sm text-slate-200">
                <a class="transition hover:text-mk-gold" href="{{ route('about') }}">About MK Scholars</a>
                <a class="transition hover:text-mk-gold" href="{{ route('contact') }}">Contact</a>
                <a class="transition hover:text-mk-gold" href="{{ route('login') }}">Login</a>
                <a class="transition hover:text-mk-gold" href="{{ route('register') }}">Create account</a>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mk-container flex flex-col gap-3 py-5 text-sm text-slate-300 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} MK Scholars. All rights reserved.</p>
            <p class="text-slate-400">Built for focused learning, coaching, and verified progress.</p>
        </div>
    </div>
</footer>

