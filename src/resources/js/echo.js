/**
 * Laravel Echo + Reverb WebSocket client
 *
 * Menghubungkan browser ke server Reverb (WebSocket).
 * Ketika WAHA webhook menerima pesan baru, server broadcast event
 * ke sini, dan kita trigger Livewire refresh + sound notification.
 */

import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

// Auto-detect apakah halaman diakses via HTTPS
const isSecure = window.location.protocol === "https:";
const reverbHost = import.meta.env.VITE_REVERB_HOST ?? window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ?? 8080;
const configuredReverbScheme = import.meta.env.VITE_REVERB_SCHEME;
const reverbScheme =
    configuredReverbScheme === "https" || configuredReverbScheme === "wss"
        ? "wss"
        : configuredReverbScheme === "http" || configuredReverbScheme === "ws"
          ? "ws"
          : isSecure
            ? "wss"
            : "ws";
const reverbForceTls = reverbScheme === "wss";
const reverbEnabledTransports = reverbForceTls ? ["ws", "wss"] : ["ws"];
const shouldDisplayReverbPort =
    reverbPort &&
    !(
        (reverbScheme === "wss" && String(reverbPort) === "443") ||
        (reverbScheme === "ws" && String(reverbPort) === "80")
    );
const reverbDisplayPort = shouldDisplayReverbPort ? `:${reverbPort}` : "";
const reverbUrl = `${reverbScheme}://${reverbHost}${reverbDisplayPort}/app/${import.meta.env.VITE_REVERB_APP_KEY}`;

const reverbCopy = () => window.wacsReverbCopy ?? { messages: {}, reasons: {} };
const reverbStatusMessage = (state) =>
    reverbCopy().messages?.[state] ?? `Reverb: ${state}`;
const reverbReason = (reason) =>
    reverbCopy().reasons?.[reason] ?? `Reverb detail: ${reason}`;

const reverbStatusLogKey = "wacs_reverb_status_logs";
const maxReverbStatusLogs = 60;

const readReverbStatusLogs = () => {
    try {
        const logs = JSON.parse(
            localStorage.getItem(reverbStatusLogKey) || "[]",
        );

        return Array.isArray(logs) ? logs : [];
    } catch (error) {
        return [];
    }
};

const writeReverbStatusLog = (payload) => {
    const logs = readReverbStatusLogs();
    const nextLogs = [
        {
            at: new Date().toISOString(),
            ...payload,
        },
        ...logs,
    ].slice(0, maxReverbStatusLogs);

    try {
        localStorage.setItem(reverbStatusLogKey, JSON.stringify(nextLogs));
    } catch (error) {
        // LocalStorage can be unavailable in strict browser modes. Console log
        // status still works, so the UI can continue from in-memory state.
    }

    window.wahaReverbStatusLogs = nextLogs;
};

window.wahaGetReverbStatus = () =>
    window.wahaReverbStatus ?? {
        state: "initialized",
        message: reverbStatusMessage("initialized"),
        wsUrl: reverbUrl,
        host: reverbHost,
        port: reverbPort,
        secure: isSecure,
        updatedAt: new Date().toISOString(),
    };

window.wahaGetReverbStatusLogs = readReverbStatusLogs;

const normalizeReverbError = (error) => {
    const code = error?.data?.code ?? error?.code;
    const message = error?.data?.message ?? error?.message;

    if (code === 4001) {
        return reverbReason("invalid_app_key");
    }

    if (code === 1006) {
        return reverbReason("abnormal_close");
    }

    if (message) {
        return message;
    }

    return reverbReason("no_details");
};

const logReverbStatus = (state, context = {}) => {
    const message = reverbStatusMessage(state);
    const payload = {
        state,
        message,
        wsUrl: reverbUrl,
        host: reverbHost,
        port: reverbPort,
        secure: isSecure,
        updatedAt: new Date().toISOString(),
        ...context,
    };

    window.wahaReverbStatus = payload;
    window.dispatchEvent(
        new CustomEvent("wacs-reverb-status-changed", {
            detail: payload,
        }),
    );
    writeReverbStatusLog(payload);

    if (state === "connected") {
        console.info("[Reverb]", message, payload);
        return;
    }

    if (["unavailable", "failed", "disconnected"].includes(state)) {
        console.warn("[Reverb]", message, payload);
        return;
    }

    console.log("[Reverb]", message, payload);
};

