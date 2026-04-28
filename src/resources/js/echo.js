/**
 * Laravel Echo + Reverb WebSocket client
 *
 * File ini mengatur koneksi WebSocket ke server Reverb.
 * Reverb adalah padanan SignalR untuk Laravel — push real-time
 * dari server ke browser tanpa polling.
 *
 * Dipanggil dari app.js, dan berjalan di halaman mana pun yang
 * memuat bundle Vite (termasuk semua halaman Filament admin).
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Pusher-js digunakan sebagai transport layer oleh Echo,
// tapi server-nya adalah Reverb (bukan Pusher cloud).
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    // Nonaktifkan Pusher stats (tidak perlu untuk Reverb)
    disableStats: true,
});

/**
 * Subscribe ke channel 'waha-inbox' dan dengarkan event 'inbox.updated'.
 *
 * Ketika event diterima, kita cari komponen Livewire InboxWhatsapp di halaman
 * dan trigger event 'waha-inbox-updated' yang akan memanggil handleInboxUpdate().
 *
 * Menggunakan 'window.Livewire.dispatch' agar tidak perlu referensi spesifik
 * ke instance komponen — semua komponen yang punya listener #[On] akan merespons.
 */
window.Echo.channel('waha-inbox')
    .listen('.inbox.updated', (event) => {
        // Kirim event ke semua komponen Livewire di halaman ini
        if (window.Livewire) {
            window.Livewire.dispatch('waha-inbox-updated', { chatId: event.chat_id });
        }
    });
