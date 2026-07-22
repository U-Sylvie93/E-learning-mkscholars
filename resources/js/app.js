import './bootstrap';
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

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

const initEntranceExamViewer = () => {
    const viewer = document.querySelector('[data-paper-viewer]');

    if (!viewer || viewer.dataset.fileKind !== 'pdf') {
        return;
    }

    const shell = document.querySelector('[data-paper-viewer-shell]');
    const pagesContainer = viewer.querySelector('[data-pdf-pages]');
    const status = document.querySelector('[data-pdf-page-status]');
    const fileUrl = viewer.dataset.fileUrl;

    if (!shell || !pagesContainer || !fileUrl) {
        return;
    }

    let pdfDocument = null;
    let zoom = 1;
    let renderToken = 0;

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const clearPages = () => {
        pagesContainer.innerHTML = '';
    };

    const renderPage = async (pageNumber, token) => {
        const page = await pdfDocument.getPage(pageNumber);

        if (token !== renderToken) {
            return;
        }

        const availableWidth = Math.max(280, Math.min(pagesContainer.clientWidth, 1024));
        const initialViewport = page.getViewport({ scale: 1 });
        const fitScale = availableWidth / initialViewport.width;
        const outputScale = Math.min(window.devicePixelRatio || 1, 2);
        const viewport = page.getViewport({ scale: fitScale * zoom });

        const pageShell = document.createElement('figure');
        pageShell.className = 'mk-pdf-page';
        pageShell.dataset.pageNumber = String(pageNumber);

        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width * outputScale);
        canvas.height = Math.floor(viewport.height * outputScale);
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;

        const caption = document.createElement('figcaption');
        caption.textContent = `Page ${pageNumber}`;

        pageShell.append(canvas, caption);
        pagesContainer.appendChild(pageShell);

        await page.render({
            canvasContext: canvas.getContext('2d'),
            transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null,
            viewport,
        }).promise;
    };

    const renderDocument = async () => {
        if (!pdfDocument) {
            return;
        }

        const token = ++renderToken;
        clearPages();
        setStatus(`${pdfDocument.numPages} page${pdfDocument.numPages === 1 ? '' : 's'}`);

        for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber += 1) {
            await renderPage(pageNumber, token);
        }
    };

    const loadDocument = async () => {
        try {
            pdfDocument = await pdfjsLib.getDocument({ url: fileUrl }).promise;
            await renderDocument();
        } catch (error) {
            clearPages();
            setStatus('Preview unavailable');
            const message = document.createElement('div');
            message.className = 'rounded-lg border border-amber-200 bg-amber-50 p-5 text-center text-sm font-semibold text-amber-900';
            message.textContent = 'Preview is not available for this file type yet.';
            pagesContainer.appendChild(message);
        }
    };

    document.querySelector('[data-pdf-dark]')?.addEventListener('click', () => {
        shell.classList.toggle('mk-paper-viewer-dark');
    });

    document.querySelector('[data-pdf-zoom-in]')?.addEventListener('click', async () => {
        zoom = Math.min(1.8, Number((zoom + 0.1).toFixed(2)));
        await renderDocument();
    });

    document.querySelector('[data-pdf-zoom-out]')?.addEventListener('click', async () => {
        zoom = Math.max(0.75, Number((zoom - 0.1).toFixed(2)));
        await renderDocument();
    });

    document.querySelector('[data-pdf-zoom-reset]')?.addEventListener('click', async () => {
        zoom = 1;
        await renderDocument();
    });

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(renderDocument, 180);
    });

    loadDocument();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEntranceExamViewer);
} else {
    initEntranceExamViewer();
}