if (!import.meta.env.VITE_REVERB_APP_KEY) {
    logReverbStatus("failed", {
        reason: reverbReason("missing_app_key"),
    });
}

logReverbStatus("connecting");

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbForceTls,
    enabledTransports: reverbEnabledTransports,
    disableStats: true,
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                const csrfToken = document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content");
                console.log(
                    "[WS-Auth] Authorizing channel:",
                    channel.name,
                    "socket:",
                    socketId,
                    "csrf:",
                    csrfToken ? "found" : "MISSING",
                );
                fetch("/broadcasting/auth", {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        socket_id: socketId,
                        channel_name: channel.name,
                    }),
                })
                    .then((response) => {
                        console.log(
                            "[WS-Auth] Response status:",
                            response.status,
                        );
                        if (!response.ok)
                            throw new Error("Auth failed: " + response.status);
                        return response.json();
                    })
                    .then((data) => {
                        console.log("[WS-Auth] Auth success:", data);
                        callback(false, data);
                    })
                    .catch((error) => {
                        console.error("[WS-Auth] Auth error:", error);
                        callback(true, error);
                    });
            },
        };
    },
});

window.wahaWsOnline =
    window.Echo.connector.pusher.connection.state === "connected";

const setWahaWsOnline = (online) => {
    window.wahaWsOnline = online;
    window.dispatchEvent(
        new CustomEvent(online ? "waha-ws-connected" : "waha-ws-disconnected"),
    );
};

const syncCurrentReverbState = () => {
    const connection = window.Echo?.connector?.pusher?.connection;

    if (!connection) {
        return;
    }

    const state = connection.state || "initialized";

    if (state === "connected") {
        logReverbStatus("connected", {
            socketId: connection.socket_id,
            source: "state-sync",
        });
        setWahaWsOnline(true);

        return;
    }

    if (["disconnected", "unavailable", "failed"].includes(state)) {
        logReverbStatus(state, {
            source: "state-sync",
        });
        setWahaWsOnline(false);
    }
};

/**
 * Subscribe ke channel 'waha-inbox'.
 * Ketika event diterima:
 * 1. Trigger Livewire dispatch â†’ loadInbox() di PHP
 * 2. Dispatch custom DOM event â†’ sound notification di Alpine.js
 */
let wahaInboxUpdateTimer = null;
let wahaInboxLatestChatId = null;

window.Echo.channel("waha-inbox").listen(".inbox.updated", (event) => {
    console.info("[Reverb] Event inbox.updated diterima.", event);
    wahaInboxLatestChatId = event.chat_id;
    const isIncoming = Boolean(event.is_incoming);

    // 1. Refresh data via Livewire dengan debounce agar burst pesan tidak memicu loadInbox berulang.
    clearTimeout(wahaInboxUpdateTimer);
    wahaInboxUpdateTimer = setTimeout(() => {
        if (window.Livewire) {
            window.Livewire.dispatch("waha-inbox-updated", {
                chatId: wahaInboxLatestChatId,
            });
        } else {
            console.warn("[Reverb] Livewire belum tersedia saat event inbox.updated diterima.");
        }
    }, 300);

    // 2. Notifikasi suara dan browser (hanya untuk pesan masuk)
    if (isIncoming) {
        window.dispatchEvent(
            new CustomEvent("waha-new-message", {
                detail: { chatId: event.chat_id, isIncoming: true },
            }),
        );

        if ("Notification" in window && Notification.permission === "granted") {
            const isTabHidden = document.hidden;
            let activeChatId = null;

            try {
                const el = document.querySelector('[wire\\:id]');
                if (el && el.__livewire) {
                    activeChatId = el.__livewire.get('selectedChatId');
                }
            } catch (e) {}

            if (isTabHidden || activeChatId !== event.chat_id) {
                try {
                    const title = "VPoint Care";
                    const body =
                        window.wacsNotificationBodyCopy ||
                        window.wacsReverbCopy?.notification_body ||
                        "WhatsApp message";
                    const notif = new Notification(title, { body });

                    notif.onclick = () => {
                        window.focus();
                        try {
                            const el = document.querySelector('[wire\\:id]');
                            if (el && el.__livewire) {
                                el.__livewire.call('selectChat', event.chat_id);
                            }
                        } catch (e) {}
                    };
                } catch (e) {
                    console.error("[Notification] Error triggering browser notification:", e);
                }
            }
        }
    }
});

