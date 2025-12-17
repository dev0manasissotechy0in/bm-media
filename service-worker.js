// Service Worker for Push Notifications
self.addEventListener('install', (event) => {
    console.log('Service Worker installing.');
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    console.log('Service Worker activating.');
    event.waitUntil(clients.claim());
});

// Handle push notifications
self.addEventListener('push', (event) => {
    console.log('Push notification received:', event);
    
    let data = {};
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = {
                title: 'New Notification',
                body: event.data.text(),
                icon: '/assets/images/icon-192.png',
                badge: '/assets/images/badge-72.png'
            };
        }
    }
    
    const options = {
        body: data.body || 'You have a new update',
        icon: data.icon || '/assets/images/icon-192.png',
        badge: data.badge || '/assets/images/badge-72.png',
        image: data.image || null,
        data: {
            url: data.url || '/',
            dateOfArrival: Date.now(),
            primaryKey: data.id || 1
        },
        actions: [
            {
                action: 'open',
                title: 'Open',
                icon: '/assets/images/checkmark.png'
            },
            {
                action: 'close',
                title: 'Close',
                icon: '/assets/images/close.png'
            }
        ],
        tag: data.tag || 'default',
        requireInteraction: data.requireInteraction || false,
        renotify: data.renotify || false,
        vibrate: [200, 100, 200],
        timestamp: Date.now()
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'News Update', options)
    );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    if (event.action === 'close') {
        return;
    }
    
    const urlToOpen = event.notification.data.url || '/';
    
    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then((windowClients) => {
            // Check if there is already a window open
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, open a new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Handle notification close
self.addEventListener('notificationclose', (event) => {
    console.log('Notification closed:', event);
    
    // Track notification close (optional)
    const notification = event.notification;
    const primaryKey = notification.data.primaryKey;
    
    // Send analytics or tracking data
});
