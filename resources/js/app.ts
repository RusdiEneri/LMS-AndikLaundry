import './bootstrap';
import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Andik Laundry';

createInertiaApp({
    title: (title) => {
        if (!title) {
            return 'Andik Laundry';
        }

        const clean = title
            .replace(/\s*-\s*LMS Andik Laundry/gi, '')
            .replace(/\s*-\s*Andik Laundry/gi, '')
            .replace(/\s*\|\s*Andik Laundry/gi, '')
            .trim();

        if (!clean || clean === 'Andik Laundry') {
            return 'Andik Laundry';
        }

        return `${clean} - Andik Laundry`;
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
