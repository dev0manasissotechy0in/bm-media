import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:icons_plus/icons_plus.dart';
import 'package:line_icons/line_icons.dart';
import 'package:news_app/screens/bookmarks.dart';
import 'package:news_app/screens/custom_page_details_screen.dart';
import 'package:news_app/screens/contact_us_screen.dart';
import '../../../configs/features_config.dart';
import '../../../mixins/user_mixin.dart';
import '../../../providers/app_settings_provider.dart';
import '../../../providers/user_data_provider.dart';
import '../../../providers/custom_pages_provider.dart';
import '../../auth/delete_account.dart';
import '../../../components/languages.dart';
import '../../../services/app_service.dart';
import '../../../services/notification_service.dart';
import '../../../theme/theme_provider.dart';
import '../../../utils/logout_dialog.dart';
import '../../../utils/next_screen.dart';

class AppSettings extends ConsumerWidget with UserMixin {
  const AppSettings({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final bool notificationEnbaled = ref.watch(nProvider);
    final setttings = ref.watch(appSettingsProvider);
    final user = ref.watch(userDataProvider);
    final customPagesAsync = ref.watch(customPagesProvider);
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 20),
          child: const Text(
            'settings',
            style: TextStyle(fontWeight: FontWeight.bold),
          ).tr(),
        ),
        ListTile(
          leading: const Icon(LineIcons.bookmark),
          title: const Text('bookmarks').tr(),
          trailing: const Icon(FeatherIcons.chevronRight),
          onTap: () => NextScreen.normal(context, const Bookmarks()),
        ),
        const Divider(),
        ListTile(
          leading: Icon(notificationEnbaled ? LineIcons.bell : LineIcons.bellSlash),
          title: const Text('notifications').tr(),
          trailing: Switch.adaptive(
            value: notificationEnbaled,
            onChanged: (value) => NotificationService().handleSubscription(context, value, ref),
          ),
        ),
        const Divider(),
        ListTile(
          leading: const Icon(Icons.dark_mode),
          title: const Text('dark-mode').tr(),
          trailing: Switch.adaptive(
            value: ref.watch(themeProvider).isDarkMode,
            onChanged: (value) => ref.read(themeProvider.notifier).changeTheme(value),
          ),
        ),
        Visibility(
          visible: isMultilanguageEnbled,
          child: Column(
            children: [
              const Divider(),
              ListTile(
                title: const Text('language').tr(),
                leading: const Icon(LineIcons.language),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => NextScreen.openBottomSheet(context, const Languages()),
              ),
            ],
          ),
        ),
        const Divider(),
        
        // Dynamic Custom Pages from Admin Panel
        customPagesAsync.when(
          data: (pages) {
            if (pages.isEmpty) {
              // Fallback to default pages if no custom pages configured
              return Column(
                children: [
                  ListTile(
                    title: const Text('privacy-policy').tr(),
                    leading: const Icon(LineIcons.lock),
                    trailing: const Icon(FeatherIcons.chevronRight),
                    onTap: () => AppService().openLinkWithCustomTab(setttings?.privacyUrl ?? ''),
                  ),
                  const Divider(),
                  ListTile(
                    title: const Text('terms-of-use').tr(),
                    leading: const Icon(LineIcons.file),
                    trailing: const Icon(FeatherIcons.chevronRight),
                    onTap: () => AppService().openLinkWithCustomTab(setttings?.termsOfUseUrl ?? ''),
                  ),
                  const Divider(),
                ],
              );
            }
            
            // Show custom pages from admin panel
            return Column(
              children: pages.map((page) {
                IconData icon;
                switch (page.title.toLowerCase()) {
                  case 'about us':
                  case 'about':
                    icon = LineIcons.infoCircle;
                    break;
                  case 'privacy policy':
                  case 'privacy':
                    icon = LineIcons.lock;
                    break;
                  case 'terms & conditions':
                  case 'terms':
                  case 'terms of use':
                    icon = LineIcons.file;
                    break;
                  case 'contact us':
                  case 'contact':
                    icon = LineIcons.envelope;
                    break;
                  case 'disclaimer':
                    icon = LineIcons.exclamationTriangle;
                    break;
                  case 'cookie policy':
                    icon = LineIcons.cookieBite;
                    break;
                  default:
                    icon = LineIcons.fileAlt;
                }
                
                return Column(
                  children: [
                    ListTile(
                      title: Text(page.title),
                      leading: Icon(icon),
                      trailing: const Icon(FeatherIcons.chevronRight),
                      onTap: () => NextScreen.normal(
                        context,
                        CustomPageDetailsScreen(
                          slug: page.slug,
                          pageTitle: page.title,
                        ),
                      ),
                    ),
                    const Divider(),
                  ],
                );
              }).toList(),
            );
          },
          loading: () => Column(
            children: [
              ListTile(
                title: const Text('privacy-policy').tr(),
                leading: const Icon(LineIcons.lock),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => AppService().openLinkWithCustomTab(setttings?.privacyUrl ?? ''),
              ),
              const Divider(),
              ListTile(
                title: const Text('terms-of-use').tr(),
                leading: const Icon(LineIcons.file),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => AppService().openLinkWithCustomTab(setttings?.termsOfUseUrl ?? ''),
              ),
              const Divider(),
            ],
          ),
          error: (_, __) => Column(
            children: [
              ListTile(
                title: const Text('privacy-policy').tr(),
                leading: const Icon(LineIcons.lock),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => AppService().openLinkWithCustomTab(setttings?.privacyUrl ?? ''),
              ),
              const Divider(),
              ListTile(
                title: const Text('terms-of-use').tr(),
                leading: const Icon(LineIcons.file),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => AppService().openLinkWithCustomTab(setttings?.termsOfUseUrl ?? ''),
              ),
              const Divider(),
            ],
          ),
        ),
        
