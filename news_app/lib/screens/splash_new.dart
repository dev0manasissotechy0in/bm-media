import 'dart:io';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/screens/intro.dart';
import 'package:news_app/screens/welcome.dart';
import 'package:simple_animations/simple_animations.dart';
import 'package:url_launcher/url_launcher.dart';
import '../components/splash_logo.dart';
import '../configs/features_config.dart';
import '../core/home.dart';
import '../models/app_settings_model.dart';
import '../models/splash_settings.dart';
import '../providers/app_settings_provider.dart';
import '../providers/user_data_provider.dart';
import '../services/auth_service.dart';
import '../services/sp_service.dart';
import '../services/deep_link_service.dart';
import '../services/in_app_update_service.dart';
import '../services/splash_api_service.dart';
import '../utils/next_screen.dart';
import '../utils/no_license.dart';
import 'auth/no_user.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  SplashSettings? _splashSettings;
  bool _splashLoaded = false;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }

  // Initialize app with splash settings
  Future<void> _initializeApp() async {
    // Initialize services first
    _initializeServices();

    // Load splash settings from API
    try {
      String platform = 'all';
      if (Platform.isAndroid) {
        platform = 'android';
      } else if (Platform.isIOS) {
        platform = 'ios';
      }

      final settings = await SplashApiService.getSplashSettings(platform: platform);
      
      if (mounted) {
        setState(() {
          _splashSettings = settings;
          _splashLoaded = true;
        });

        // If showing dynamic splash with duration, wait then proceed
        if (settings?.showDynamic == true && settings?.dynamicSplash != null) {
          final duration = settings!.dynamicSplash!.displayDuration;
          await Future.delayed(Duration(seconds: duration));
          if (mounted) {
            _proceedToApp();
          }
        } else {
          // Default splash - proceed after delay
          await Future.delayed(const Duration(seconds: 2));
          if (mounted) {
            _proceedToApp();
          }
        }
      }
    } catch (e) {
      debugPrint('Error loading splash: $e');
      // Fallback: proceed after delay
      await Future.delayed(const Duration(seconds: 2));
      if (mounted) {
        _proceedToApp();
      }
    }
  }

  // Initialize deep links and in-app updates
  Future<void> _initializeServices() async {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        DeepLinkService().init(context, ref);
        debugPrint('✅ Deep Link Service initialized');
        
        InAppUpdateService().checkForCriticalUpdates(context);
        debugPrint('✅ In-App Update Service initialized');
      }
    });
  }

  // Getting required settings data and navigating
  Future<void> _proceedToApp() async {
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
        // if user not found
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
        debugPrint('no setting data found');
        NextScreen.replaceAnimation(context, const WelcomeScreen());
      }
    }
  }

  // Handle button click on dynamic splash
  Future<void> _handleSplashButtonClick() async {
    final dynamicSplash = _splashSettings?.dynamicSplash;
    if (dynamicSplash == null) return;

    // Track click
    await SplashApiService.trackClick();

    // Handle action URL
    final actionUrl = dynamicSplash.buttonAction;
    if (actionUrl.isNotEmpty) {
      try {
        final uri = Uri.parse(actionUrl);
        if (await canLaunchUrl(uri)) {
          await launchUrl(uri, mode: LaunchMode.externalApplication);
        }
      } catch (e) {
        debugPrint('Error launching URL: $e');
      }
    }

    // Proceed to app
    if (mounted) {
      _proceedToApp();
    }
  }

  // Convert hex color string to Color
  Color _hexToColor(String hexString) {
    final buffer = StringBuffer();
    if (hexString.length == 7) buffer.write('ff');
    buffer.write(hexString.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  // Build default splash screen
  Widget _buildDefaultSplash() {
    if (_splashSettings == null) {
      // Fallback while loading
      return const Center(child: SplashLogo());
    }

    final defaultSplash = _splashSettings!.defaultSplash;
    final bgColor = _hexToColor(defaultSplash.backgroundColor);
    final textColor = _hexToColor(defaultSplash.textColor);

    Widget logoWidget = Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Logo image or fallback
        Image.network(
          'http://localhost/${defaultSplash.logo}',
          width: 150,
          height: 150,
          errorBuilder: (context, error, stackTrace) {
            return const SplashLogo();
          },
        ),
        const SizedBox(height: 24),
        // Tagline
        Text(
          defaultSplash.tagline,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w500,
            color: textColor,
          ),
          textAlign: TextAlign.center,
        ),
      ],
    );

    // Apply animation based on type
    switch (defaultSplash.animationType) {
      case 'fade':
        return Container(
          color: bgColor,
          child: MirrorAnimationBuilder<double>(
            curve: Curves.easeInOut,
            tween: Tween(begin: 0.0, end: 1.0),
            duration: const Duration(milliseconds: 900),
            builder: (context, value, _) {
              return Opacity(opacity: value, child: logoWidget);
            },
          ),
        );
      case 'slide':
        return Container(
          color: bgColor,
          child: MirrorAnimationBuilder<double>(
            curve: Curves.easeOut,
            tween: Tween(begin: -100.0, end: 0.0),
            duration: const Duration(milliseconds: 900),
            builder: (context, value, _) {
              return Transform.translate(
                offset: Offset(0, value),
                child: logoWidget,
              );
            },
          ),
        );
      case 'zoom':
        return Container(
          color: bgColor,
          child: MirrorAnimationBuilder<double>(
            curve: Curves.easeInOut,
            tween: Tween(begin: 100.0, end: 200.0),
            duration: const Duration(milliseconds: 900),
            builder: (context, value, _) {
              return Center(
                child: SizedBox(
                  height: value,
                  width: value,
                  child: logoWidget,
                ),
              );
            },
          ),
        );
      case 'bounce':
        return Container(
          color: bgColor,
          child: MirrorAnimationBuilder<double>(
            curve: Curves.bounceOut,
            tween: Tween(begin: 0.0, end: 1.0),
            duration: const Duration(milliseconds: 1200),
            builder: (context, value, _) {
              return Transform.scale(scale: value, child: logoWidget);
            },
          ),
        );
      default:
        // No animation
        return Container(
          color: bgColor,
          child: Center(child: logoWidget),
        );
    }
  }

  // Build dynamic splash screen
  Widget _buildDynamicSplash() {
    if (_splashSettings?.dynamicSplash == null) {
      return _buildDefaultSplash();
    }

    final dynamicSplash = _splashSettings!.dynamicSplash!;
    final bgColor = _hexToColor(dynamicSplash.backgroundColor);
    final textColor = _hexToColor(dynamicSplash.textColor);

    return Container(
      color: bgColor,
      child: SafeArea(
        child: Stack(
          children: [
            // Full-screen splash image
            if (dynamicSplash.image.isNotEmpty)
              Positioned.fill(
                child: Image.network(
                  'http://localhost/${dynamicSplash.image}',
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) {
                    return Container(color: bgColor);
                  },
                ),
              ),
            
            // Content overlay
            Positioned(
              bottom: 80,
              left: 24,
              right: 24,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Title
                  if (dynamicSplash.title.isNotEmpty)
                    Text(
                      dynamicSplash.title,
                      style: TextStyle(
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        color: textColor,
                        shadows: [
                          Shadow(
                            offset: const Offset(0, 1),
                            blurRadius: 4,
                            color: Colors.black.withOpacity(0.3),
                          ),
                        ],
                      ),
                      textAlign: TextAlign.center,
                    ),
                  
                  const SizedBox(height: 12),
                  
                  // Subtitle
                  if (dynamicSplash.subtitle.isNotEmpty)
                    Text(
                      dynamicSplash.subtitle,
                      style: TextStyle(
                        fontSize: 16,
                        color: textColor.withOpacity(0.9),
                        shadows: [
                          Shadow(
                            offset: const Offset(0, 1),
                            blurRadius: 3,
                            color: Colors.black.withOpacity(0.2),
                          ),
                        ],
                      ),
                      textAlign: TextAlign.center,
                    ),
                  
                  const SizedBox(height: 24),
                  
                  // Action button
                  if (dynamicSplash.buttonText.isNotEmpty)
                    ElevatedButton(
                      onPressed: _handleSplashButtonClick,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: textColor,
                        foregroundColor: bgColor,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 48,
                          vertical: 16,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(30),
                        ),
                        elevation: 4,
                      ),
                      child: Text(
                        dynamicSplash.buttonText,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                ],
              ),
            ),
            
            // Skip button (top-right)
            Positioned(
              top: 16,
              right: 16,
              child: TextButton(
                onPressed: _proceedToApp,
                style: TextButton.styleFrom(
                  backgroundColor: Colors.black.withOpacity(0.3),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 8,
                  ),
                ),
                child: Text(
                  'Skip',
                  style: TextStyle(
                    color: textColor,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (!_splashLoaded) {
      // Show loading fallback
      return const Scaffold(
        body: Center(child: SplashLogo()),
      );
    }

    return Scaffold(
      body: _splashSettings?.showDynamic == true
          ? _buildDynamicSplash()
          : _buildDefaultSplash(),
    );
  }
}
