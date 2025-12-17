import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/custom_ad_widget.dart';
import 'package:news_app/models/custom_ad_model.dart';
import '../providers/app_settings_provider.dart';
import 'ad_manager.dart';
import 'native_ad_widget.dart';

class InlineAds extends StatefulWidget {
  const InlineAds({super.key, required this.ref, required this.index});

  final WidgetRef ref;
  final int index;

  @override
  State<InlineAds> createState() => _InlineAdsState();
}

class _InlineAdsState extends State<InlineAds> {
  CustomAdModel? selectedAd;

  @override
  void initState() {
    super.initState();
    _selectAd();
  }

  void _selectAd() {
    final settings = widget.ref.read(appSettingsProvider);
    if (AdManager.isCustomAdsEnabled(widget.ref) && AdManager.isPostIntervalValid(widget.ref, widget.index)) {
      final List<CustomAdModel> ads = settings?.ads?.customAds ?? [];
      if (ads.isNotEmpty) {
        selectedAd = ads[Random().nextInt(ads.length)];
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (selectedAd != null) {
      return CustomAdWidget(ad: selectedAd!, radius: 5);
    } else if (AdManager.isNativeAdsEnabled(widget.ref) && AdManager.isPostIntervalValid(widget.ref, widget.index)) {
      return const Padding(
        padding: EdgeInsets.only(bottom: 20),
        child: NativeAdWidget(isSmallSize: false),
      );
    }
    return const SizedBox.shrink();
  }
}
