import 'dart:io';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:icons_plus/icons_plus.dart';
import 'package:news_app/screens/auth/phone_auth/phone_login.dart';
import 'package:news_app/utils/next_screen.dart';
import 'package:rounded_loading_button_plus/rounded_loading_button.dart';
import '../../configs/features_config.dart';
import '../../models/user_model.dart';
import '../../providers/app_config_provider.dart';
import '../../services/auth_service.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/snackbars.dart';

class SocialLogins extends ConsumerStatefulWidget {
  const SocialLogins({super.key, required this.afterSignIn, this.popUpScreen});
  final VoidCallback afterSignIn;
  final bool? popUpScreen;

  @override
  ConsumerState<SocialLogins> createState() => _SocialLoginsState();
}

class _SocialLoginsState extends ConsumerState<SocialLogins> {
  final googleCtlr = RoundedLoadingButtonController();
  final fbController = RoundedLoadingButtonController();
  final appleController = RoundedLoadingButtonController();

  UserModel _userModel(UserCredential userCredential) {
    final UserModel user = UserModel(
      id: userCredential.user!.uid,
      email: userCredential.user?.email ?? 'No Email',
      name: userCredential.user!.displayName ?? 'No Name',
      createdAt: DateTime.now().toUtc(),
      imageUrl: userCredential.user?.photoURL,
      platform: Platform.isAndroid ? 'Android' : 'iOS',
    );
    return user;
  }

  _validateData(UserCredential userCredential) async {
    bool userExists = await FirebaseService().isUserExists(userCredential.user!.uid);
    if (!userExists) {
      await FirebaseService().saveUserData(_userModel(userCredential)).then((value) async {
        await FirebaseService().updateUserStats();
        widget.afterSignIn();
      });
    } else {
      widget.afterSignIn();
    }
  }

  _handleGoogleSignIn() async {
    googleCtlr.start();
    UserCredential? userCredential = await AuthService().signInWithGoogle().onError((error, stackTrace) {
      googleCtlr.reset();
      debugPrint('google sign in error: $error');
      return null;
    });
    if (userCredential != null && userCredential.user != null) {
      _validateData(userCredential);
    } else {
      googleCtlr.reset();
    }
  }

  _handleFacebookSignIn() async {
    fbController.start();
    UserCredential? userCredential = await AuthService().signInWithFacebook().onError((error, stackTrace) {
      fbController.reset();
      debugPrint('fb sign in error: $error');
      return null;
    });
    if (userCredential != null && userCredential.user != null) {
      _validateData(userCredential);
    } else {
      fbController.reset();
      if (!mounted) return;
      openSnackbarFailure(context, 'Error on Facebbok Login. Please try again!');
    }
  }

  _handleAppleSignIn() async {
    appleController.start();
    UserCredential? userCredential = await AuthService().signInWithApple().onError((error, stackTrace) {
      appleController.reset();
      debugPrint('apple sign in error: $error');
      return null;
    });
    if (userCredential != null && userCredential.user != null) {
      _validateData(userCredential);
    } else {
      appleController.reset();
    }
  }

  @override
  Widget build(BuildContext context) {
    final appConfig = ref.watch(appConfigProvider);
    
    return Column(
      children: [
        // const SizedBox(height: 30),
        Visibility(
          visible: appConfig.googleLoginEnabled,
          child: RoundedLoadingButton(
            controller: googleCtlr,
            animateOnTap: false,
            color: Colors.blueAccent,
            elevation: 0,
            borderRadius: 25,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(FontAwesome.google_brand, color: Colors.white),
                const SizedBox(width: 15),
                Text(
                  'Sign In With Google',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
                )
              ],
            ),
            onPressed: () => _handleGoogleSignIn(),
          ),
        ),
        Visibility(
          visible: appConfig.facebookLoginEnabled,
          child: Padding(
            padding: const EdgeInsets.only(top: 12),
            child: RoundedLoadingButton(
              controller: fbController,
              animateOnTap: false,
              color: Colors.indigo,
              elevation: 0,
              borderRadius: 25,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(FontAwesome.facebook_brand, color: Colors.white),
                  const SizedBox(width: 15),
                  Text(
                    'Sign In With Facebook',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
                  )
                ],
              ),
              onPressed: () => _handleFacebookSignIn(),
            ),
          ),
        ),
        Visibility(
          visible: isAppleLoginEnabled && Platform.isIOS,
          child: Padding(
            padding: const EdgeInsets.only(top: 12),
            child: RoundedLoadingButton(
                controller: appleController,
                animateOnTap: false,
                color: Colors.grey.shade800,
                elevation: 0,
                borderRadius: 25,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(FontAwesome.apple_brand, color: Colors.white),
                    const SizedBox(width: 15),
                    Text(
                      'Sign In With Apple',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
                    )
                  ],
                ),
                onPressed: () => _handleAppleSignIn()),
          ),
        ),
        Visibility(
          visible: isPhoneNumberLoginEnabled,
          child: Padding(
            padding: const EdgeInsets.only(top: 12),
            child: RoundedLoadingButton(
              controller: RoundedLoadingButtonController(),
              animateOnTap: false,
              color: Colors.grey.shade800,
              elevation: 0,
              borderRadius: 25,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(FontAwesome.phone_solid, color: Colors.white),
                  const SizedBox(width: 15),
                  Text(
                    'Sign In With Mobile',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
                  )
                ],
              ),
              onPressed: () => NextScreen.normal(context, PhoneLogin(popUpScreen: widget.popUpScreen)),
            ),
          ),
        ),
      ],
    );
  }
}
