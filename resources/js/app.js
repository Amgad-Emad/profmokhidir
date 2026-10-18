// Public pages: Alpine for small UI behaviour and the brand motion script. Livewire is never loaded here.
import Alpine from 'alpinejs';
import siteMenu from './menu';
import './motion';

window.Alpine = Alpine;
Alpine.data('siteMenu', siteMenu);
Alpine.start();
