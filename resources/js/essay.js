/*
 | Commentary reading layout (Alpine components):
 |
 |   essayContents     the table of contents marks the part being read (aria-current)
 |                     as its heading passes the reading line.
 |   readingProgress   a slim brass line under the header grows with the reader's
 |                     progress through the essay. It follows the scroll directly (no
 |                     transition), so it reads the same with reduced motion.
 */

// The reading line sits a third of the way down the viewport.
const READING_LINE = 0.33;

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
}

export function essayContents() {
    return {
        current: null,

        init() {
            const ids = Array.from(this.$el.querySelectorAll('a[href^="#"]')).map((a) => a.getAttribute('href').slice(1));
            const headings = ids.map((id) => document.getElementById(id)).filter(Boolean);
            if (headings.length === 0) return;

            onScrollFrame(() => {
                const line = window.innerHeight * READING_LINE;
                let current = null;
                headings.forEach((heading) => {
                    if (heading.getBoundingClientRect().top <= line) current = heading.id;
                });
                this.current = current;
            });
        },

        isCurrent(id) {
            return this.current === id;
        },
    };
}

export function readingProgress() {
    return {
        init() {
            const target = document.querySelector(this.$el.dataset.target);
            if (!target) return;

            onScrollFrame(() => {
                // 0 with the essay's top at the top of the viewport, 1 with its end at the bottom.
                const rect = target.getBoundingClientRect();
                const travel = rect.height - window.innerHeight;
                const progress = travel > 0 ? -rect.top / travel : rect.bottom <= window.innerHeight ? 1 : 0;
                this.$el.style.setProperty('--reading-progress', Math.min(1, Math.max(0, progress)).toFixed(4));
            });
        },
    };
}
