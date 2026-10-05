import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT, 10) : 8080;
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'http';
const isSecure = reverbScheme === 'https' || window.location.protocol === 'https:';

if (reverbKey) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS: isSecure,
            enabledTransports: ['ws', 'wss'],
        });
    } catch (err) {
        console.warn('[NDMU-RMAS] Echo initialization skipped:', err);
    }
}

function initLiveStateStore() {
    if (!window.Alpine) return;
    if (window.Alpine.store('liveState')) return;

    window.Alpine.store('liveState', {
        unreadCount: 0,
        recentNotifications: [],
        badges: {},
        toasts: [],
        initialized: false,
        fetching: false,

        init() {
            this.fetchLatest();
            this.setupListeners();
            this.setupPolling();
        },

        setupListeners() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            const userId = userIdMeta?.content;

            if (userId && window.Echo) {
                try {
                    window.Echo.private(`App.Models.User.${userId}`)
                        .listen('.UserLiveStateUpdated', (event) => {
                            this.applyState(event);
                            if (event.toast) {
                                this.addToast(event.toast);
                            }
                            window.dispatchEvent(new CustomEvent('ndmu:live-state-updated', { detail: event }));
                        });
                } catch (err) {
                    console.warn('[NDMU-RMAS] Echo channel subscribe failed:', err);
                }
            }
        },

        setupPolling() {
            // Reverb delivers changes while connected; poll only as a fallback.
            setInterval(() => {
                if (document.visibilityState === 'visible' && !this.reverbConnected()) {
                    this.fetchLatest();
                }
            }, 25000);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && !this.reverbConnected()) {
                    this.fetchLatest();
                }
            });

            window.Echo?.connector?.pusher?.connection?.bind('connected', () => this.fetchLatest());
        },

        reverbConnected() {
            return window.Echo?.connector?.pusher?.connection?.state === 'connected';
        },

        fetchLatest() {
            const userIdMeta = document.querySelector('meta[name="user-id"]');
            if (!userIdMeta?.content || this.fetching) return;

            this.fetching = true;

            fetch('/user/live-state', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((res) => (res.ok ? res.json() : null))
                .then((data) => {
                    if (data) {
                        this.applyState(data);
                    }
                })
                .catch(() => {})
                .finally(() => { this.fetching = false; });
        },

        applyState(data) {
            if (typeof data.unread_notifications === 'number') {
                this.unreadCount = data.unread_notifications;
            }
            if (Array.isArray(data.recent_notifications)) {
                this.recentNotifications = data.recent_notifications;
            }
            if (data.badges && typeof data.badges === 'object') {
                this.badges = { ...this.badges, ...data.badges };
            }
            this.initialized = true;
        },

        addToast(toast) {
            if (!toast || !toast.title) return;
            const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
            const item = {
                id,
                title: toast.title,
                message: toast.message || '',
                context: toast.context || 'Workflow Update',
                visible: true,
            };
            this.toasts.push(item);

            setTimeout(() => {
                this.dismissToast(id);
            }, 5500);
        },

        dismissToast(id) {
            const target = this.toasts.find((t) => t.id === id);
            if (target) {
                target.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter((t) => t.id !== id);
                }, 300);
            }
        },
    });
}

if (window.Alpine) {
    initLiveStateStore();
} else {
    document.addEventListener('alpine:init', initLiveStateStore);
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Alpine) {
            initLiveStateStore();
        }
    });
}
