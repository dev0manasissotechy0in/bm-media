import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/branding_provider.dart';
import '../configs/app_assets.dart';

class AppLogo extends ConsumerWidget {
  const AppLogo({super.key, this.width, this.showText = true});
  final double? width;
  final bool showText;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final brandingAsync = ref.watch(brandingNotifierProvider);
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return brandingAsync.when(
      data: (branding) {
        // If app name is available and showText is true, display text
        if (showText && branding.appName.isNotEmpty) {
          return Text(
            branding.appName,
            style: TextStyle(
              // fontFamily: 'Westack', // Uncomment when font files are added
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: isDark ? Colors.white : Colors.black87,
              letterSpacing: -0.5,
            ),
          );
        }
        
        // Otherwise, show logo image
        final String appLogo = isDark ? logoDark : logo;
        return Image.asset(
          appLogo,
          height: 60,
          width: width ?? 110,
        );
      },
      loading: () {
        // Show logo while loading
        final String appLogo = isDark ? logoDark : logo;
        return Image.asset(
          appLogo,
          height: 60,
          width: width ?? 110,
        );
      },
      error: (error, stack) {
        // Show logo on error
        final String appLogo = isDark ? logoDark : logo;
        return Image.asset(
          appLogo,
          height: 60,
          width: width ?? 110,
        );
      },
    );
  }
}
