/*
 | Brand motion. No animation library: CSS transitions carry the timing
 | (see the "motion" section of resources/css/app.css); this file only
 | observes the viewport and toggles classes or writes a scroll progress.
 |
 |   [data-rise]           fade in and rise 40px over 1200ms when in view
 |   [data-rise-group]     children rise one after another, 120ms apart
 |   [data-rule]           brass rule draws left to right over 1600ms
 |   [data-scroll-scale]   scales 1.0 -> 0.7 and fades slightly as it scrolls out
 |   [data-scroll-line]    writes --line-progress (0 -> 1) as the element passes
 |                         the reading line, so a hairline draws with the scroll
 |                         (and stays drawn when scrolling back up)
 |
 | Everything is skipped under prefers-reduced-motion: content shows at once.
 */

const STAGGER_MS = 120;
const SCALE_FROM = 1;
const SCALE_TO = 0.7;
const FADE_TO = 0.6;
// The hairline is drawn down to this fraction of the viewport height.
const LINE_REACH = 0.75;

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

function revealEverything(root) {
    root.querySelectorAll('[data-rise], [data-rise-group], [data-rise-group] > *').forEach((el) => {
        el.classList.add('is-visible');
        el.style.removeProperty('--rise-delay');
    });
    root.querySelectorAll('[data-rule]').forEach((el) => el.classList.add('is-drawn'));
    root.querySelectorAll('[data-scroll-scale]').forEach((el) => {
        el.style.removeProperty('transform');
        el.style.removeProperty('opacity');
    });
    root.querySelectorAll('[data-scroll-line]').forEach((el) => el.style.removeProperty('--line-progress'));
}

// Run `update` once now and then at most once a frame while scrolling or resizing.
function onScrollFrame(update) {
    let ticking = false;

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(() => {
            ticking = false;
            update();
        });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();

    return () => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
    };
}

function setupRise(root) {
    if (!('IntersectionObserver' in window)) {
        revealEverything(root);
        return () => {};
    }

    // Stagger children of each group by 120ms.
    root.querySelectorAll('[data-rise-group]').forEach((group) => {
        Array.from(group.children).forEach((child, index) => {
            child.style.setProperty('--rise-delay', `${index * STAGGER_MS}ms`);
        });
    });

    const pending = new Set();
    const reveal = (el) => {
        el.classList.add(el.hasAttribute('data-rule') ? 'is-drawn' : 'is-visible');
        pending.delete(el);
        observer.unobserve(el);
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) reveal(entry.target);
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
    );

    root.querySelectorAll('[data-rise], [data-rise-group], [data-rule]').forEach((el) => {
        pending.add(el);
        observer.observe(el);
    });

    // The bottom margin never admits an element that sits in the last 8% of the page (a seal on a short
    // page, the end of a long one), so once the page cannot scroll further everything left is revealed.
    const atEnd = () => window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 1;
    const revealRest = () => {
        if (!atEnd()) return;
        pending.forEach(reveal);
        window.removeEventListener('scroll', revealRest);
    };
    window.addEventListener('scroll', revealRest, { passive: true });
    requestAnimationFrame(revealRest);

    return () => {
        observer.disconnect();
        window.removeEventListener('scroll', revealRest);
    };
}

function setupScrollScale(root) {
    const targets = Array.from(root.querySelectorAll('[data-scroll-scale]'));
    if (targets.length === 0) return () => {};

    const update = () => {
        const scrollY = window.scrollY || window.pageYOffset;

        targets.forEach((el) => {
            // Progress 0 at the top of the page, 1 once the element's bottom edge has scrolled past the top.
            const rect = el.getBoundingClientRect();
            const bottomFromPageTop = rect.bottom + scrollY;
            const progress = bottomFromPageTop > 0 ? Math.min(1, Math.max(0, scrollY / bottomFromPageTop)) : 0;

            const scale = SCALE_FROM + (SCALE_TO - SCALE_FROM) * progress;
            const opacity = 1 + (FADE_TO - 1) * progress;

            el.style.transform = `scale(${scale.toFixed(4)})`;
            el.style.opacity = opacity.toFixed(3);
        });
    };

    const stop = onScrollFrame(update);

    return () => {
        stop();
        targets.forEach((el) => {
            el.style.removeProperty('transform');
            el.style.removeProperty('opacity');
        });
    };
}

function setupScrollLines(root) {
    const lines = Array.from(root.querySelectorAll('[data-scroll-line]'));
    if (lines.length === 0) return () => {};

    // Furthest progress per line: a drawn line stays drawn when the reader scrolls back up.
    const drawn = new WeakMap();

    const update = () => {
        const reach = window.innerHeight * LINE_REACH;

        lines.forEach((el) => {
            // 0 while the element's top is below the reading line, 1 once its bottom has passed it.
            const rect = el.getBoundingClientRect();
            const progress = rect.height > 0 ? Math.min(1, Math.max(0, (reach - rect.top) / rect.height)) : 1;
            const furthest = Math.max(drawn.get(el) ?? 0, progress);

            drawn.set(el, furthest);
            el.style.setProperty('--line-progress', furthest.toFixed(4));
        });
    };

    const stop = onScrollFrame(update);

    return () => {
        stop();
        lines.forEach((el) => el.style.removeProperty('--line-progress'));
    };
}

export function initMotion(root = document) {
    let teardown = [];

    const start = () => {
        if (reducedMotion.matches) {
            revealEverything(root);
            return;
        }
        teardown = [setupRise(root), setupScrollScale(root), setupScrollLines(root)];
    };

    const stop = () => {
        teardown.forEach((fn) => fn());
        teardown = [];
    };

    start();

    // If the user changes the OS setting while the page is open, follow it.
    reducedMotion.addEventListener('change', () => {
        stop();
        start();
    });

    return stop;
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initMotion());
} else {
    initMotion();
}
