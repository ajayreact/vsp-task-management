import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import type { ComponentType } from 'react';
import ReactDOMServer from 'react-dom/server';

type PageModule = { default: ComponentType<Record<string, unknown>> };

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        resolve: (name) => {
            const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx', {
                eager: true,
            });

            return pages[`./Pages/${name}.tsx`];
        },
        setup: ({ App, props }) => <App {...props} />,
    }),
);
