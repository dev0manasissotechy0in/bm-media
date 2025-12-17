import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/screens/intro.dart';
import 'package:news_app/screens/welcome.dart';
import 'package:simple_animations/simple_animations.dart';
import '../components/splash_logo.dart';
import '../configs/features_config.dart';
import '../core/home.dart';
import '../models/app_settings_model.dart';
import '../providers/app_settings_provider.dart';
import '../providers/user_data_provider.dart';
import '../services/auth_service.dart';
import '../services/sp_service.dart';
import '../services/deep_link_service.dart';
import '../services/in_app_update_service.dart';
import '../utils/next_screen.dart';
import '../utils/no_license.dart';
import 'auth/no_user.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  // Getting required settings data
  _getRequiredData() async {
    final user = FirebaseAuth.instance.currentUser;
    if (user != null) {
      // Signed In
      await ref.read(userDataProvider.notifier).getData();
      final userData = ref.read(userDataProvider);
      if (userData != null) {
        if (ref.read(appSettingsProvider) == null) {
          await ref.read(appSettingsProvider.notifier).getData();
        }
        if (!mounted) return;

        // Checking license
        final settings = ref.read(appSettingsProvider);
        final bool isIntroDone = await SPService().isIntroDone();

        if (settings?.license != LicenseType.none) {
          if (settings?.onBoarding == true && !isIntroDone) {
            if (!mounted) return;
            NextScreen.replaceAnimation(context, const IntroScreen());
          } else {
            if (!mounted) return;
            NextScreen.replaceAnimation(context, const Home());
          }
        } else {
          if (!mounted) return;
          NextScreen.openBottomSheet(context, const NoLicenseFound());
        }
      } else {
        // if user not fould
        await AuthService().userLogOut();
        await AuthService().googleLogout();
        if (!mounted) return;
        NextScreen.replace(context, const NoUserFound());
      }
    } else {
      // Signed Out
      await ref.read(appSettingsProvider.notifier).getData();
      final settings = ref.read(appSettingsProvider);
      final bool isGuestUser = await SPService().isGuestUser();
      final bool isIntroDone = await SPService().isIntroDone();

      if (settings != null) {
        // Checking license
        if (settings.license != LicenseType.none) {
          // Guest User
          if (isGuestUser) {
            // Intro Check
            if (settings.onBoarding == true && !isIntroDone) {
              if (!mounted) return;
              NextScreen.replaceAnimation(context, const IntroScreen());
            } else {
              // Going home. Intro done or disabled
              if (!mounted) return;
              NextScreen.replaceAnimation(context, const Home());
            }
          } else {
            if (!mounted) return;
            NextScreen.replaceAnimation(context, const WelcomeScreen());
          }
        } else {
          if (!mounted) return;
          NextScreen.openBottomSheet(context, const NoLicenseFound());
        }
      } else {
        if (!mounted) return;
        debugPrint('no settting data found');
        NextScreen.replaceAnimation(context, const WelcomeScreen());
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _initializeServices();
    _getRequiredData();
  }

  // Initialize deep links and in-app updates
  Future<void> _initializeServices() async {
    // Initialize deep link service
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        DeepLinkService().init(context, ref);
        debugPrint('✅ Deep Link Service initialized');
        
        // Check for app updates
        InAppUpdateService().checkForCriticalUpdates(context);
        debugPrint('✅ In-App Update Service initialized');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: !splashIconAnimationEnabled
          ? const Center(child: SplashLogo())
          : MirrorAnimationBuilder<double>(
              curve: Curves.easeInOut,
              tween: Tween(begin: 100.0, end: 200),
              duration: const Duration(milliseconds: 900),
              builder: (context, value, _) {
                return Center(
                  child: SizedBox(
                    height: value,
                    width: value,
                    child: const SplashLogo(),
                  ),
                );
              },
            ),
    );
  }
}
