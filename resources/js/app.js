/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

// Passkeys for the admin login, used by the views of spatie/laravel-passkeys
import { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';
window.browserSupportsWebAuthn = browserSupportsWebAuthn;
window.startAuthentication = startAuthentication;
window.startRegistration = startRegistration;

// Fonts of the agenda cards, served by the app itself so visitors' browsers do not contact Google
import '@fontsource/source-sans-3/400.css';
import '@fontsource/source-sans-3/600.css';
import '@fontsource/source-sans-3/700-italic.css';
import '@fontsource/saira-semi-condensed/800.css';

// Makes the site installable as a web app on phones, see public/sw.js
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

// Keep the browser's install prompt for the "Install app" button on the homepage, it can fire before that button exists
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    window.deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('install-available'));
});
