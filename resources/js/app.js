// Public pages: Alpine for small UI behaviour and the brand motion script. Livewire is never loaded here.
import Alpine from 'alpinejs';
import masthead from './masthead';
import siteSearch from './search';
import lightbox from './lightbox';
import videoFacade from './video-facade';
import { catalogueSheet, citePanel } from './catalogue';
import { essayContents, readingProgress } from './essay';
import './motion';

window.Alpine = Alpine;
Alpine.data('masthead', masthead);
Alpine.data('siteSearch', siteSearch);
Alpine.data('lightbox', lightbox);
Alpine.data('videoFacade', videoFacade);
Alpine.data('catalogueSheet', catalogueSheet);
Alpine.data('citePanel', citePanel);
Alpine.data('essayContents', essayContents);
Alpine.data('readingProgress', readingProgress);
Alpine.start();
