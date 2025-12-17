import 'package:news_app/services/firestore_stub.dart';
import '../constants/app_constants.dart';
import 'ads_model.dart';

enum LicenseType { none, regular, extended }

class AppSettingsModel {
  final bool? featured,
      tags,
      skipLogin,
      onBoarding,
      latestArticles,
      popular,
      comments,
      author,
      views,
      likes,
      videoTab,
      audioTab,
      drawerMenu,
      logoAtCenter,
      date,
      readingTime,
      searchBox,
      featureAutoSlide;

  final String? supportEmail, website, privacyUrl, termsOfUseUrl;
  List<HomeCategory>? homeCategories;
  final AppSettingsSocialInfo? social;
  final AdsModel? ads;
  final LicenseType? license;
  final String? postDetailsLayout, categoryTileLayout;

  AppSettingsModel({
    this.featured,
    this.tags,
    this.onBoarding,
    this.supportEmail,
    this.website,
    this.privacyUrl,
    this.homeCategories,
    this.social,
    this.skipLogin,
    this.latestArticles,
    this.ads,
    this.license,
    this.popular,
    this.comments,
    this.author,
    this.views,
    this.likes,
    this.videoTab,
    this.audioTab,
    this.drawerMenu,
    this.logoAtCenter,
    this.postDetailsLayout,
    this.categoryTileLayout,
    this.date,
    this.readingTime,
    this.searchBox,
    this.featureAutoSlide,
    this.termsOfUseUrl,
  });

  factory AppSettingsModel.fromFirestore(DocumentSnapshot snap) {
    final Map d = snap.data() as Map<String, dynamic>;
    return AppSettingsModel(
      featured: d['featured'] ?? true,
      onBoarding: d['onboarding'] ?? false,
      skipLogin: d['skip_login'] ?? true,
      latestArticles: d['latest_articles'] ?? true,
      tags: d['tags'] ?? true,
      supportEmail: d['email'],
      privacyUrl: d['privacy_url'],
      website: d['website'],
      social: d['social'] != null ? AppSettingsSocialInfo.fromMap(d['social']) : null,
      ads: d['ads'] != null ? AdsModel.fromMap(d['ads']) : null,
      license: _getLicenseType(d['license']),
      popular: d['popular'] ?? true,
      comments: d['comments'] ?? true,
      likes: d['likes'] ?? true,
      views: d['views'] ?? true,
      author: d['author'] ?? true,
      videoTab: d['video_tab'] ?? true,
      audioTab: d['audio_tab'] ?? true,
      drawerMenu: d['drawer_menu'] ?? true,
      logoAtCenter: d['logo_center'] ?? false,
      postDetailsLayout: d['post_details_layout'] ?? postDetailsLayoutTypes.keys.elementAt(0),
      categoryTileLayout: d['category_tile_layout'] ?? categoryTileLayoutTypes.keys.elementAt(0),
      homeCategories: (d['home_categories'] as List<dynamic>?)?.map((e) => HomeCategory.fromMap(e)).toList(),
      date: d['date'] ?? true,
      readingTime: d['reading_time'] ?? true,
      searchBox: d['search_box'] ?? true,
      featureAutoSlide: d['feature_autoslide'] ?? true,
      termsOfUseUrl: d['terms_of_use'],
    );
  }

  static LicenseType _getLicenseType(String? value) {
    if (value == 'regular') {
      return LicenseType.regular;
    } else if (value == 'extended') {
      return LicenseType.extended;
    } else {
      return LicenseType.none;
    }
  }

}

class HomeCategory {
  final String id, name;

  HomeCategory({required this.id, required this.name});

  factory HomeCategory.fromMap(Map<String, dynamic> d) {
    return HomeCategory(
      id: d['id'],
      name: d['name'],
    );
  }

  static Map<String, dynamic> getMap(HomeCategory d) {
    return {
      'id': d.id,
      'name': d.name,
    };
  }
}

class AppSettingsSocialInfo {
  final String? fb, youtube, twitter, instagram;

  AppSettingsSocialInfo({required this.fb, required this.youtube, required this.twitter, required this.instagram});

  factory AppSettingsSocialInfo.fromMap(Map<String, dynamic> d) {
    return AppSettingsSocialInfo(
      fb: d['fb'],
      youtube: d['youtube'],
      instagram: d['instagram'],
      twitter: d['twitter'],
    );
  }

  static Map<String, dynamic> getMap(AppSettingsSocialInfo d) {
    return {
      'fb': d.fb,
      'youtube': d.youtube,
      'instagram': d.instagram,
      'twitter': d.twitter,
    };
  }
}
