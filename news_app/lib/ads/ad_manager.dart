import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_mobile_ads/google_mobile_ads.dart';
import '../configs/ad_config.dart';
import '../providers/app_settings_provider.dart';
import '../providers/user_data_provider.dart';
import 'interstitial_ads.dart';

class AdManager {
  static Future initAds(WidgetRef ref) async {
    final settings = ref.read(appSettingsProvider);
    final user = ref.read(userDataProvider);
    if (settings?.ads?.isAdsEnabled == true && (user == null || user.isPremiumUser == false)) {
      await MobileAds.instance.initialize();
      debugPrint('Initialized ads');
    }
  }

  static bool isBannerEnbaled(WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    final user = ref.read(userDataProvider);

    if (settings?.ads?.isAdsEnabled == true && settings?.ads?.bannerEnbaled == true && (user == null || user.isPremiumUser == false)) {
      return true;
    } else {
      return false;
    }
  }

  static initInterstitailAds(WidgetRef ref) async {
    final settings = ref.read(appSettingsProvider);
    final user = ref.read(userDataProvider);

    final int interval = settings?.ads?.clickCountInterstitialAds ?? AdConfig.clickAmountCountInterstitalAdsDefault;

    // Checking elgibility
    if (settings?.ads?.isAdsEnabled == true && settings?.ads?.interstitialEnabled == true && (user == null || user.isPremiumUser == false)) {
      final adsProvider = ref.read(interstitalAdProvider);

      // Updating clicks count
      await Future.delayed(const Duration(seconds: 1)).then((value) => adsProvider.updateClickCount());

      final int clickCount = adsProvider.clickCount;
      debugPrint('clcikcount: $clickCount');

      if (ref.read(interstitalAdProvider).isInterstitalAdLoaded) {
        if (clickCount % interval == 0) {
          ref.read(interstitalAdProvider).showInterstitialAd();
        }
      } else {
        ref.read(interstitalAdProvider).createInterstitialAd();
      }
    } else {
      debugPrint('interstitial ads disbaled');
    }
  }

  static bool isNativeAdsEnabled(WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    final user = ref.read(userDataProvider);

    if (settings?.ads?.isAdsEnabled == true && settings?.ads?.nativeEnabled == true && (user == null || user.isPremiumUser == false)) {
      return true;
    } else {
      return false;
    }
  }

  static bool isCustomAdsEnabled(WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    final user = ref.read(userDataProvider);

    if (settings?.ads?.isAdsEnabled == true &&
        settings?.ads?.customAdsEnabled == true &&
        (settings?.ads?.customAds?.isNotEmpty ?? true) &&
        (user == null || user.isPremiumUser == false)) {
      return true;
    } else {
      return false;
    }
  }

  // Inline ads post interval for native ads
  static bool isPostIntervalValid(WidgetRef ref, int index) {
    final settings = ref.read(appSettingsProvider);
    final int postInterval = settings?.ads?.postIntervalCountInlineAds ?? AdConfig.postIntervaCountInlineAdsDefault;
    if (((index + 1) % postInterval == 0)) {
      return true;
    } else {
      return false;
    }
  }
}
