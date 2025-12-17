import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/screens/welcome.dart';
import '../models/user_model.dart';
import '../providers/user_data_provider.dart';
import '../screens/home/home_bottom_bar.dart';
import '../screens/home/home_view.dart';
import '../services/auth_service.dart';
import '../utils/next_screen.dart';

mixin UserMixin {
  void handleLogout(context, {required WidgetRef ref}) async {
    await AuthService().userLogOut().onError((error, stackTrace) => debugPrint('error: $error'));
    await AuthService().googleLogout().onError((error, stackTrace) => debugPrint('error1: $error'));
    ref.invalidate(userDataProvider);
    ref.invalidate(homeTabControllerProvider);
    ref.invalidate(navBarIndexProvider);
    NextScreen.closeOthersAnimation(context, const WelcomeScreen());
  }

  static bool isExpired(UserModel user) {
    final DateTime expireDate = user.subscription!.expireAt;
    final DateTime now = DateTime.now().toUtc();
    final difference = expireDate.difference(now).inDays;
    if (difference >= 0) {
      return false;
    } else {
      return true;
    }
  }

  static bool isUserPremium(UserModel? user) {
    return user != null && user.subscription != null && isExpired(user) == false ? true : false;
  }

  int remainingDays(UserModel user) {
    final DateTime expireDate = user.subscription!.expireAt;
    final DateTime now = DateTime.now().toUtc();
    final difference = expireDate.difference(now).inDays;
    return difference;
  }
}
