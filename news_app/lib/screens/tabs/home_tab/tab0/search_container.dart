import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/user_avatar.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:news_app/providers/user_data_provider.dart';
import 'package:news_app/screens/auth/login.dart';
import 'package:news_app/screens/search/search_view.dart';
import 'package:news_app/screens/tabs/profile_tab/profile_tab.dart';
import 'package:news_app/utils/next_screen.dart';

class SearchContainer extends ConsumerWidget {
  const SearchContainer({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(userDataProvider);
    return Container(
      padding: const EdgeInsets.only(left: 15, right: 15, top: 15, bottom: 10),
      height: 65,
      width: MediaQuery.sizeOf(context).width,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.start,
        children: <Widget>[
          InkWell(
            child: UserAvatar(iconSize: 22, radius: 38, imageUrl: user?.imageUrl),
            onTap: () {
              if (user != null) {
                NextScreen.openBottomSheet(context, const Scaffold(body: ProfileTab()));
              } else {
                NextScreen.openBottomSheet(context, const LoginScreen(popUpScreen: true));
              }
            },
          ),
          const SizedBox(
            width: 10,
          ),
          Expanded(
            child: InkWell(
              child: Container(
                padding: const EdgeInsets.only(left: 20, right: 20),
                width: MediaQuery.sizeOf(context).width,
                alignment: Alignment.centerLeft,
                decoration: BoxDecoration(
                  color: Theme.of(context).cardColor,
                  border: Border.all(color: Colors.grey.shade400, width: 0.5),
                  borderRadius: BorderRadius.circular(40),
                ),
                child: Text(
                  'search-articles'.tr(),
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500),
                ),
              ),
              onTap: () {
                NextScreen.normal(context, const SearchScreen());
              },
            ),
          ),
        ],
      ),
    );
  }
}
