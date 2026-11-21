import './bootstrap';
import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const defaultTitle = 'Andik Laundry - Tempat Laundry Modern di Hendrosari, Gresik';
const appName = 'Andik Laundry';

createInertiaApp({
    title: (title) => {
        if (!title || title === defaultTitle || title === 'Andik Laundry') {
            return defaultTitle;
        }

        const clean = title
            .replace(/\s*-\s*LMS Andik Laundry/gi, '')
            .replace(/\s*-\s*Andik Laundry/gi, '')
            .replace(/\s*\|\s*Andik Laundry/gi, '')
            .trim();

        if (!clean || clean === 'Andik Laundry') {
            return defaultTitle;
        }

        return `${clean} - ${appName}`;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue')
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#1F7FC4',
        showSpinner: true,
    },
});
