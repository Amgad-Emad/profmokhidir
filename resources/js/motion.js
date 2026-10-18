/*
 | Brand motion. No animation library: CSS transitions carry the timing
 | (see the "motion" section of resources/css/app.css); this file only
 | observes the viewport and toggles classes or writes a scroll progress.
 |
 |   [data-rise]           fade in and rise 40px over 1200ms when in view
 |   [data-rise-group]     children rise one after another, 120ms apart
 |   [data-rule]           brass rule draws left to right over 1600ms
 |   [data-scroll-scale]   scales 1.0 -> 0.7 and fades slightly as it scrolls out
 |
 | Everything is skipped under prefers-reduced-motion: content shows at once.
 */

const STAGGER_MS = 120;
const SCALE_FROM = 1;
const SCALE_TO = 0.7;
const FADE_TO = 0.6;

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

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                el.classList.add(el.hasAttribute('data-rule') ? 'is-drawn' : 'is-visible');
                observer.unobserve(el);
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
    );

    root.querySelectorAll('[data-rise], [data-rise-group], [data-rule]').forEach((el) => observer.observe(el));

    return () => observer.disconnect();
}

function setupScrollScale(root) {
    const targets = Array.from(root.querySelectorAll('[data-scroll-scale]'));
    if (targets.length === 0) return () => {};

    let ticking = false;

    const update = () => {
        ticking = false;
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

    const onScroll = () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(update);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    update();

    return () => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('resize', onScroll);
        targets.forEach((el) => {
            el.style.removeProperty('transform');
            el.style.removeProperty('opacity');
        });
    };
}

export function initMotion(root = document) {
    let teardown = [];

    const start = () => {
        if (reducedMotion.matches) {
            revealEverything(root);
            return;
        }
        teardown = [setupRise(root), setupScrollScale(root)];
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
