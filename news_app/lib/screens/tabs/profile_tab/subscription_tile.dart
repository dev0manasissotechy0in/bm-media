// import 'package:easy_localization/easy_localization.dart';
// import 'package:feather_icons/feather_icons.dart';
// import 'package:flutter/material.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:news_app/iAP/iap_config.dart';
// import 'package:news_app/iAP/iap_screen.dart';
// import 'package:news_app/models/app_settings_model.dart';
// import 'package:news_app/models/user_model.dart';
// import 'package:news_app/utils/next_screen.dart';
// import '../../../configs/app_assets.dart';
// import '../../../mixins/user_mixin.dart';
// import '../../../providers/app_settings_provider.dart';

// class SubscriptionTile extends ConsumerWidget {
//   const SubscriptionTile({super.key, required this.user});

//   final UserModel? user;

//   @override
//   Widget build(BuildContext context, WidgetRef ref) {
//     final settings = ref.read(appSettingsProvider);
//     if (IAPConfig.iAPEnabled && settings?.license == LicenseType.extended) {
//       if (user != null && user?.isPremiumUser == true) {
//         return _SubscribedContainer(user: user!);
//       } else {
//         return _SubscribeContainer(user: user);
//       }
//     } else {
//       return const SizedBox.shrink();
//     }
//   }
// }

// class _SubscribeContainer extends StatelessWidget {
//   const _SubscribeContainer({required this.user});

//   final UserModel? user;

//   @override
//   Widget build(BuildContext context) {
//     return Container(
//       margin: const EdgeInsets.symmetric(vertical: 20),
//       decoration: BoxDecoration(
//         borderRadius: BorderRadius.circular(10),
//         color: Theme.of(context).primaryColor,
//       ),
//       child: ListTile(
//         onTap: () => NextScreen.openBottomSheet(context, const IAPScreen()),
//         trailing: const Icon(FeatherIcons.chevronRight, color: Colors.white),
//         minVerticalPadding: 20,
//         leading: Image.asset(
//           plusImage,
//           height: 40,
//           width: 40,
//         ),
//         title: Text(
//           'iap-title',
//           style: Theme.of(context).textTheme.titleLarge?.copyWith(fontSize: 18, color: Colors.white, fontWeight: FontWeight.w700),
//         ).tr(),
//       ),
//     );
//   }
// }

// class _SubscribedContainer extends StatelessWidget with UserMixin {
//   const _SubscribedContainer({required this.user});

//   final UserModel user;

//   @override
//   Widget build(BuildContext context) {
//     return Container(
//       margin: const EdgeInsets.symmetric(vertical: 20),
//       decoration: BoxDecoration(
//         border: Border.all(width: 0.3, color: Colors.blueGrey),
//         borderRadius: BorderRadius.circular(10),
//       ),
//       child: ListTile(
//         onTap: () => NextScreen.openBottomSheet(context, const IAPScreen()),
//         minVerticalPadding: 20,
//         leading: Image.asset(plusImage, height: 40, width: 40),
//         title: Text(
//           user.subscription!.plan,
//           style: Theme.of(context).textTheme.titleMedium?.copyWith(fontSize: 18, fontWeight: FontWeight.w600),
//         ),
//         subtitle: UserMixin.isExpired(user)
//             ? const Text(
//                 'expired',
//                 style: TextStyle(color: Colors.redAccent),
//               ).tr()
//             : RichText(
//                 text: TextSpan(
//                   text: 'active'.tr().padRight(8),
//                   style: Theme.of(context).textTheme.bodyMedium?.copyWith(
//                         color: Colors.blueAccent,
//                       ),
//                   children: [
//                     const TextSpan(text: '('),
//                     TextSpan(
//                       text: 'expire-in-days'.tr(args: [remainingDays(user).toString()]),
//                       style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.red),
//                     ),
//                     const TextSpan(text: ')')
//                   ],
//                 ),
//               ),
//       ),
//     );
//   }
// }