        ListTile(
          title: const Text('contact-us').tr(),
          leading: const Icon(LineIcons.envelope),
          trailing: const Icon(FeatherIcons.chevronRight),
          onTap: () => NextScreen.normal(context, const ContactUsScreen()),
        ),
        const Divider(),
        ListTile(
          title: const Text('rate-app').tr(),
          leading: const Icon(LineIcons.star),
          trailing: const Icon(FeatherIcons.chevronRight),
          onTap: () => AppService().launchAppReview(context, ref),
        ),
        Visibility(
          visible: user != null,
          child: Column(
            children: [
              const Divider(),
              ListTile(
                title: const Text('account-control').tr(),
                leading: const Icon(LineIcons.userCog),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => NextScreen.iOS(context, const DeleteAccount()),
              ),
              const Divider(),
              ListTile(
                title: const Text('logout').tr(),
                leading: const Icon(FeatherIcons.logOut),
                trailing: const Icon(FeatherIcons.chevronRight),
                onTap: () => openLogoutDialog(context, () => handleLogout(context, ref: ref)),
              ),
            ],
          ),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 30),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Visibility(
                visible: setttings?.social?.fb?.isNotEmpty ?? false,
                child: InkWell(
                  onTap: () => AppService().openLink(setttings?.social?.fb ?? ''),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    margin: const EdgeInsets.symmetric(horizontal: 6),
                    decoration: BoxDecoration(
                      border: Border.all(width: 0.5, color: Theme.of(context).dividerColor),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(LineIcons.facebook, size: 28),
                  ),
                ),
              ),
              Visibility(
                visible: setttings?.social?.twitter?.isNotEmpty ?? false,
                child: InkWell(
                  onTap: () => AppService().openLink(setttings?.social?.twitter ?? ''),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    margin: const EdgeInsets.symmetric(horizontal: 6),
                    decoration: BoxDecoration(
                      border: Border.all(width: 0.5, color: Theme.of(context).dividerColor),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(FontAwesome.x_twitter_brand),
                  ),
                ),
              ),
              Visibility(
                visible: setttings?.social?.youtube?.isNotEmpty ?? false,
                child: InkWell(
                  onTap: () => AppService().openLink(setttings?.social?.youtube ?? ''),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    margin: const EdgeInsets.symmetric(horizontal: 6),
                    decoration: BoxDecoration(
                      border: Border.all(width: 0.5, color: Theme.of(context).dividerColor),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(LineIcons.youtube, size: 28),
                  ),
                ),
              ),
              Visibility(
                visible: setttings?.social?.instagram?.isNotEmpty ?? false,
                child: InkWell(
                  onTap: () => AppService().openLink(setttings?.social?.instagram ?? ''),
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    margin: const EdgeInsets.symmetric(horizontal: 6),
                    decoration: BoxDecoration(
                      border: Border.all(width: 0.5, color: Theme.of(context).dividerColor),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(LineIcons.instagram, size: 28),
                  ),
                ),
              ),
            ],
          ),
          
        )
      ],
    );
  }
}
