/**
 * Web Push Notifications Manager
 * Client-side JavaScript for managing push notifications
 */

class PushNotificationManager {
    constructor() {
        this.vapidPublicKey = 'YOUR_VAPID_PUBLIC_KEY'; // Generate from admin panel
        this.swRegistration = null;
        this.isSubscribed = false;
    }

    /**
     * Initialize push notifications
     */
    async init() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications not supported');
            return false;
        }

        try {
            // Register service worker
            this.swRegistration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('Service Worker registered:', this.swRegistration);

            // Check current subscription
            await this.checkSubscription();

            return true;
        } catch (error) {
            console.error('Service Worker registration failed:', error);
            return false;
        }
    }

    /**
     * Check if user is subscribed
     */
    async checkSubscription() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();
            this.isSubscribed = !(subscription === null);

            if (this.isSubscribed) {
                console.log('User is subscribed to push notifications');
                this.updateUI(true);
            } else {
                console.log('User is NOT subscribed to push notifications');
                this.updateUI(false);
            }

            return this.isSubscribed;
        } catch (error) {
            console.error('Error checking subscription:', error);
            return false;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        try {
            // Request permission
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.warn('Notification permission denied');
                return false;
            }

            // Subscribe to push manager
            const subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            console.log('User subscribed:', subscription);

            // Send subscription to server
            await this.sendSubscriptionToServer(subscription, 'subscribe');

            this.isSubscribed = true;
            this.updateUI(true);

            return true;
        } catch (error) {
            console.error('Failed to subscribe:', error);
            return false;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        try {
            const subscription = await this.swRegistration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();
                console.log('User unsubscribed');

                // Remove subscription from server
                await this.sendSubscriptionToServer(subscription, 'unsubscribe');

                this.isSubscribed = false;
                this.updateUI(false);

                return true;
            }
        } catch (error) {
            console.error('Failed to unsubscribe:', error);
            return false;
        }
    }

    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer(subscription, action) {
        try {
            const response = await fetch('/api/notifications/subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    subscription: subscription
                })
            });

            const result = await response.json();
            console.log('Server response:', result);

            return result.success;
        } catch (error) {
            console.error('Failed to send subscription to server:', error);
            return false;
        }
    }

    /**
     * Update UI based on subscription status
     */
    updateUI(subscribed) {
        const subscribeBtn = document.getElementById('push-subscribe-btn');
        const unsubscribeBtn = document.getElementById('push-unsubscribe-btn');

        if (subscribeBtn) {
            subscribeBtn.style.display = subscribed ? 'none' : 'inline-block';
        }

        if (unsubscribeBtn) {
            unsubscribeBtn.style.display = subscribed ? 'inline-block' : 'none';
        }
    }

    /**
     * Show local notification (for testing)
     */
    async showLocalNotification(title, options) {
        if (!('Notification' in window)) {
            console.warn('Notifications not supported');
            return;
        }

        if (Notification.permission === 'granted') {
            await this.swRegistration.showNotification(title, options);
        } else if (Notification.permission !== 'denied') {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await this.swRegistration.showNotification(title, options);
            }
        }
    }

    /**
     * Helper: Convert VAPID key
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Initialize on page load
const pushManager = new PushNotificationManager();

document.addEventListener('DOMContentLoaded', async () => {
    await pushManager.init();

    // Attach event listeners
    const subscribeBtn = document.getElementById('push-subscribe-btn');
    const unsubscribeBtn = document.getElementById('push-unsubscribe-btn');

    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', async () => {
            await pushManager.subscribe();
        });
    }

    if (unsubscribeBtn) {
        unsubscribeBtn.addEventListener('click', async () => {
            await pushManager.unsubscribe();
        });
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PushNotificationManager;
}
