import "./bootstrap";
import "@fontsource-variable/inter";

import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import { Root } from "./root";

const appName = import.meta.env.VITE_APP_NAME || "Sale CRM";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob("./Pages/**/*.jsx")
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <Root>
                <App {...props} />
            </Root>
        );
    },
    progress: { color: "#fbbf24", showSpinner: false, includeCSS: true },
});
