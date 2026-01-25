import axios from 'axios';

declare global {
    interface Window {
        axios: typeof axios;
    }
    // Ziggy route helper
    var route: (name?: string, params?: any, absolute?: boolean) => string;
}
