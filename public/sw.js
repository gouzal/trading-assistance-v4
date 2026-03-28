const CACHE_NAME = 'trading-assistant-v3';
const STATIC_ASSETS = ['/dashboard', '/manifest.json'];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS))
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Trading Assistant';

    const options = {
        body:               data.body  || '',
        icon:               '/icons/icon-192.png',
        badge:              '/icons/icon-192.png',
        requireInteraction: true,
        data: {
            symbol: data.symbol || null,
            days:   data.days   ?? null,
        },
    };

    // Always attach Buy/Dismiss if this is a stock alert
    if (data.symbol) {
        options.actions = [
            { action: 'buy',     title: 'Buy' },
            { action: 'dismiss', title: 'Dismiss' },
        ];
    }

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const { symbol, days } = event.notification.data || {};
    const action = event.action; // 'buy', 'dismiss', or '' (tapped body)

    // Build the target URL with query params so the page can record the response
    let url = '/orders';
    if (symbol && (action === 'buy' || action === 'dismiss')) {
        const params = new URLSearchParams({ notify_action: action, symbol });
        if (action === 'buy' && days !== null) params.set('days', days);
        url = `/orders?${params.toString()}`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Try to reuse an existing app window
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            if (clients.openWindow) return clients.openWindow(url);
        })
    );
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    if (event.request.url.includes('/api/')) return; // Never cache API calls

    event.respondWith(
        fetch(event.request)
            .then(response => {
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});
