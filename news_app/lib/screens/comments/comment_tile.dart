import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/services/firebase_service_stub.dart';
import '../../models/comment.dart';
import '../../providers/app_settings_provider.dart';
import '../../providers/user_data_provider.dart';
import '../../services/app_service.dart';
import '../../components/user_avatar.dart';

class CommentTile extends ConsumerWidget {
  const CommentTile({super.key, required this.comment, required this.onRefresh});

  final Comment comment;
  final VoidCallback onRefresh;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
      leading: UserAvatar(
        imageUrl: comment.commentUser.imageUrl,
        iconSize: 25,
        radius: 50,
      ),
      isThreeLine: true,
      title: Text(
        comment.commentUser.name,
        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            comment.comment,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.normal),
          ),
          const SizedBox(
            height: 10,
          ),
          Text(
            AppService.getDateTime(comment.createdAt),
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.normal, fontSize: 13),
          )
        ],
      ),
      trailing: _menuButton(ref, context),
    );
  }

  PopupMenuButton _menuButton(WidgetRef ref, context) {
    final user = ref.watch(userDataProvider);
    final bool hasDeleteAccess = user != null && user.id == comment.commentUser.id;
    return PopupMenuButton(
      color: Theme.of(context).cardColor,
      itemBuilder: (context) {
        return [
          PopupMenuItem(
            child: Text('report', style: Theme.of(context).textTheme.titleMedium).tr(),
            onTap: () {
              final user = ref.read(userDataProvider);
              final supportEmail = ref.read(appSettingsProvider)?.supportEmail ?? '';
              AppService().openCommentReportEmail(context, comment, user, supportEmail);
            },
          ),
          !hasDeleteAccess
              ? const PopupMenuItem(child: null, enabled: false)
              : PopupMenuItem(
                  child: Text('delete', style: Theme.of(context).textTheme.titleMedium).tr(),
                  onTap: () => _handleDeleteComment(),
                ),
        ];
      },
      child: const Icon(FeatherIcons.moreHorizontal, size: 20),
    );
  }

  _handleDeleteComment() async {
    await FirebaseService().deleteComment(comment);
    onRefresh();
  }
}
