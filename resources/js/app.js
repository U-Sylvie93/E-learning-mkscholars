import './bootstrap';

const animateCounters = () => {
    const counters = document.querySelectorAll('[data-testid="animated-counter"][data-counter-target]');

    if (!counters.length) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const setFinalValue = (counter) => {
        const target = Number.parseInt(counter.dataset.counterTarget || counter.textContent || '0', 10);
        counter.textContent = Number.isFinite(target) ? target.toLocaleString() : counter.textContent;
    };

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        counters.forEach(setFinalValue);
        return;
    }

    const animate = (counter) => {
        if (counter.dataset.counterAnimated === 'true') {
            return;
        }

        counter.dataset.counterAnimated = 'true';

        const target = Number.parseInt(counter.dataset.counterTarget || '0', 10);

        if (!Number.isFinite(target) || target <= 0) {
            setFinalValue(counter);
            return;
        }

        const duration = 900;
        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            counter.textContent = Math.round(target * eased).toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                setFinalValue(counter);
            }
        };

        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animate(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.35 });

    counters.forEach((counter) => observer.observe(counter));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', animateCounters);
} else {
    animateCounters();
}
