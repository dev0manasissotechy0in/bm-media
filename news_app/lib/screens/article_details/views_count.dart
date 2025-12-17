import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/services/firebase_service_stub.dart';
import '../../providers/app_settings_provider.dart';

// Temporarily disabled during migration
final viewsStreamProvider = StateProvider.autoDispose.family<int, String>((ref, articleId) {
  return 0; // Return static value during migration
});

class ViewsCount extends ConsumerStatefulWidget {
  final Article article;
  final Color? textColor;

  const ViewsCount({super.key, required this.article, this.textColor});

  @override
  ConsumerState<ViewsCount> createState() => _ViewsCountState();
}

class _ViewsCountState extends ConsumerState<ViewsCount> {
  @override
  void initState() {
    _updateViews();
    super.initState();
  }

  _updateViews() async {
    await Future.delayed(const Duration(seconds: 3));
    FirebaseService().updateViewsCount(widget.article.id);
  }

  @override
  Widget build(BuildContext context) {
    final settings = ref.read(appSettingsProvider);
    if (settings?.views == false) return const SizedBox.shrink();

    // During migration, show the article's current view count
    return _text(widget.article.views ?? 0);
  }

  Row _text(int count) {
    return Row(
      children: [
        Icon(Icons.remove_red_eye, size: 20, color: widget.textColor ?? Colors.grey),
        const SizedBox(width: 3),
        Text(
          'count-views',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: widget.textColor ?? Colors.grey),
        ).tr(args: [count.toString()]),
      ],
    );
  }
}
