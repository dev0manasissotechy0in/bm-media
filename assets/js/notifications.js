/**
 * NOTIFICATION SYSTEM FOR CASE THREADS
 * Handles real-time notification updates and interactions
 */

(function() {
    'use strict';
    
    let notificationCheckInterval = null;
    const CHECK_INTERVAL = 30000; // Check every 30 seconds
    
    // Initialize notification system
    function initNotifications() {
        if (!isUserLoggedIn()) {
            return;
        }
        
        // Load notifications on page load
        loadNotifications();
        
        // Set up periodic checking
        notificationCheckInterval = setInterval(loadNotifications, CHECK_INTERVAL);
        
        // Mark all read handler
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', markAllAsRead);
        }
        
        // Dropdown shown event - refresh notifications
        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('shown.bs.dropdown', loadNotifications);
        }
    }
    
    // Check if user is logged in
    function isUserLoggedIn() {
        // Check if notification elements exist (they only show for logged-in users)
        return document.getElementById('notificationDropdown') !== null;
    }
    
    // Load notifications from API
    async function loadNotifications() {
        try {
            // Get user ID from session or use placeholder
            const userId = getUserId();
            
            const response = await fetch(`${API_URL}/notifications/list.php?user_id=${userId}&per_page=10`);
            const data = await response.json();
            
            if (data.success) {
                updateNotificationUI(data.data);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }
    
    // Update notification UI
    function updateNotificationUI(data) {
        const badge = document.getElementById('notificationBadge');
        const list = document.getElementById('notificationList');
        const markAllBtn = document.getElementById('markAllReadBtn');
        const notifications = data.notifications || [];
        const unreadCount = data.unread_count || 0;
        
        // Update badge
        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Update mark all button visibility
        if (markAllBtn) {
            markAllBtn.style.display = unreadCount > 0 ? 'block' : 'none';
        }
        
        // Update notification list
        if (list) {
            if (notifications.length === 0) {
                list.innerHTML = `
                    <li class="text-center py-3 text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                        No notifications
                    </li>
                `;
            } else {
                list.innerHTML = notifications.map(notification => createNotificationHTML(notification)).join('');
                
                // Attach click handlers
                attachNotificationHandlers();
            }
        }
    }
    
    // Create HTML for single notification
    function createNotificationHTML(notification) {
        const isUnread = !notification.is_read;
        const bgClass = isUnread ? 'bg-light' : '';
        const icon = getNotificationIcon(notification.notification_type);
        const color = getNotificationColor(notification.notification_type);
        
        return `
            <li class="dropdown-item notification-item ${bgClass}" 
                data-notification-id="${notification.id}"
                data-action-url="${notification.action_url || ''}"
                style="cursor: pointer; white-space: normal; padding: 12px 16px; border-bottom: 1px solid #eee;">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <i class="bi bi-${icon} fs-4 text-${color}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="mb-0">${escapeHtml(notification.title)}</strong>
                            ${isUnread ? '<span class="badge bg-primary rounded-pill" style="font-size: 0.6rem;">New</span>' : ''}
                        </div>
                        ${notification.message ? `<p class="mb-1 text-muted small">${escapeHtml(notification.message)}</p>` : ''}
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>${notification.time_ago}
                            </small>
                            ${isUnread ? '<button class="btn btn-sm btn-link p-0 mark-read-btn" data-notification-id="' + notification.id + '"><small>Mark read</small></button>' : ''}
                        </div>
                    </div>
                </div>
            </li>
        `;
    }
    
    // Get icon for notification type
    function getNotificationIcon(type) {
        const icons = {
            'new_article': 'newspaper',
            'timeline_event': 'clock-history',
            'document_added': 'file-earmark-text',
            'verdict': 'gavel',
            'case_update': 'info-circle',
            'case_closed': 'check-circle'
        };
        return icons[type] || 'bell';
    }
    
    // Get color for notification type
    function getNotificationColor(type) {
        const colors = {
            'new_article': 'primary',
            'timeline_event': 'info',
            'document_added': 'success',
            'verdict': 'danger',
            'case_update': 'warning',
            'case_closed': 'secondary'
        };
        return colors[type] || 'primary';
    }
    
    // Attach handlers to notification items
    function attachNotificationHandlers() {
        // Click on notification item
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', async function(e) {
                // Don't trigger if clicking mark read button
                if (e.target.closest('.mark-read-btn')) {
                    return;
                }
                
                const notificationId = this.dataset.notificationId;
                const actionUrl = this.dataset.actionUrl;
                
                // Mark as read
                await markNotificationAsRead(notificationId);
                
                // Navigate to action URL
                if (actionUrl) {
                    window.location.href = BASE_URL + actionUrl;
                }
            });
        });
        
        // Click on mark read button
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.stopPropagation();
                const notificationId = this.dataset.notificationId;
                await markNotificationAsRead(notificationId);
                loadNotifications(); // Refresh list
            });
        });
    }
    
    // Mark single notification as read
    async function markNotificationAsRead(notificationId) {
        try {
            const userId = getUserId();
            const response = await fetch(`${API_URL}/notifications/mark-read.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    notification_id: parseInt(notificationId)
                })
            });
            
            const data = await response.json();
            return data.success;
        } catch (error) {
            console.error('Error marking notification as read:', error);
            return false;
        }
    }
    
    // Mark all notifications as read
    async function markAllAsRead() {
        try {
            const userId = getUserId();
            const response = await fetch(`${API_URL}/notifications/mark-read.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    mark_all: true
                })
            });
            
            const data = await response.json();
            if (data.success) {
                loadNotifications(); // Refresh list
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    // Get user ID (placeholder - should come from session)
    function getUserId() {
        // TODO: Get from session or JWT token
        return 1;
    }
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotifications);
    } else {
        initNotifications();
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (notificationCheckInterval) {
            clearInterval(notificationCheckInterval);
        }
    });
})();
