import 'dart:io';
import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
// Phone number input package removed during migration
// import 'package:intl_phone_number_input_perci/intl_phone_number_input_perci.dart';
import 'package:news_app/configs/app_config.dart';
import 'package:news_app/screens/auth/phone_auth/otp_screen.dart';
import 'package:news_app/utils/next_screen.dart';
import 'package:news_app/utils/toasts.dart';
import 'package:rounded_loading_button_plus/rounded_loading_button.dart';
import '../../../models/user_model.dart';
import '../../../providers/user_data_provider.dart';
import '../../../services/firebase_service_stub.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../splash.dart';

// Temporary stub class during migration
class PhoneNumber {
  final String? isoCode;
  String? phoneNumber;
  PhoneNumber({this.isoCode, this.phoneNumber});
}

class PhoneLogin extends ConsumerStatefulWidget {
  const PhoneLogin({super.key, this.popUpScreen});

  final bool? popUpScreen;

  @override
  ConsumerState<PhoneLogin> createState() => _PhoneLoginState();
}

class _PhoneLoginState extends ConsumerState<PhoneLogin> {
  final textController = TextEditingController();
  String initialCountry = AppConfig.intialCountryCodeforPhoneLogin;
  PhoneNumber number = PhoneNumber(isoCode: AppConfig.intialCountryCodeforPhoneLogin);
  final _btnController = RoundedLoadingButtonController();
  String? _verificationId;
  final formKey = GlobalKey<FormState>();

  _handleSendVerification() async {
    if (formKey.currentState!.validate()) {
      formKey.currentState!.save();
      FocusScope.of(context).requestFocus(FocusNode());
      String phoneNumber = number.phoneNumber!;
      try {
        _btnController.start();
        await FirebaseAuth.instance.verifyPhoneNumber(
          phoneNumber: phoneNumber,
          verificationCompleted: (PhoneAuthCredential credential) async {
            // Sign in with verification completed automatically
            final UserCredential userCredential = await FirebaseAuth.instance.signInWithCredential(credential);
            _validateData(userCredential);
            // Navigate to your home screen or handle successful login
          },
          verificationFailed: (FirebaseAuthException e) {
            // Handle verification error
            _btnController.reset(); // Set button to error state
            debugPrint(e.message);
            openToast(e.message);
          },
          codeSent: (String verificationId, int? resendToken) {
            setState(() {
              _verificationId = verificationId;
            });
            _btnController.reset(); // Set button to success state
            NextScreen.replace(
                context,
                OTPScreen(
                  verficationId: _verificationId!,
                  popUpScreen: widget.popUpScreen,
                  phoneNumber: phoneNumber,
                ));
          },
          codeAutoRetrievalTimeout: (String verificationId) {
            setState(() {
              _verificationId = verificationId;
            });
          },
        );
      } catch (e) {
        _btnController.reset(); // Set button to error state
        debugPrint(e.toString());
        openToast(e.toString());
      }
    }
  }

  UserModel _userModel(UserCredential userCredential) {
    final UserModel user = UserModel(
      id: userCredential.user!.uid,
      email: userCredential.user?.phoneNumber ?? 'No Email',
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
        afterSignIn();
      });
    } else {
      afterSignIn();
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
      appBar: AppBar(),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'verify-number',
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold),
            ).tr(),
            const SizedBox(height: 30),
            Form(
              key: formKey,
              child: TextFormField(
                controller: textController,
                decoration: const InputDecoration(
                  labelText: 'Phone Number',
                  hintText: 'Enter phone number with country code',
                  border: OutlineInputBorder(),
                ),
                keyboardType: TextInputType.phone,
                validator: (value) {
                  if (value == null || value.isEmpty) return 'Phone number can not be empty';
                  return null;
                },
                onSaved: (value) {
                  setState(() {
                    number.phoneNumber = value;
                  });
                },
              ),
              /* Original phone number input widget - temporarily disabled
              child: InternationalPhoneNumberInput(
                onInputChanged: (PhoneNumber number) {},
                validator: (value) {
                  if (value!.isEmpty) return 'Phone number can not be empty';
                  return null;
                },
                selectorConfig: const SelectorConfig(
                  selectorType: PhoneInputSelectorType.BOTTOM_SHEET,
                  useBottomSheetSafeArea: true,
                  setSelectorButtonAsPrefixIcon: true,
                  leadingPadding: 20,
                  trailingSpace: false,
                ),
                ignoreBlank: false,
                autoValidateMode: AutovalidateMode.disabled,
                initialValue: number,
                textFieldController: textController,
                formatInput: true,
                keyboardType: const TextInputType.numberWithOptions(signed: true, decimal: true),
                inputBorder: const OutlineInputBorder(),
                autoFocus: true,
                onSaved: (PhoneNumber number) {
                  setState(() {
                    this.number = number;
                  });
                },
              ),
              */
            ),
            const SizedBox(height: 30),
            RoundedLoadingButton(
              controller: _btnController,
              animateOnTap: false,
              color: Theme.of(context).primaryColor,
              elevation: 0,
              borderRadius: 25,
              child: Text(
                'send-otp',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
              ).tr(),
              onPressed: () => _handleSendVerification(),
            )
          ],
        ),
      ),
    );
  }
}
