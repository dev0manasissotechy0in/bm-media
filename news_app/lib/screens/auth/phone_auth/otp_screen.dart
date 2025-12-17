import 'dart:async';
import 'dart:io';
import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/services/auth_service.dart';
import 'package:news_app/utils/toasts.dart';
import 'package:otp_text_field/otp_text_field.dart';
import 'package:otp_text_field/style.dart';
import 'package:rounded_loading_button_plus/rounded_loading_button.dart';
import '../../../models/user_model.dart';
import '../../../providers/user_data_provider.dart';
import '../../../services/firebase_service_stub.dart';
import '../../../utils/next_screen.dart';
import '../../splash.dart';

class OTPScreen extends ConsumerStatefulWidget {
  const OTPScreen({super.key, required this.verficationId, this.popUpScreen, required this.phoneNumber});

  final String verficationId;
  final bool? popUpScreen;
  final String phoneNumber;

  @override
  ConsumerState<OTPScreen> createState() => _OTPScreenState();
}

class _OTPScreenState extends ConsumerState<OTPScreen> {
  final _btnController = RoundedLoadingButtonController();
  bool _isResendButtonEnabled = false;
  late Timer _timer;
  int _start = 60;
  late String _verficationId;
  final OtpFieldController otpController = OtpFieldController();
  String _otp = '0';

  _handleSubmit(otpCode) async {
    _btnController.start();
    UserCredential? user = await AuthService().signInWithPhoneNumber(_verficationId, otpCode);
    if (user != null) {
      await _validateData(user);
      _btnController.reset();
    } else {
      openToast1('OTP is incorrect');
      _btnController.reset();
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
      navigator.pop();
    }
  }

  _handleResendVerification() async {
    String phoneNumber = widget.phoneNumber;
    try {
      _btnController.start();
      await FirebaseAuth.instance.verifyPhoneNumber(
        phoneNumber: phoneNumber,
        verificationCompleted: (PhoneAuthCredential credential) async {
          final UserCredential userCredential = await FirebaseAuth.instance.signInWithCredential(credential);
          _validateData(userCredential);
        },
        verificationFailed: (FirebaseAuthException e) {
          _btnController.reset(); // Set button to error state
          debugPrint(e.message);
          openToast1(e.message);
        },
        codeSent: (String verificationID, int? resendToken) {
          setState(() {
            _verficationId = verificationID;
          });
          _btnController.reset(); // Set button to success state
          openToast1('Code resent successfully');
          _startTimer();
        },
        codeAutoRetrievalTimeout: (String verificationId) {
          _verficationId = verificationId;
        },
      );
    } catch (e) {
      _btnController.reset(); // Set button to error state
      debugPrint(e.toString());
      openToast(e.toString());
    }
  }

  void _startTimer() {
    setState(() {
      _isResendButtonEnabled = false;
    });
    _start = 60;
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_start == 0) {
        setState(() {
          _isResendButtonEnabled = true;
        });
        timer.cancel();
      } else {
        setState(() {
          _start--;
        });
      }
    });
  }

  @override
  void initState() {
    super.initState();
    _verficationId = widget.verficationId;
    _startTimer();
  }

  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
      ),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'verification-code',
              style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold),
            ).tr(),
            const SizedBox(height: 8),
            Text(
              'verification-message',
              style: Theme.of(context).textTheme.bodyLarge,
            ).tr(args: [widget.phoneNumber]),
            const SizedBox(height: 30),
            OTPTextField(
              controller: otpController,
              length: 6,
              width: MediaQuery.of(context).size.width,
              textFieldAlignment: MainAxisAlignment.spaceAround,
              fieldStyle: FieldStyle.underline,
              otpFieldStyle: OtpFieldStyle(borderColor: Theme.of(context).primaryColor),
              outlineBorderRadius: 15,
              style: const TextStyle(fontSize: 17),
              onChanged: (pin) {
                debugPrint("Changed: $pin");
                setState(() {
                  _otp = pin;
                });
              },
              onCompleted: (pin) {
                _handleSubmit(pin);
              },
            ),
            const SizedBox(height: 40),
            RoundedLoadingButton(
              controller: _btnController,
              animateOnTap: false,
              color: Colors.indigo,
              elevation: 0,
              borderRadius: 25,
              child: Text(
                'submit',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
              ).tr(),
              onPressed: () => _handleSubmit(_otp),
            ),
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                TextButton(
                  onPressed: !_isResendButtonEnabled ? null : () => _handleResendVerification(),
                  child: const Text('resend-code').tr(),
                ),
                Text('00:${_start.toString()}')
              ],
            )
          ],
        ),
      ),
    );
  }
}
