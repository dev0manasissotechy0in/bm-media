/**
 * Enhanced Web Notifications System
 * Handles web push notifications, notification bell, and user preferences
 * Works with the new NotificationManager backend
 */

class EnhancedNotificationSystem {
    constructor() {
        this.apiBase = '/api/notifications/index.php';
        this.unreadCount = 0;
        this.notifications = [];
        this.refreshInterval = null;
        this.userId = this.getUserId();
        
        this.init();
    }

    /**
     * Initialize the notification system
     */
    async init() {
        console.log('🔔 Initializing enhanced notification system...');
        
        // Request web push permission
        await this.requestPushPermission();
        
        // Load initial notifications
        await this.loadNotifications();
        
        // Start auto-refresh (every 30 seconds)
        this.startAutoRefresh();
        
        // Setup event listeners
        this.setupEventListeners();
        
        console.log('✅ Enhanced notification system initialized');
    }

    /**
     * Get user ID from session/cookie
     */
    getUserId() {
        // Try to get from meta tag or PHP session
        const metaTag = document.querySelector('meta[name="user-id"]');
        return metaTag ? metaTag.content : null;
    }

    /**
     * Request web push notification permission
     */
    async requestPushPermission() {
        if (!('Notification' in window)) {
            console.warn('⚠️ This browser does not support notifications');
            return;
        }

        if (!('serviceWorker' in navigator)) {
            console.warn('⚠️ This browser does not support service workers');
            return;
        }

        try {
            // Request permission
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('✅ Notification permission granted');
                
                // Register service worker
                const registration = await navigator.serviceWorker.register('/service-worker.js');
                console.log('✅ Service Worker registered');
                
                // Subscribe to push notifications
                await this.subscribeToPush(registration);
            } else {
                console.log('❌ Notification permission denied');
            }
        } catch (error) {
            console.error('❌ Error requesting permission:', error);
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribeToPush(registration) {
        try {
            // Get existing subscription or create new one
            let subscription = await registration.pushManager.getSubscription();
            
            if (!subscription) {
                // Create new subscription
                // Note: You need to generate VAPID keys for production
                const vapidPublicKey = 'BEl62iUYgUivxIkv69yViEuiBIa-Ib9-SkvMeAtA3LFgDzkrxZJjSgSnfckjBJuBkr3qBUYIHBQFLXYp5Nksh8U';
                
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey)
                });
            }
            
            // Send subscription to backend
            await this.sendSubscriptionToBackend(subscription);
            
            console.log('✅ Subscribed to push notifications');
        } catch (error) {
            console.error('❌ Error subscribing to push:', error);
        }
    }

    /**
     * Send subscription to backend
     */
    async sendSubscriptionToBackend(subscription) {
        const subscriptionJson = subscription.toJSON();
        
        await fetch(`${this.apiBase}?action=subscribe-web-push`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: this.userId,
                endpoint: subscriptionJson.endpoint,
                keys: subscriptionJson.keys
            })
        });
    }

    /**
     * Load notifications from backend
     */
    async loadNotifications() {
        try {
            const response = await fetch(
                `${this.apiBase}?action=get-notifications&user_id=${this.userId}&limit=20`
            );
            
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.updateUI();
            }
        } catch (error) {
            console.error('❌ Error loading notifications:', error);
        }
    }

    /**
     * Update UI with notifications
     */
    updateUI() {
        // Update badge
        const badge = document.getElementById('notificationBadge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }

        // Update mark all read button
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.style.display = this.unreadCount > 0 ? 'inline-block' : 'none';
        }

        // Update notification list
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            if (this.notifications.length === 0) {
                notificationList.innerHTML = `
                    <li class="text-center py-3 text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                        No notifications
                    </li>
                `;
            } else {
                notificationList.innerHTML = this.notifications
                    .map(notification => this.createNotificationHTML(notification))
                    .join('');
            }
        }
    }

    /**
     * Create HTML for a notification item
     */
    createNotificationHTML(notification) {
        const isRead = notification.is_read == 1;
        const icon = this.getNotificationIcon(notification.type);
        const timeAgo = this.getTimeAgo(notification.created_at);
        
        return `
            <li class="dropdown-item notification-item ${isRead ? 'read' : 'unread'}" 
                data-id="${notification.id}" 
                data-url="${notification.action_url || '#'}"
                style="white-space: normal; cursor: pointer; ${isRead ? '' : 'background-color: #f0f7ff;'}">
                <div class="d-flex align-items-start py-2">
                    <div class="flex-shrink-0 me-3">
                        <span class="fs-4">${icon}</span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold mb-1 text-truncate" style="font-size: 0.9rem;">
                            ${this.escapeHtml(notification.title)}
                        </div>
                        <div class="text-muted mb-1" style="font-size: 0.85rem; line-height: 1.3;">
                            ${this.escapeHtml(notification.message).substring(0, 100)}${notification.message.length > 100 ? '...' : ''}
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">${timeAgo}</small>
                    </div>
                    ${!isRead ? '<div class="flex-shrink-0"><span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px; padding: 0;"></span></div>' : ''}
                </div>
            </li>
            <li><hr class="dropdown-divider my-0"></li>
        `;
    }

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            'news': '📰',
            'breaking': '🔥',
            'case_study': '📋',
            'case_study_update': '🔄',
            'general': '🔔'
        };
        return icons[type] || '🔔';
    }

    /**
     * Get time ago string
     */
    getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Mark notification as read
     */
    async markAsRead(notificationId) {
        try {
            await fetch(`${this.apiBase}?action=mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notificationId,
                    user_id: this.userId
                })
            });
            
            // Update local state
            const notification = this.notifications.find(n => n.id == notificationId);
            if (notification && notification.is_read == 0) {
                notification.is_read = 1;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateUI();
            }
        } catch (error) {
            console.error('❌ Error marking as read:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllAsRead() {
        try {
            await fetch(`${this.apiBase}?action=mark-all-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: this.userId
                })
            });
            
            // Update local state
            this.notifications.forEach(n => n.is_read = 1);
            this.unreadCount = 0;
            this.updateUI();
            
            console.log('✅ All notifications marked as read');
        } catch (error) {
            console.error('❌ Error marking all as read:', error);
        }
    }

    /**
     * Track notification click
     */
    async trackClick(notificationId) {
        try {
            await fetch(`${this.apiBase}?action=track-click`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_id: notificationId,
                    user_id: this.userId
                })
            });
        } catch (error) {
            console.error('❌ Error tracking click:', error);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Mark all as read button
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.markAllAsRead();
            });
        }

        // Notification item clicks (using event delegation)
        const notificationList = document.getElementById('notificationList');
        if (notificationList) {
            notificationList.addEventListener('click', async (e) => {
                const item = e.target.closest('.notification-item');
                if (item) {
                    const notificationId = item.dataset.id;
                    const url = item.dataset.url;
                    
                    // Mark as read
                    await this.markAsRead(notificationId);
                    
                    // Track click
                    await this.trackClick(notificationId);
                    
                    // Navigate to URL
                    if (url && url !== '#') {
                        window.location.href = url;
                    }
                }
            });
        }

        // Refresh on dropdown open
        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('shown.bs.dropdown', () => {
                this.loadNotifications();
            });
        }
    }

    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        // Refresh every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Convert URL-safe base64 to Uint8Array (for VAPID key)
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

// Initialize enhanced notification system when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.enhancedNotificationSystem = new EnhancedNotificationSystem();
    });
} else {
    window.enhancedNotificationSystem = new EnhancedNotificationSystem();
}
