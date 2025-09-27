import React from 'react';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './contexts/ThemeContext';

const appName = import.meta.env.VITE_APP_NAME || 'Vibe Kanban';

createInertiaApp({
    title: (title) => {
        // Use shared SEO meta from props if available
        if (props.initialPage?.props?.seo?.meta?.title) {
            return title 
                ? `${title} | ${props.initialPage.props.seo.meta.title}` 
                : props.initialPage.props.seo.meta.title;
        }
        return title ? `${title} | ${appName}` : appName;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.jsx`,
            import.meta.glob('./pages/**/*.jsx', { eager: false })
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);
        
        root.render(
            <ThemeProvider>
                <App {...props} />
            </ThemeProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});