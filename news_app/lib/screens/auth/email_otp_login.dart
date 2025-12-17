import 'package:flutter/material.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:rounded_loading_button_plus/rounded_loading_button.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../components/privacy_info.dart';
import '../../services/email_auth_service.dart';
import '../../utils/snackbars.dart';
import '../splash.dart';
import '../../core/home.dart';
import '../../utils/next_screen.dart';
import '../../providers/user_data_provider.dart';
import 'dart:async';

class EmailOTPLogin extends ConsumerStatefulWidget {
  const EmailOTPLogin({super.key, this.popUpScreen});

  final bool? popUpScreen;

  @override
  ConsumerState<EmailOTPLogin> createState() => _EmailOTPLoginState();
}

class _EmailOTPLoginState extends ConsumerState<EmailOTPLogin> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _nameController = TextEditingController();
  final _otpController = TextEditingController();
  final _sendOtpBtnController = RoundedLoadingButtonController();
  final _verifyOtpBtnController = RoundedLoadingButtonController();
  
  final EmailAuthService _emailAuthService = EmailAuthService();
  
  bool _otpSent = false;
  int _expiryMinutes = 10;
  Timer? _timer;
  int _remainingSeconds = 0;

  @override
  void dispose() {
    _timer?.cancel();
    _emailController.dispose();
    _nameController.dispose();
    _otpController.dispose();
    super.dispose();
  }

  void _startTimer() {
    _remainingSeconds = _expiryMinutes * 60;
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds > 0) {
        setState(() => _remainingSeconds--);
      } else {
        timer.cancel();
      }
    });
  }

  String _getTimerText() {
    final minutes = _remainingSeconds ~/ 60;
    final seconds = _remainingSeconds % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  Future<void> _handleSendOTP() async {
    if (_formKey.currentState!.validate()) {
      _formKey.currentState!.save();
      _sendOtpBtnController.start();

      final result = await _emailAuthService.sendOTP(
        email: _emailController.text.trim(),
        name: _nameController.text.trim(),
        userType: 'user',
      );

      _sendOtpBtnController.reset();

      if (result['success'] == true) {
        // Handle new API response format with data object
        final data = result['data'] ?? result;
        
        setState(() {
          _otpSent = true;
          _expiryMinutes = data['expires_in_minutes'] ?? 10;
        });
        _startTimer();
        
        if (mounted) {
          // Show success message
          String message = data['message'] ?? result['message'] ?? 'OTP sent successfully';
          
          // In debug mode, show OTP in snackbar
          if (data['debug'] != null && data['debug']['otp'] != null) {
            message += '\n\nDEBUG OTP: ${data['debug']['otp']}';
            debugPrint('🔐 OTP for ${_emailController.text}: ${data['debug']['otp']}');
          }
          
          openSnackbar(context, message);
        }
      } else {
        if (mounted) {
          openSnackbarFailure(context, result['message'] ?? 'Failed to send OTP');
        }
      }
    }
  }

  Future<void> _handleVerifyOTP() async {
    if (_otpController.text.trim().length != 6) {
      openSnackbarFailure(context, 'Please enter a valid 6-digit OTP');
      return;
    }

    _verifyOtpBtnController.start();

    final result = await _emailAuthService.verifyOTP(
      email: _emailController.text.trim(),
      otp: _otpController.text.trim(),
      name: _nameController.text.trim(),
      userType: 'user',
    );

    if (result['success'] == true) {
      _verifyOtpBtnController.success();
      
      // Save user data and token locally
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', result['token']);
      await prefs.setString('user_id', result['user']['id'].toString());
      await prefs.setString('user_email', result['user']['email']);
      await prefs.setString('user_name', result['user']['full_name'] ?? '');
      if (result['user']['profile_photo'] != null) {
        await prefs.setString('user_image', result['user']['profile_photo']);
      }
      debugPrint('✅ User logged in: ${result['user']}');
      debugPrint('✅ Auth token saved: ${result['token']}');
      
      await _afterSignIn();
    } else {
      _verifyOtpBtnController.reset();
      if (mounted) {
        openSnackbarFailure(context, result['message']);
      }
    }
  }

  Future<void> _afterSignIn() async {
    _timer?.cancel();
    
    // Mark as guest user (email login doesn't use Firebase)
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('is_guest_user', true);
    
    if (widget.popUpScreen == null || widget.popUpScreen == false) {
      if (mounted) {
        // Navigate directly to Home instead of SplashScreen
        NextScreen.closeOthersAnimation(context, const Home());
      }
    } else {
      if (mounted) {
        final navigator = Navigator.of(context);
        await ref.read(userDataProvider.notifier).getData();
        navigator.pop();
      }
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
        title: const Text('Email Login').tr(),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(25),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Login with Email',
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                      fontSize: 28,
                    ),
              ),
              const SizedBox(height: 5),
              Text(
                'We\'ll send you a verification code',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      color: Theme.of(context).colorScheme.secondary,
                    ),
              ),
              const SizedBox(height: 30),
              
              // Email Field
              TextFormField(
                controller: _emailController,
                enabled: !_otpSent,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                  hintText: 'Enter your email',
                  label: const Text('Email'),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(3)),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.clear, size: 20),
                    onPressed: () => _emailController.clear(),
                  ),
                ),
                validator: (value) {
                  if (value == null || value.isEmpty) {
                    return 'Email is required';
                  }
                  if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                    return 'Enter a valid email';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 20),
              
              // Name Field (Optional)
              TextFormField(
                controller: _nameController,
                enabled: !_otpSent,
                keyboardType: TextInputType.name,
                decoration: InputDecoration(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                  hintText: 'Enter your name (optional)',
                  label: const Text('Name'),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(3)),
                  suffixIcon: IconButton(
                    icon: const Icon(Icons.clear, size: 20),
                    onPressed: () => _nameController.clear(),
                  ),
                ),
              ),
              
              if (!_otpSent) ...[
                const SizedBox(height: 30),
                RoundedLoadingButton(
                  animateOnTap: false,
                  controller: _sendOtpBtnController,
                  onPressed: _handleSendOTP,
                  width: MediaQuery.sizeOf(context).width,
                  color: Theme.of(context).primaryColor,
                  elevation: 0,
                  child: Text(
                    'Send OTP',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                  ),
                ),
              ],
              
              if (_otpSent) ...[
                const SizedBox(height: 20),
                
                // OTP Field
                TextFormField(
                  controller: _otpController,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  decoration: InputDecoration(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                    hintText: 'Enter 6-digit OTP',
                    label: const Text('OTP Code'),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(3)),
                    suffixIcon: IconButton(
                      icon: const Icon(Icons.clear, size: 20),
                      onPressed: () => _otpController.clear(),
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                
                // Timer
                if (_remainingSeconds > 0)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.timer_outlined, size: 18),
                      const SizedBox(width: 5),
                      Text(
                        'Code expires in ${_getTimerText()}',
                        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                              color: Colors.orange,
                              fontWeight: FontWeight.w500,
                            ),
                      ),
                    ],
                  )
                else
                  Text(
                    'OTP expired! Please request a new code.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.red,
                          fontWeight: FontWeight.w500,
                        ),
                    textAlign: TextAlign.center,
                  ),
                
                const SizedBox(height: 20),
                
                // Verify Button
                RoundedLoadingButton(
                  animateOnTap: false,
                  controller: _verifyOtpBtnController,
                  onPressed: _remainingSeconds > 0 ? _handleVerifyOTP : null,
                  width: MediaQuery.sizeOf(context).width,
                  color: _remainingSeconds > 0 
                      ? Theme.of(context).primaryColor 
                      : Colors.grey,
                  elevation: 0,
                  child: Text(
                    'Verify & Login',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                  ),
                ),
                
                const SizedBox(height: 15),
                
                // Resend OTP
                Center(
                  child: TextButton(
                    onPressed: () {
                      setState(() {
                        _otpSent = false;
                        _otpController.clear();
                        _timer?.cancel();
                      });
                    },
                    child: const Text(
                      'Change Email / Resend OTP',
                      style: TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ),
              ],
              
              const SizedBox(height: 20),
              const PrivacyInfo(),
            ],
          ),
        ),
      ),
    );
  }
}
