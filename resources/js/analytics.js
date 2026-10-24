/*
 | First-party, cookieless analytics (App\Constants\Analytics). Loaded only on
 | public pages, only while analytics is switched on, never for signed-in
 | staff. It sets no cookie and stores nothing in the browser.
 |
 | One page view is sent when the page is shown. Engaged time counts while the
 | tab is visible and the visitor has scrolled or interacted within the idle
 | window; it is sent, with the deepest scroll mark reached (25/50/75/100%),
 | whenever the tab is hidden or the page is left. Browsers that ask not to be
 | tracked (Do Not Track, Global Privacy Control) and automated browsers send
 | nothing.
 */
const meta = document.querySelector('meta[name="analytics"]');

// Analytics::IDLE_SECONDS and Analytics::SCROLL_DEPTHS (AnalyticsScriptTest keeps them in step).
// The meta tag carries only the endpoint, to keep the public pages' HTML as small as it was.
const IDLE_SECONDS = 30;
const SCROLL_DEPTHS = [25, 50, 75, 100];

function optedOut() {
    return navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl === true || navigator.webdriver === true;
}

function randomId() {
    if (crypto.randomUUID) return crypto.randomUUID();
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function start(endpoint) {
    const idleMs = IDLE_SECONDS * 1000;
    const depths = SCROLL_DEPTHS;
    let view;
    let engagedMs;
    let depth;
    let sent;
    let lastActive;
    let lastTick;
    let timer = null;

    // text/plain keeps sendBeacon a simple request; the server reads the JSON body.
    const send = (payload) => navigator.sendBeacon(endpoint, new Blob([JSON.stringify({ ...payload, v: view, sw: screen.width })], { type: 'text/plain' }));

    const measureDepth = () => {
        const page = document.documentElement.scrollHeight;
        const seen = page > 0 ? ((window.scrollY + window.innerHeight) / page) * 100 : 100;
        for (const mark of depths) {
            if (seen >= mark - 0.5 && mark > depth) depth = mark;
        }
    };

    const tick = () => {
        const now = performance.now();
        if (document.visibilityState === 'visible' && now - lastActive < idleMs) {
            engagedMs += Math.min(now - lastTick, 1500);
        }
        lastTick = now;
    };

    const run = () => {
        if (timer === null && document.visibilityState === 'visible') {
            lastTick = performance.now();
            timer = window.setInterval(tick, 1000);
        }
    };

    const pause = () => {
        if (timer !== null) {
            tick();
            window.clearInterval(timer);
            timer = null;
        }
    };

    const flush = () => {
        const seconds = Math.round(engagedMs / 1000);
        if (seconds === sent.seconds && depth === sent.depth) return;
        sent = { seconds, depth };
        send({ t: 'engagement', e: seconds, s: depth });
    };

    const begin = (restored = false) => {
        view = randomId();
        // The script starts after the page has loaded and gone idle; a visible page was being read meanwhile.
        engagedMs = restored || document.visibilityState !== 'visible' ? 0 : Math.min(performance.now(), idleMs);
        depth = 0;
        sent = { seconds: 0, depth: 0 };
        lastActive = performance.now();
        const utm = new URLSearchParams(location.search).get('utm_source');
        send({ t: 'pageview', p: location.pathname, ti: document.title, r: document.referrer, u: utm });
        measureDepth();
        run();
    };

    const active = () => {
        lastActive = performance.now();
    };

    ['pointerdown', 'keydown', 'mousemove', 'touchstart', 'wheel'].forEach((type) => window.addEventListener(type, active, { passive: true }));
    window.addEventListener('scroll', () => {
        active();
        measureDepth();
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            pause();
            flush();
        } else {
            active();
            run();
        }
    });
    window.addEventListener('pagehide', () => {
        pause();
        flush();
    });
    // A page restored from the back/forward cache is a new view.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) begin(true);
    });

    begin();
}

// app.js imports this module only after the page has loaded.
if (meta && navigator.sendBeacon && window.crypto && !optedOut()) {
    try {
        start(meta.content);
    } catch (error) {
        // Analytics must never break the page.
    }
}
