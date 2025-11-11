import './bootstrap';
import Alpine from 'alpinejs';
import dashboard from './dashboard.js';

window.Alpine = Alpine;
Alpine.data('dashboard', dashboard);

Alpine.data('app', (pageData) => ({    // Initializer
    init() {
        // Merge dashboard data if it exists for the current page
        if (pageData) {
            Object.assign(this, dashboard(pageData.generateUrl, pageData.modelsUrl, pageData.availableApis));
        }
    }
}));

Alpine.start();
