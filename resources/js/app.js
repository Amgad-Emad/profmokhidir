// Public pages: Alpine for small UI behaviour and the brand motion script. Livewire is never loaded here.
import Alpine from 'alpinejs';
import masthead from './masthead';
import siteSearch from './search';
import lightbox from './lightbox';
import videoFacade from './video-facade';
import { catalogueSheet, citePanel } from './catalogue';
import { essayContents, readingProgress } from './essay';
import { authChallenge, authForm } from './auth';
import { initForms, registerForms } from './forms';
import './motion';
import './theme';

// First-party analytics (App\Constants\Analytics): fetched only where the server printed its
// settings, and only once the page has loaded, painted and gone idle, so it never competes with it.
if (document.querySelector('meta[name="analytics"]')) {
    const idle = (fn) => ('requestIdleCallback' in window ? window.requestIdleCallback(fn, { timeout: 4000 }) : window.setTimeout(fn, 1500));
    const painted = (fn) => {
        if (!('PerformanceObserver' in window) || !PerformanceObserver.supportedEntryTypes?.includes('paint')) return window.setTimeout(fn, 1500);
        new PerformanceObserver((list, observer) => {
            if (list.getEntriesByName('first-contentful-paint').length === 0) return;
            observer.disconnect();
            fn();
        }).observe({ type: 'paint', buffered: true });
    };
    const loadAnalytics = () => painted(() => idle(() => import('./analytics')));
    if (document.readyState === 'complete') loadAnalytics();
    else window.addEventListener('load', loadAnalytics, { once: true });
}

window.Alpine = Alpine;
Alpine.data('masthead', masthead);
Alpine.data('siteSearch', siteSearch);
Alpine.data('lightbox', lightbox);
Alpine.data('videoFacade', videoFacade);
Alpine.data('catalogueSheet', catalogueSheet);
Alpine.data('citePanel', citePanel);
Alpine.data('essayContents', essayContents);
Alpine.data('readingProgress', readingProgress);
Alpine.data('authForm', authForm);
Alpine.data('authChallenge', authChallenge);
registerForms(Alpine);
initForms();
Alpine.start();
