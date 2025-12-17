import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../models/case_notification.dart';
import '../../services/case_notification_service.dart';
import '../../providers/user_provider.dart';
import '../cases/case_thread_detail_screen.dart';

class CaseNotificationsScreen extends StatefulWidget {
  const CaseNotificationsScreen({Key? key}) : super(key: key);

  @override
  State<CaseNotificationsScreen> createState() => _CaseNotificationsScreenState();
}

class _CaseNotificationsScreenState extends State<CaseNotificationsScreen> {
  List<CaseNotification> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = true;
  bool _hasError = false;
  int _currentPage = 1;
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
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent * 0.9) {
      if (!_isLoading && _hasMore) {
        _loadMore();
      }
    }
  }

  Future<void> _loadNotifications() async {
    setState(() {
      _isLoading = true;
      _hasError = false;
    });

    try {
      final userProvider = Provider.of<UserProvider>(context, listen: false);
      final userId = userProvider.user?.id ?? 1; // Placeholder

      final result = await CaseNotificationService.getNotifications(
        userId: userId,
        page: 1,
        perPage: 20,
      );

      if (mounted) {
        setState(() {
          _notifications = result['notifications'] ?? [];
          _unreadCount = result['unread_count'] ?? 0;
          _isLoading = false;
          _currentPage = 1;
          final pagination = result['pagination'];
          _hasMore = pagination != null && pagination['current_page'] < pagination['total_pages'];
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isLoading = false;
          _hasError = true;
        });
      }
    }
  }

  Future<void> _loadMore() async {
    if (_isLoading || !_hasMore) return;

    setState(() => _isLoading = true);

    try {
      final userProvider = Provider.of<UserProvider>(context, listen: false);
      final userId = userProvider.user?.id ?? 1;

      final result = await CaseNotificationService.getNotifications(
        userId: userId,
        page: _currentPage + 1,
        perPage: 20,
      );

      if (mounted) {
        setState(() {
          _notifications.addAll(result['notifications'] ?? []);
          _currentPage++;
          _isLoading = false;
          final pagination = result['pagination'];
          _hasMore = pagination != null && pagination['current_page'] < pagination['total_pages'];
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _markAsRead(CaseNotification notification) async {
    if (notification.isRead) return;

    final userProvider = Provider.of<UserProvider>(context, listen: false);
    final userId = userProvider.user?.id ?? 1;

    final success = await CaseNotificationService.markAsRead(
      userId: userId,
      notificationId: notification.id,
    );

    if (success && mounted) {
      setState(() {
        final index = _notifications.indexWhere((n) => n.id == notification.id);
        if (index != -1) {
          _notifications[index] = CaseNotification(
            id: notification.id,
            userId: notification.userId,
            caseId: notification.caseId,
            notificationType: notification.notificationType,
            title: notification.title,
            message: notification.message,
            actionUrl: notification.actionUrl,
            entityType: notification.entityType,
            entityId: notification.entityId,
            isRead: true,
            isSent: notification.isSent,
            createdAt: notification.createdAt,
            readAt: DateTime.now(),
            caseTitle: notification.caseTitle,
            caseSlug: notification.caseSlug,
            caseThumbnail: notification.caseThumbnail,
            caseStatus: notification.caseStatus,
            createdAtFormatted: notification.createdAtFormatted,
            timeAgo: notification.timeAgo,
          );
          _unreadCount = (_unreadCount > 0) ? _unreadCount - 1 : 0;
        }
      });
    }
  }

  Future<void> _markAllAsRead() async {
    final userProvider = Provider.of<UserProvider>(context, listen: false);
    final userId = userProvider.user?.id ?? 1;

    final success = await CaseNotificationService.markAllAsRead(userId: userId);

    if (success && mounted) {
      _loadNotifications();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('All notifications marked as read')),
      );
    }
  }

  void _handleNotificationTap(CaseNotification notification) {
    _markAsRead(notification);

    if (notification.caseSlug != null) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => CaseThreadDetailScreen(slug: notification.caseSlug!),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Notifications'),
            if (_unreadCount > 0)
              Text(
                '$_unreadCount unread',
                style: Theme.of(context).textTheme.bodySmall,
              ),
          ],
        ),
        actions: [
          if (_unreadCount > 0)
            TextButton(
              onPressed: _markAllAsRead,
              child: const Text('Mark all read'),
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadNotifications,
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading && _notifications.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_hasError) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.grey),
            const SizedBox(height: 16),
            const Text('Failed to load notifications'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadNotifications,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (_notifications.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: const [
            Icon(Icons.notifications_off, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text('No notifications', style: TextStyle(fontSize: 18, color: Colors.grey)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadNotifications,
      child: ListView.builder(
        controller: _scrollController,
        itemCount: _notifications.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index >= _notifications.length) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(16.0),
                child: CircularProgressIndicator(),
              ),
            );
          }

          final notification = _notifications[index];
          return _buildNotificationItem(notification);
        },
      ),
    );
  }

  Widget _buildNotificationItem(CaseNotification notification) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      color: notification.isRead ? Colors.white : Colors.blue.shade50,
      child: InkWell(
        onTap: () => _handleNotificationTap(notification),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Icon
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: notification.getColor().withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  notification.getIcon(),
                  color: notification.getColor(),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            notification.title,
                            style: TextStyle(
                              fontWeight: notification.isRead ? FontWeight.normal : FontWeight.bold,
                              fontSize: 15,
                            ),
                          ),
                        ),
                        if (!notification.isRead)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                            decoration: BoxDecoration(
                              color: Colors.blue,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Text(
                              'New',
                              style: TextStyle(color: Colors.white, fontSize: 10),
                            ),
                          ),
                      ],
                    ),
                    if (notification.message != null) ...[
                      const SizedBox(height: 4),
                      Text(
                        notification.message!,
                        style: const TextStyle(fontSize: 13, color: Colors.grey),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.access_time, size: 14, color: Colors.grey.shade600),
                        const SizedBox(width: 4),
                        Text(
                          notification.timeAgo ?? '',
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                        ),
                        if (!notification.isRead) ...[
                          const Spacer(),
                          TextButton(
                            onPressed: () => _markAsRead(notification),
                            style: TextButton.styleFrom(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              minimumSize: Size.zero,
                              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            ),
                            child: const Text('Mark read', style: TextStyle(fontSize: 12)),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
