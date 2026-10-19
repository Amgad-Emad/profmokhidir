// Public pages: Alpine for small UI behaviour and the brand motion script. Livewire is never loaded here.
import Alpine from 'alpinejs';
import siteMenu from './menu';
import lightbox from './lightbox';
import videoFacade from './video-facade';
import './motion';

window.Alpine = Alpine;
Alpine.data('siteMenu', siteMenu);
Alpine.data('lightbox', lightbox);
Alpine.data('videoFacade', videoFacade);
Alpine.start();
