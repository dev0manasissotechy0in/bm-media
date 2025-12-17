import 'package:flutter/material.dart';
import 'package:timeago/timeago.dart' as timeago;
import '../services/enhanced_notification_service.dart';

/// Notifications Screen - Shows all notifications for the user
class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({Key? key}) : super(key: key);

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final EnhancedNotificationService _notificationService =
      EnhancedNotificationService();

  List<Map<String, dynamic>> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = true;
  bool _isLoadingMore = false;
  int _currentPage = 0;
  final int _pageSize = 20;
  bool _hasMore = true;

  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadNotifications();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
            _scrollController.position.maxScrollExtent - 200 &&
        !_isLoadingMore &&
        _hasMore) {
      _loadMoreNotifications();
    }
  }

  Future<void> _loadNotifications() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final result = await _notificationService.getNotifications(
        limit: _pageSize,
        offset: 0,
      );

      setState(() {
        _notifications = result['notifications'];
        _unreadCount = result['unread_count'];
        _currentPage = 0;
        _hasMore = result['notifications'].length == _pageSize;
        _isLoading = false;
      });
    } catch (e) {
      debugPrint('Error loading notifications: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _loadMoreNotifications() async {
    if (_isLoadingMore || !_hasMore) return;

    setState(() {
      _isLoadingMore = true;
    });

    try {
      final result = await _notificationService.getNotifications(
        limit: _pageSize,
        offset: (_currentPage + 1) * _pageSize,
      );

      setState(() {
        _notifications.addAll(result['notifications']);
        _currentPage++;
        _hasMore = result['notifications'].length == _pageSize;
        _isLoadingMore = false;
      });
    } catch (e) {
      debugPrint('Error loading more notifications: $e');
      setState(() {
        _isLoadingMore = false;
      });
    }
  }

  Future<void> _markAsRead(int notificationId, int index) async {
    final success = await _notificationService.markAsRead(notificationId);
    if (success) {
      setState(() {
        _notifications[index]['is_read'] = 1;
        if (_unreadCount > 0) _unreadCount--;
      });
    }
  }

  Future<void> _markAllAsRead() async {
    final success = await _notificationService.markAllAsRead();
    if (success) {
      setState(() {
        for (var notification in _notifications) {
          notification['is_read'] = 1;
        }
        _unreadCount = 0;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('All notifications marked as read')),
        );
      }
    }
  }

  void _onNotificationTap(Map<String, dynamic> notification, int index) {
    // Mark as read
    if (notification['is_read'] == 0) {
      _markAsRead(notification['id'], index);
    }

    // Navigate based on type
    final String? type = notification['type'];
    final int? referenceId = notification['reference_id'];

    // TODO: Implement navigation based on type
    // Example:
    // if (type == 'news' && referenceId != null) {
    //   Navigator.pushNamed(context, '/article', arguments: referenceId);
    // } else if (type == 'breaking' && referenceId != null) {
    //   Navigator.pushNamed(context, '/story', arguments: referenceId);
    // } else if (type == 'case_study' || type == 'case_study_update') {
    //   Navigator.pushNamed(context, '/case', arguments: referenceId);
    // }

    debugPrint('Navigate to: $type - $referenceId');
  }

  String _getNotificationIcon(String? type) {
    switch (type) {
      case 'news':
        return '📰';
      case 'breaking':
        return '🔥';
      case 'case_study':
        return '📋';
      case 'case_study_update':
        return '🔄';
      default:
        return '🔔';
    }
  }

  Color _getNotificationColor(String? type) {
    switch (type) {
      case 'news':
        return Colors.blue;
      case 'breaking':
        return Colors.red;
      case 'case_study':
        return Colors.purple;
      case 'case_study_update':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            const Text('Notifications'),
            if (_unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.red,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '$_unreadCount',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (_unreadCount > 0)
            IconButton(
              icon: const Icon(Icons.done_all),
              tooltip: 'Mark all as read',
              onPressed: _markAllAsRead,
            ),
          IconButton(
            icon: const Icon(Icons.settings),
            tooltip: 'Notification settings',
            onPressed: () {
              Navigator.pushNamed(context, '/notification-settings');
            },
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _notifications.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _loadNotifications,
                  child: ListView.builder(
                    controller: _scrollController,
                    itemCount: _notifications.length + (_isLoadingMore ? 1 : 0),
                    itemBuilder: (context, index) {
                      if (index == _notifications.length) {
                        return const Center(
                          child: Padding(
                            padding: EdgeInsets.all(16.0),
                            child: CircularProgressIndicator(),
                          ),
                        );
                      }

                      final notification = _notifications[index];
                      return _buildNotificationItem(notification, index);
                    },
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.notifications_off_outlined,
            size: 80,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'No notifications yet',
            style: TextStyle(
              fontSize: 18,
              color: Colors.grey[600],
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'We\'ll notify you when something new arrives',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey[500],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationItem(Map<String, dynamic> notification, int index) {
    final bool isRead = notification['is_read'] == 1;
    final String type = notification['type'] ?? 'general';
    final String title = notification['title'] ?? '';
    final String message = notification['message'] ?? '';
    final String? imageUrl = notification['image_url'];
    final DateTime? createdAt = notification['created_at'] != null
        ? DateTime.tryParse(notification['created_at'])
        : null;

    return Container(
      color: isRead ? Colors.transparent : Colors.blue.withOpacity(0.05),
      child: ListTile(
        onTap: () => _onNotificationTap(notification, index),
        leading: Stack(
          children: [
            CircleAvatar(
              backgroundColor: _getNotificationColor(type).withOpacity(0.1),
              child: Text(
                _getNotificationIcon(type),
                style: const TextStyle(fontSize: 24),
              ),
            ),
            if (!isRead)
              Positioned(
                right: 0,
                top: 0,
                child: Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                    border: Border.all(color: Colors.white, width: 2),
                  ),
                ),
              ),
          ],
        ),
        title: Text(
          title,
          style: TextStyle(
            fontWeight: isRead ? FontWeight.normal : FontWeight.bold,
            fontSize: 15,
          ),
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text(
              message,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 13,
                color: Colors.grey[600],
              ),
            ),
            const SizedBox(height: 4),
            Text(
              createdAt != null ? timeago.format(createdAt) : '',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[500],
              ),
            ),
          ],
        ),
        trailing: imageUrl != null && imageUrl.isNotEmpty
            ? ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.network(
                  imageUrl,
                  width: 60,
                  height: 60,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) {
                    return Container(
                      width: 60,
                      height: 60,
                      color: Colors.grey[300],
                      child: Icon(Icons.image_not_supported,
                          color: Colors.grey[500]),
                    );
                  },
                ),
              )
            : null,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 12,
        ),
      ),
    );
  }
}
