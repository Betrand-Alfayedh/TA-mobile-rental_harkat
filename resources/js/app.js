import './bootstrap';
import './echo';
import './booking';
import Alpine from 'alpinejs';
import lucide from 'lucide/dist/umd/lucide.js';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener("DOMContentLoaded", () => {
    lucide.createIcons();
});
