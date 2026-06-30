<footer class="bg-[#062B45] text-white">
    <div class="border-b border-white/10 bg-white/[0.025]">
        <div class="mk-container py-5">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Verified certificates', 'Share proof of completed learning.', 'certificate'],
                    ['Weekly check-ins', 'Student support keeps momentum clear.', 'headset'],
                    ['Live class schedule', 'Join sessions and review recordings.', 'calendar'],
                    ['Progress tracker', 'See lessons, goals, and next steps.', 'chart'],
                ] as $item)
                    <div class="rounded-2xl border border-mk-gold/20 bg-[#FFF6DC] p-4 text-mk-navy shadow-sm transition hover:-translate-y-0.5 hover:shadow-soft">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-mk-gold text-mk-navy shadow-sm shadow-mk-gold/20">
                                <x-public-icon :name="$item[2]" class="h-4 w-4" />
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-mk-navy">{{ $item[0] }}</h3>
                                <p class="mt-1 text-xs leading-5 text-slate-600">{{ $item[1] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mk-container grid gap-7 py-7 md:grid-cols-2 lg:grid-cols-[1.15fr_0.68fr_0.75fr_1fr] lg:items-start">
        <div>
            <x-brand-logo text-class="text-white" tagline-class="text-mk-gold" />
            <p class="mt-4 max-w-md text-sm leading-6 text-slate-200">
                MK Scholars helps students build practical skills, stay coached, and earn verifiable progress through focused online learning.
            </p>
            <form action="mailto:mkscholars250@gmail.com" method="GET" class="mt-5 grid max-w-md gap-2 sm:grid-cols-[1fr_auto]" aria-label="Newsletter signup">
                <label class="sr-only" for="footer-email">Email address</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-mk-navy/55"><x-public-icon name="mail" class="h-4 w-4" /></span>
                    <input id="footer-email" name="subject" type="email" placeholder="Email for updates" class="min-h-10 w-full rounded-lg border border-white/15 bg-white py-2 pl-9 pr-3 text-sm font-semibold text-mk-navy placeholder:text-slate-400 focus:border-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/40">
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-mk-gold px-4 py-2.5 text-sm font-extrabold text-mk-navy transition hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-mk-gold/50 focus:ring-offset-2 focus:ring-offset-[#062B45]">
                    <x-public-icon name="sparkles" class="h-4 w-4" />
                    Subscribe
                </button>
            </form>
            <p class="mt-2 text-xs leading-5 text-slate-400">Stay connected with course launches, learning tips, and student support updates.</p>
        </div>

        <div>
            <p class="text-xs font-extrabold uppercase tracking-wide text-mk-gold">Quick links</p>
            <div class="mt-3 grid gap-2 text-sm text-slate-200">
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('home') }}">Home</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('about') }}">About</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('academies') }}">Academies</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('courses') }}">Courses</a>
            </div>
        </div>

        <div>
            <p class="text-xs font-extrabold uppercase tracking-wide text-mk-gold">Learning</p>
            <div class="mt-3 grid gap-2 text-sm text-slate-200">
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('courses') }}">Coding & Tech</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('courses') }}">Language Academy</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('courses') }}">Test Preparation</a>
                <a class="transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('courses') }}">Career Skills</a>
            </div>
        </div>

        <div>
            <p class="text-xs font-extrabold uppercase tracking-wide text-mk-gold">Contact</p>
            <div class="mt-3 grid gap-2 text-sm text-slate-200">
                <a class="inline-flex items-center gap-2 transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="{{ route('contact') }}"><x-public-icon name="message" class="h-4 w-4 text-mk-gold" />Contact support</a>
                <a class="inline-flex items-center gap-2 transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="tel:+250798611161"><x-public-icon name="phone" class="h-4 w-4 text-mk-gold" />+250798611161</a>
                <a class="inline-flex items-center gap-2 break-words transition hover:text-mk-gold focus:outline-none focus:ring-2 focus:ring-mk-gold/50" href="mailto:mkscholars250@gmail.com"><x-public-icon name="mail" class="h-4 w-4 shrink-0 text-mk-gold" /><span class="break-all">mkscholars250@gmail.com</span></a>
                <span class="inline-flex items-center gap-2"><x-public-icon name="pin" class="h-4 w-4 shrink-0 text-mk-gold" />Kigali, Rwanda - Kicukiro</span>
            </div>
            <div class="mt-5 flex flex-wrap gap-2.5">
                <a href="https://www.instagram.com/accounts/login/?next=%2Fmkscholars_&source=omni_redirect" target="_blank" rel="noopener noreferrer" aria-label="Visit MK Scholars on Instagram" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] text-slate-100 transition hover:border-mk-gold hover:bg-mk-gold hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9Zm4.5 3.6A4.4 4.4 0 1 1 12 16.4 4.4 4.4 0 0 1 12 7.6Zm0 2A2.4 2.4 0 1 0 12 14.4 2.4 2.4 0 0 0 12 9.6Zm4.7-2.5a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z" /></svg>
                </a>
                <a href="https://www.whatsapp.com/channel/0029VbBFZSt8vd1L1hSex31Z" target="_blank" rel="noopener noreferrer" aria-label="Visit MK Scholars on WhatsApp" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] text-slate-100 transition hover:border-mk-gold hover:bg-mk-gold hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.1 2a9.8 9.8 0 0 0-8.4 14.9L2.5 22l5.2-1.3A9.9 9.9 0 1 0 12.1 2Zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12.1 20Zm4.5-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1l-.7.9c-.1.2-.3.2-.5.1a6.5 6.5 0 0 1-3.2-2.8c-.1-.2 0-.4.1-.5l.4-.5c.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.8-1.8c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.2 5 4.4.7.3 1.2.5 1.7.6.7.2 1.3.2 1.8.1.6-.1 1.4-.6 1.6-1.1.2-.6.2-1 .1-1.1-.1-.1-.2-.2-.4-.3Z" /></svg>
                </a>
                <a href="https://www.youtube.com/@mkscholars" target="_blank" rel="noopener noreferrer" aria-label="Visit MK Scholars on YouTube" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] text-slate-100 transition hover:border-mk-gold hover:bg-mk-gold hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.6 4.6 12 4.6 12 4.6s-5.6 0-7.5.5a3 3 0 0 0-2.1 2.1A31 31 0 0 0 2 12a31 31 0 0 0 .4 4.8 3 3 0 0 0 2.1 2.1c1.9.5 7.5.5 7.5.5s5.6 0 7.5-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 22 12a31 31 0 0 0-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z" /></svg>
                </a>
                <a href="https://www.facebook.com/people/MK-Scholars/100069262368212/?sk=following" target="_blank" rel="noopener noreferrer" aria-label="Visit MK Scholars on Facebook" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] text-slate-100 transition hover:border-mk-gold hover:bg-mk-gold hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 8.5V7.2c0-.6.4-.8.9-.8H17V3h-3c-3.1 0-4.2 1.9-4.2 4v1.5H7V12h2.8v9H14v-9h2.8l.5-3.5H14Z" /></svg>
                </a>
                <a href="https://x.com/MkScholars" target="_blank" rel="noopener noreferrer" aria-label="Visit MK Scholars on X" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/15 bg-white/[0.06] text-slate-100 transition hover:border-mk-gold hover:bg-mk-gold hover:text-mk-navy focus:outline-none focus:ring-2 focus:ring-mk-gold/50">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.4 3h3.1l-6.8 7.8 8 10.2h-6.3l-4.9-6.3L4.9 21H1.8l7.3-8.4L1.5 3h6.5l4.4 5.7L17.4 3Zm-1.1 16.2H18L7 4.7H5.2l11.1 14.5Z" /></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="mk-container flex flex-col gap-2 py-3 text-xs text-slate-300 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} MK Scholars. All rights reserved.</p>
            <p class="text-slate-400">Focused learning, coaching, and verified progress.</p>
        </div>
    </div>
</footer>


