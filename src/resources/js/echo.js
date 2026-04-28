/**
 * Laravel Echo + Reverb WebSocket client
 *
 * Menghubungkan browser ke server Reverb (WebSocket).
 * Ketika WAHA webhook menerima pesan baru, server broadcast event
 * ke sini, dan kita trigger Livewire refresh + sound notification.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
});

/**
 * Subscribe ke channel 'waha-inbox'.
 * Ketika event diterima:
 * 1. Trigger Livewire dispatch → loadInbox() di PHP
 * 2. Dispatch custom DOM event → sound notification di Alpine.js
 */
window.Echo.channel('waha-inbox')
    .listen('.inbox.updated', (event) => {
        // 1. Refresh data via Livewire
        if (window.Livewire) {
            window.Livewire.dispatch('waha-inbox-updated', { chatId: event.chat_id });
        }

        // 2. Trigger sound notification (dihandle Alpine.js di blade)
        window.dispatchEvent(new CustomEvent('waha-new-message', {
            detail: { chatId: event.chat_id },
        }));
    });

// Expose Echo connection state untuk UI indicator
window.Echo.connector.pusher.connection.bind('connected', () => {
    window.dispatchEvent(new CustomEvent('waha-ws-connected'));
});
window.Echo.connector.pusher.connection.bind('disconnected', () => {
    window.dispatchEvent(new CustomEvent('waha-ws-disconnected'));
});
window.Echo.connector.pusher.connection.bind('unavailable', () => {
    window.dispatchEvent(new CustomEvent('waha-ws-disconnected'));
});