// Expose Echo connection state untuk UI indicator
window.Echo.connector.pusher.connection.bind("connected", () => {
    logReverbStatus("connected", {
        socketId: window.Echo.connector.pusher.connection.socket_id,
    });
    setWahaWsOnline(true);
});
window.Echo.connector.pusher.connection.bind("disconnected", () => {
    logReverbStatus("disconnected");
    setWahaWsOnline(false);
});
window.Echo.connector.pusher.connection.bind("unavailable", () => {
    logReverbStatus("unavailable", {
        reason: reverbReason("browser_unavailable"),
    });
    setWahaWsOnline(false);
});
window.Echo.connector.pusher.connection.bind("failed", () => {
    logReverbStatus("failed");
    setWahaWsOnline(false);
});
window.Echo.connector.pusher.connection.bind("state_change", (states) => {
    logReverbStatus(states.current, {
        previous: states.previous,
    });
});
window.Echo.connector.pusher.connection.bind("error", (error) => {
    const reason = normalizeReverbError(error);
    const payload = {
        state: window.Echo.connector.pusher.connection.state,
        message: reverbStatusMessage("error"),
        reason,
        wsUrl: reverbUrl,
        host: reverbHost,
        port: reverbPort,
        secure: isSecure,
        updatedAt: new Date().toISOString(),
        errorCode: error?.data?.code ?? error?.code ?? null,
    };

    console.error("[Reverb] Error koneksi Reverb.", {
        state: window.Echo.connector.pusher.connection.state,
        reason,
        wsUrl: reverbUrl,
        error,
    });

    window.wahaReverbStatus = payload;
    window.dispatchEvent(
        new CustomEvent("wacs-reverb-status-changed", {
            detail: payload,
        }),
    );
    writeReverbStatusLog(payload);
});

syncCurrentReverbState();

const reverbInitialSync = window.setInterval(() => {
    syncCurrentReverbState();

    if (window.Echo?.connector?.pusher?.connection?.state === "connected") {
        window.clearInterval(reverbInitialSync);
    }
}, 500);

window.setTimeout(() => window.clearInterval(reverbInitialSync), 10000);

// Presence channel untuk tracking Agent/CS aktif secara unik berdasarkan ID
window.Echo.join("waha-agents")
    .here((users) => {
        window.wahaActiveUsers = users || [];
        window.dispatchEvent(
            new CustomEvent("waha-agents-updated", {
                detail: { count: window.wahaActiveUsers.length },
            }),
        );
    })
    .joining((user) => {
        if (!window.wahaActiveUsers) window.wahaActiveUsers = [];
        // Pastikan tidak ada duplikat ID jika user login di banyak tab
        if (!window.wahaActiveUsers.find((u) => u.id === user.id)) {
            window.wahaActiveUsers.push(user);
        }
        window.dispatchEvent(
            new CustomEvent("waha-agents-updated", {
                detail: { count: window.wahaActiveUsers.length },
            }),
        );
    })
    .leaving((user) => {
        if (!window.wahaActiveUsers) window.wahaActiveUsers = [];
        // Hapus user dari array saat mereka benar-benar keluar dari semua tab
        window.wahaActiveUsers = window.wahaActiveUsers.filter(
            (u) => u.id !== user.id,
        );
        window.dispatchEvent(
            new CustomEvent("waha-agents-updated", {
                detail: { count: window.wahaActiveUsers.length },
            }),
        );
    });
