import 'package:flutter/material.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:news_app/models/article.dart';

class LiveTimeline extends StatelessWidget {
  const LiveTimeline({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context) {
    debugPrint('🔴 LiveTimeline - isLive: ${article.isLive}, timelineUpdates: ${article.timelineUpdates?.length ?? 0}');
    debugPrint('🔴 LiveTimeline - timelineUpdates data: ${article.timelineUpdates}');
    
    if (article.isLive != true || article.timelineUpdates == null || article.timelineUpdates!.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      margin: const EdgeInsets.symmetric(vertical: 20),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: Colors.red.withValues(alpha: 0.3),
          width: 2,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: Colors.red.withValues(alpha: 0.1),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(8)),
            ),
            child: Row(
              children: [
                Container(
                  width: 10,
                  height: 10,
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  'LIVE UPDATES',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Colors.red,
                        letterSpacing: 1.2,
                      ),
                ),
                const Spacer(),
                Icon(Icons.update, color: Colors.red.shade700, size: 20),
              ],
            ),
          ),
          
          // Timeline Items
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(15),
            itemCount: article.timelineUpdates!.length,
            separatorBuilder: (context, index) => const SizedBox(height: 15),
            itemBuilder: (context, index) {
              final update = article.timelineUpdates![index];
              final isFirst = index == 0;
              
              return IntrinsicHeight(
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Timeline indicator
                    Column(
                      children: [
                        Container(
                          width: 12,
                          height: 12,
                          decoration: BoxDecoration(
                            color: isFirst ? Colors.red : Colors.grey,
                            shape: BoxShape.circle,
                            border: Border.all(
                              color: isFirst ? Colors.red.shade700 : Colors.grey.shade400,
                              width: 2,
                            ),
                          ),
                        ),
                        if (index < article.timelineUpdates!.length - 1)
                          Expanded(
                            child: Container(
                              width: 2,
                              color: Colors.grey.shade300,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(width: 15),
                    
                    // Content
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Time
                          Row(
                            children: [
                              Icon(
                                Icons.access_time,
                                size: 14,
                                color: isFirst ? Colors.red : Colors.grey.shade600,
                              ),
                              const SizedBox(width: 5),
                              Text(
                                _formatTime(update['time'] ?? ''),
                                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                      color: isFirst ? Colors.red : Colors.grey.shade600,
                                      fontWeight: isFirst ? FontWeight.w600 : FontWeight.normal,
                                    ),
                              ),
                              if (isFirst) ...[
                                const SizedBox(width: 8),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: Colors.red,
                                    borderRadius: BorderRadius.circular(3),
                                  ),
                                  child: const Text(
                                    'LATEST',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 10,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 8),
                          
                          // Update text
                          Text(
                            update['update'] ?? update['text'] ?? '',
                            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                                  height: 1.5,
                                  fontWeight: isFirst ? FontWeight.w500 : FontWeight.normal,
                                ),
                          ),
                          
                          // Image if available
                          if (update['image'] != null && update['image'].toString().isNotEmpty) ...[
                            const SizedBox(height: 10),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(8),
                              child: Image.network(
                                update['image'],
                                fit: BoxFit.cover,
                                height: 150,
                                width: double.infinity,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  String _formatTime(String time) {
    try {
      final dateTime = DateTime.parse(time);
      final now = DateTime.now();
      final difference = now.difference(dateTime);
      
      if (difference.inMinutes < 1) {
        return 'Just now';
      } else if (difference.inMinutes < 60) {
        return '${difference.inMinutes}m ago';
      } else if (difference.inHours < 24) {
        return '${difference.inHours}h ago';
      } else {
        return DateFormat('MMM dd, hh:mm a').format(dateTime);
      }
    } catch (e) {
      return time;
    }
  }
}
