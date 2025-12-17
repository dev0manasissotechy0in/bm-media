import 'dart:convert';
import 'dart:math';
import 'package:crypto/crypto.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
// Removed social login packages during migration
// import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
import 'package:fluttertoast/fluttertoast.dart';
// import 'package:google_sign_in/google_sign_in.dart';
// import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import '../utils/snackbars.dart';
import 'api_service.dart';

class AuthService {
  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;
  Stream<User?> userSteam = FirebaseAuth.instance.authStateChanges();
  final user = FirebaseAuth.instance.currentUser;
  // final GoogleSignIn googleSignIn = GoogleSignIn();

  Future<UserCredential?> signInWithPhoneNumber(String verficationId, String otpCode) async {
    UserCredential? user;

    try {
      final AuthCredential credential = PhoneAuthProvider.credential(
        verificationId: verficationId,
        smsCode: otpCode,
      );
      user = await _firebaseAuth.signInWithCredential(credential);
      debugPrint('Successfully signed in UID: ${user.credential}');
    } catch (e) {
      debugPrint('Failed to sign in with phone number: $e');
    }
    return user;
  }

  Future<bool> loginWithEmailPassword(BuildContext context, String email, String password) async {
    try {
      // Use backend MySQL authentication instead of Firebase
      final user = await ApiService().loginUser(email, password);
      
      if (user != null) {
        // Successfully logged in via backend
        debugPrint('✅ Successfully logged in via backend: ${user.email}');
        
        // Store user data in shared preferences
        final sp = await SharedPreferences.getInstance();
        await sp.setString('user_id', user.id);
        await sp.setString('user_email', user.email);
        await sp.setString('user_name', user.name);
        if (user.imageUrl != null) {
          await sp.setString('user_image', user.imageUrl!);
        }
        
        debugPrint('✅ User data saved to SharedPreferences');
        return true;
      } else {
        if (!context.mounted) return false;
        openSnackbarFailure(context, 'Invalid email or password');
        return false;
      }
    } catch (e) {
      debugPrint('❌ Login error: $e');
      if (!context.mounted) return false;
      openSnackbarFailure(context, 'Login failed. Please try again.');
      return false;
    }
  }

  Future<UserCredential?> signInWithGoogle() async {
    // Google Sign-In temporarily disabled during migration
    Fluttertoast.showToast(msg: 'Google Sign-In is temporarily unavailable');
    return null;
    /* 
    final GoogleSignInAccount? googleUser = await googleSignIn.signIn().catchError((error) {
      return null;
    });
    if (googleUser == null) {
      return null;
    }
    final GoogleSignInAuthentication googleAuth = await googleUser.authentication;
    final credential = GoogleAuthProvider.credential(
      accessToken: googleAuth.accessToken,
      idToken: googleAuth.idToken,
    );
    return await _firebaseAuth.signInWithCredential(credential);
    */
  }

  Future<UserCredential?> signInWithFacebook() async {
    // Facebook Sign-In temporarily disabled during migration
    Fluttertoast.showToast(msg: 'Facebook Sign-In is temporarily unavailable');
    return null;
    /*
    final LoginResult loginResult = await FacebookAuth.instance.login();
    final OAuthCredential facebookAuthCredential = FacebookAuthProvider.credential(loginResult.accessToken!.tokenString);
    return await _firebaseAuth.signInWithCredential(facebookAuthCredential);
    */
  }

  String generateNonce([int length = 32]) {
    const charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVXYZabcdefghijklmnopqrstuvwxyz-._';
    final random = Random.secure();
    return List.generate(length, (_) => charset[random.nextInt(charset.length)]).join();
  }

  /// Returns the sha256 hash of [input] in hex notation.
  String sha256ofString(String input) {
    final bytes = utf8.encode(input);
    final digest = sha256.convert(bytes);
    return digest.toString();
  }

  Future<UserCredential?> signInWithApple() async {
    // Apple Sign-In temporarily disabled during migration
    Fluttertoast.showToast(msg: 'Apple Sign-In is temporarily unavailable');
    return null;
    /*
    final rawNonce = generateNonce();
    final nonce = sha256ofString(rawNonce);
    final appleCredential = await SignInWithApple.getAppleIDCredential(
      scopes: [
        AppleIDAuthorizationScopes.email,
        AppleIDAuthorizationScopes.fullName,
      ],
      nonce: nonce,
    );
    final oauthCredential = OAuthProvider("apple.com").credential(
      idToken: appleCredential.identityToken,
      rawNonce: rawNonce,
      accessToken: appleCredential.authorizationCode
    );
    return await FirebaseAuth.instance.signInWithCredential(oauthCredential);
    */
  }

  Future userLogOut() async {
    if (user != null) {
      await _firebaseAuth.signOut();
    } else {
      debugPrint('Not signed in');
    }
  }

  Future googleLogout() async {
    // Google logout temporarily disabled during migration
    /*
    final bool isSignedIn = await googleSignIn.isSignedIn();
    if (isSignedIn) {
      await googleSignIn.signOut();
    }
    */
  }

  Future<UserCredential?> signUpWithEmailPassword(BuildContext context, String email, String password) async {
    UserCredential? user;
    try {
      user = await _firebaseAuth.createUserWithEmailAndPassword(email: email, password: password);
    } on FirebaseAuthException catch (e) {
      debugPrint('error: e');
      if (!context.mounted) return null;
      openSnackbarFailure(context, e.message);
    }
    return user;
  }

  Future deleteUserAuth() async {
    await user?.delete().catchError((e) {
      debugPrint('error on deleting account');
      Fluttertoast.showToast(msg: e);
    });
  }

  Future sendEmailVerification() async {
    if (_firebaseAuth.currentUser != null) {
      await _firebaseAuth.currentUser!.sendEmailVerification().catchError((e) => debugPrint('Email sending failed'));
    }
  }

  Future sendPasswordRestEmail(BuildContext context, String email) async {
    try {
      await _firebaseAuth.sendPasswordResetEmail(email: email);
      if (!context.mounted) return;
      openSnackbar(context, 'An email has been sent to $email. Go to that link & reset your password.');
    } on FirebaseAuthException catch (error) {
      if (!context.mounted) return;
      openSnackbarFailure(context, error.message);
    }
  }
}
