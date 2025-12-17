import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:line_icons/line_icons.dart';
import 'package:news_app/components/app_logo.dart';
import 'package:news_app/components/languages.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/screens/auth/login.dart';
import 'package:news_app/screens/splash.dart';
import 'package:news_app/utils/next_screen.dart';
import 'package:easy_localization/easy_localization.dart';
import '../configs/features_config.dart';
import '../services/sp_service.dart';
import 'auth/sign_up.dart';

class WelcomeScreen extends ConsumerStatefulWidget {
  const WelcomeScreen({super.key});

  @override
  ConsumerState createState() => _WelcomeScreenState();
}

class _WelcomeScreenState extends ConsumerState<WelcomeScreen> {
  void _onSkipPressed(BuildContext context) async {
    await SPService().setGuestUser().then((value) {
      if (!context.mounted) return;
      NextScreen.replaceAnimation(context, const SplashScreen());
    });
  }

  @override
  Widget build(BuildContext context) {
    final settings = ref.watch(appSettingsProvider);
    return Scaffold(
      appBar: AppBar(
        actions: [
          Visibility(
            visible: settings?.skipLogin ?? true,
            child: TextButton(
              child: const Text('skip').tr(),
              onPressed: () => _onSkipPressed(context),
            ),
          ),
          Visibility(
            visible: isMultilanguageEnbled,
            child: IconButton(
              padding: const EdgeInsets.only(right: 10),
              icon: const Icon(LineIcons.language),
              onPressed: () => NextScreen.openBottomSheet(context, const Languages()),
            ),
          )
        ],
      ),
      bottomNavigationBar: BottomAppBar(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          color: Colors.transparent,
          height: 140,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              SizedBox(
                height: 50,
                width: double.infinity,
                child: FilledButton(
                  onPressed: () => NextScreen.openBottomSheet(context, const LoginScreen()),
                  style: FilledButton.styleFrom(
                    backgroundColor: Theme.of(context).primaryColor,
                  ),
                  child: Text(
                    'sign-in-to-continue',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w600),
                  ).tr(),
                ),
              ),
              Wrap(
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  Text(
                    "no-account",
                    style: Theme.of(context).textTheme.bodyMedium,
                  ).tr(),
                  TextButton(
                      child: Text(
                        'create-account',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600, fontSize: 15),
                      ).tr(),
                      onPressed: () => NextScreen.openBottomSheet(context, const SignUpScreen())),
                ],
              ),
            ],
          )),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              const Image(
                image: AssetImage(splash),
                height: 130,
              ),
              const SizedBox(height: 30),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'welcome-to',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
                  ).tr(),
                  const SizedBox(
                    width: 10,
                  ),
                  const AppLogo(width: 150),
                ],
              ),
              Text(
                'welcome-intro',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w400, fontSize: 18),
                textAlign: TextAlign.center,
              ).tr()
            ],
          ),
        ),
      ),
    );
  }
}
