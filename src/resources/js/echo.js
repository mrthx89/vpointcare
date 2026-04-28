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
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                console.log('[WS-Auth] Authorizing channel:', channel.name, 'socket:', socketId, 'csrf:', csrfToken ? 'found' : 'MISSING');
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name
                    })
                })
                    .then(response => {
                        console.log('[WS-Auth] Response status:', response.status);
                        if (!response.ok) throw new Error('Auth failed: ' + response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('[WS-Auth] Auth success:', data);
                        callback(false, data);
                    })
                    .catch(error => {
                        console.error('[WS-Auth] Auth error:', error);
                        callback(true, error);
                    });
            }
        };
    }
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

// Presence channel untuk tracking Agent/CS aktif secara unik berdasarkan ID
window.Echo.join('waha-agents')
    .here((users) => {
        window.wahaActiveUsers = users || [];
        window.dispatchEvent(new CustomEvent('waha-agents-updated', { detail: { count: window.wahaActiveUsers.length } }));
    })
    .joining((user) => {
        if (!window.wahaActiveUsers) window.wahaActiveUsers = [];
        // Pastikan tidak ada duplikat ID jika user login di banyak tab
        if (!window.wahaActiveUsers.find(u => u.id === user.id)) {
            window.wahaActiveUsers.push(user);
        }
        window.dispatchEvent(new CustomEvent('waha-agents-updated', { detail: { count: window.wahaActiveUsers.length } }));
    })
    .leaving((user) => {
        if (!window.wahaActiveUsers) window.wahaActiveUsers = [];
        // Hapus user dari array saat mereka benar-benar keluar dari semua tab
        window.wahaActiveUsers = window.wahaActiveUsers.filter(u => u.id !== user.id);
        window.dispatchEvent(new CustomEvent('waha-agents-updated', { detail: { count: window.wahaActiveUsers.length } }));
    });
