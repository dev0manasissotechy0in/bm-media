import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../components/user_avatar.dart';
import '../../../mixins/user_mixin.dart';
import '../../../models/user_model.dart';
import '../../edit_profile.dart';
import '../../../utils/next_screen.dart';

class UserInfo extends StatelessWidget with UserMixin {
  const UserInfo({super.key, required this.user, required this.ref});

  final UserModel user;
  final WidgetRef ref;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
          onTap: () => NextScreen.openBottomSheet(context, EditProfile(user: user), maxHeight: 0.80),
          title: Text(
            user.name,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold, fontSize: 20),
          ),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(user.email),
            ],
          ),
          leading: UserAvatar(imageUrl: user.imageUrl, radius: 50, iconSize: 25),
          trailing: const Icon(
            FeatherIcons.edit3,
            size: 20,
          ),
        ),
      ],
    );
  }
}
