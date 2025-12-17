import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:line_icons/line_icons.dart';
// import 'package:news_app/screens/tabs/profile_tab/subscription_tile.dart';
import '../../../providers/user_data_provider.dart';
import '../../../providers/custom_pages_provider.dart';
import '../../contact_us_screen.dart';
import '../../custom_page_details_screen.dart';
import '../../../utils/next_screen.dart';
import 'guest_user.dart';
import 'settings.dart';
import 'user_info.dart';

class ProfileTab extends ConsumerWidget {
  const ProfileTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(userDataProvider);
    final customPagesAsync = ref.watch(customPagesProvider);
    
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            title: const Text('profile').tr(),
            pinned: true,
            titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
          ),
          SliverToBoxAdapter(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  user == null ? const GuestUser() : UserInfo(user: user, ref: ref),
                  // SubscriptionTile(user: user),
                  
                  // Quick Access Section
                  const SizedBox(height: 20),
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Text(
                      'Quick Access',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ),
                  Card(
                    elevation: 2,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Column(
                      children: [
                        // Contact Us
                        ListTile(
                          leading: Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Theme.of(context).primaryColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Icon(
                              LineIcons.envelope,
                              color: Theme.of(context).primaryColor,
                              size: 20,
                            ),
                          ),
                          title: const Text('Contact Us'),
                          subtitle: const Text('Get in touch with us'),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () => NextScreen.normal(context, const ContactUsScreen()),
                        ),
                        const Divider(height: 1),
                        
                        // Custom Pages
                        customPagesAsync.when(
                          data: (pages) {
                            if (pages.isEmpty) return const SizedBox.shrink();
                            
                            return Column(
                              children: pages.take(3).map((page) {
                                IconData icon;
                                String subtitle;
                                Color iconColor;
                                
                                switch (page.title.toLowerCase()) {
                                  case 'about us':
                                  case 'about':
                                    icon = LineIcons.infoCircle;
                                    subtitle = 'Learn more about us';
                                    iconColor = Colors.blue;
                                    break;
                                  case 'privacy policy':
                                  case 'privacy':
                                    icon = LineIcons.lock;
                                    subtitle = 'Our privacy policy';
                                    iconColor = Colors.green;
                                    break;
                                  case 'terms & conditions':
                                  case 'terms':
                                  case 'terms of use':
                                    icon = LineIcons.fileAlt;
                                    subtitle = 'Terms and conditions';
                                    iconColor = Colors.orange;
                                    break;
                                  default:
                                    icon = LineIcons.file;
                                    subtitle = 'View details';
                                    iconColor = Colors.grey;
                                }
                                
                                return Column(
                                  children: [
                                    ListTile(
                                      leading: Container(
                                        padding: const EdgeInsets.all(8),
                                        decoration: BoxDecoration(
                                          color: iconColor.withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(
                                          icon,
                                          color: iconColor,
                                          size: 20,
                                        ),
                                      ),
                                      title: Text(page.title),
                                      subtitle: Text(subtitle),
                                      trailing: const Icon(Icons.chevron_right),
                                      onTap: () => NextScreen.normal(
                                        context,
                                        CustomPageDetailsScreen(
                                          slug: page.slug,
                                          pageTitle: page.title,
                                        ),
                                      ),
                                    ),
                                    if (pages.indexOf(page) < pages.length - 1 && pages.indexOf(page) < 2)
                                      const Divider(height: 1),
                                  ],
                                );
                              }).toList(),
                            );
                          },
                          loading: () => const SizedBox.shrink(),
                          error: (_, __) => const SizedBox.shrink(),
                        ),
                      ],
                    ),
                  ),
                  
                  const SizedBox(height: 10),
                  const AppSettings(),
                ],
              ),
            ),
          )
        ],
      ),
    );
  }
}
