import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:line_icons/line_icons.dart';
import 'package:rounded_loading_button_plus/rounded_loading_button.dart';
import '../../components/privacy_info.dart';
import '../../providers/user_data_provider.dart';
import '../../services/auth_service.dart';
import '../../utils/next_screen.dart';
import '../splash.dart';
import 'email_otp_login.dart';
import 'reset_password.dart';
import 'sign_up.dart';
import 'social_logins.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key, this.popUpScreen});

  final bool? popUpScreen;

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  var formKey = GlobalKey<FormState>();
  var emailCtlr = TextEditingController();
  var passwordCtrl = TextEditingController();
  final _btnController = RoundedLoadingButtonController();

  bool offsecureText = true;
  IconData lockIcon = LineIcons.lock;

  Future _handleLoginWithUsernamePassword() async {
    if (formKey.currentState!.validate()) {
      formKey.currentState!.save();
      _btnController.start();
      
      // Backend auth returns true on success
      final success = await AuthService().loginWithEmailPassword(context, emailCtlr.text.trim(), passwordCtrl.text);
      
      if (success) {
        _btnController.success();
        // Refresh user data after successful login
        await ref.read(userDataProvider.notifier).getData();
        afterSignIn();
      } else {
        _btnController.reset();
      }
    }
  }

  void _onlockPressed() {
    if (offsecureText == true) {
      setState(() {
        offsecureText = false;
        lockIcon = LineIcons.lockOpen;
      });
    } else {
      setState(() {
        offsecureText = true;
        lockIcon = LineIcons.lock;
      });
    }
  }

  void afterSignIn() async {
    if (widget.popUpScreen == null || widget.popUpScreen == false) {
      NextScreen.closeOthersAnimation(context, const SplashScreen());
    } else {
      final navigator = Navigator.of(context);
      await ref.read(userDataProvider.notifier).getData();
      navigator.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: true,
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.only(left: 25, right: 25, top: 20, bottom: 50),
        child: Form(
          key: formKey,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.start,
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'login',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold, fontSize: 28),
              ).tr(),
              const SizedBox(
                height: 5,
              ),
              Text(
                'login-to-access-features',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Theme.of(context).colorScheme.secondary),
              ).tr(),
              const SizedBox(height: 20),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Email OTP Login - Primary Method
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 20),
                    child: ElevatedButton.icon(
                      onPressed: () => NextScreen.iOS(context, EmailOTPLogin(popUpScreen: widget.popUpScreen)),
                      icon: const Icon(Icons.email_outlined, color: Colors.white),
                      label: Text(
                        'Continue with Email OTP',
                        style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Theme.of(context).primaryColor,
                        padding: const EdgeInsets.symmetric(vertical: 15),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(3),
                        ),
                      ),
                    ),
                  ),
                  
                  Container(
                    alignment: Alignment.center,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: const Text(
                      '------ OR ------',
                      style: TextStyle(color: Colors.blueGrey),
                    ),
                  ),
                  
                  // Traditional Email/Password Login (Collapsed by default)
                  ExpansionTile(
                    title: const Text(
                      'Login with Password',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                    leading: const Icon(Icons.lock_outline),
                    children: [
                      const SizedBox(height: 10),
                      TextFormField(
                        decoration: InputDecoration(
                            contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                            hintText: 'enter-email'.tr(),
                            label: const Text('email').tr(),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(3),
                            ),
                            suffixIcon: IconButton(
                              icon: const Icon(
                                Icons.clear,
                                size: 20,
                              ),
                              onPressed: () => emailCtlr.clear(),
                            )),
                        controller: emailCtlr,
                        keyboardType: TextInputType.emailAddress,
                        validator: (value) {
                          if (value!.isEmpty) return 'Email is required';
                          return null;
                        },
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        decoration: InputDecoration(
                            contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                            hintText: 'enter-password'.tr(),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(3),
                            ),
                            label: const Text('password').tr(),
                            suffixIcon: IconButton(
                              padding: const EdgeInsets.all(0),
                              style: IconButton.styleFrom(padding: const EdgeInsets.all(0)),
                              icon: Icon(
                                lockIcon,
                                size: 20,
                              ),
                              onPressed: () => _onlockPressed(),
                            )),
                        controller: passwordCtrl,
                        obscureText: offsecureText,
                        keyboardType: TextInputType.visiblePassword,
                        validator: (value) {
                          if (value!.isEmpty) return 'Password is required';
                          return null;
                        },
                      ),
                      Container(
                        alignment: Alignment.centerRight,
                        child: TextButton(
                          child: const Text(
                            'forgot-password',
                            style: TextStyle(fontWeight: FontWeight.w600, color: Colors.blueAccent),
                          ).tr(),
                          onPressed: () => NextScreen.iOS(context, const ResetPassword()),
                        ),
                      ),
                      RoundedLoadingButton(
                        animateOnTap: false,
                        controller: _btnController,
                        onPressed: () => _handleLoginWithUsernamePassword(),
                        width: MediaQuery.sizeOf(context).width * 1.0,
                        color: Theme.of(context).primaryColor,
                        elevation: 0,
                        child: Text(
                          'login',
                          style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600, color: Colors.white),
                        ).tr(),
                      ),
                      const SizedBox(height: 15),
                    ],
                  ),
                  Container(
                    width: double.infinity,
                    alignment: Alignment.center,
                    child: Wrap(
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        Text(
                          "no-account",
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Theme.of(context).colorScheme.secondary),
                        ).tr(),
                        TextButton(
                            child: Text(
                              'Sign up with Email OTP',
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600, fontSize: 15),
                            ),
                            onPressed: () => NextScreen.replace(context, EmailOTPLogin(popUpScreen: widget.popUpScreen))),
                      ],
                    ),
                  ),
                  Container(
                    alignment: Alignment.center,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: const Text(
                      'Other sign-in options',
                      style: TextStyle(color: Colors.blueGrey, fontSize: 12),
                    ),
                  ),
                  
                  SocialLogins(
                    afterSignIn: afterSignIn,
                    popUpScreen: widget.popUpScreen,
                  ),
                  const PrivacyInfo(),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
