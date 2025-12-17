class AppBranding {
  final String appName;
  final String appTagline;
  final String logoText;
  final String footerText;
  final String logoLight;
  final String logoDark;
  final String fontFamily;
  final String fontPath;
  final String appVersion;
  final String minAppVersion;
  final bool forceUpdate;
  final bool showSplash;
  final bool enableDarkMode;
  final int splashDuration;
  final String splashLogoUrl;
  final String splashBackgroundColor;
  final String splashText;
  final String lastUpdated;

  AppBranding({
    required this.appName,
    required this.appTagline,
    required this.logoText,
    required this.footerText,
    required this.logoLight,
    required this.logoDark,
    required this.fontFamily,
    required this.fontPath,
    required this.appVersion,
    required this.minAppVersion,
    required this.forceUpdate,
    required this.showSplash,
    required this.enableDarkMode,
    required this.splashDuration,
    required this.splashLogoUrl,
    required this.splashBackgroundColor,
    required this.splashText,
    required this.lastUpdated,
  });

  factory AppBranding.fromJson(Map<String, dynamic> json) {
    return AppBranding(
      appName: json['app_name'] ?? 'Brackodd Media',
      appTagline: json['app_tagline'] ?? '',
      logoText: json['logo_text'] ?? '',
      footerText: json['footer_text'] ?? '',
      logoLight: json['logo_light'] ?? 'assets/images/logo.png',
      logoDark: json['logo_dark'] ?? 'assets/images/logoDark.png',
      fontFamily: json['font_family'] ?? 'Westack',
      fontPath: json['font_path'] ?? 'assets/fonts/westack',
      appVersion: json['app_version'] ?? '1.0.0',
      minAppVersion: json['min_app_version'] ?? '1.0.0',
      forceUpdate: json['force_update'] ?? false,
      showSplash: json['show_splash'] ?? true,
      enableDarkMode: json['enable_dark_mode'] ?? true,
      splashDuration: json['splash_duration'] ?? 3,
      splashLogoUrl: json['splash_logo_url'] ?? 'assets/images/logo.png',
      splashBackgroundColor: json['splash_background_color'] ?? '#FFFFFF',
      splashText: json['splash_text'] ?? '',
      lastUpdated: json['last_updated'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'app_name': appName,
      'app_tagline': appTagline,
      'logo_text': logoText,
      'footer_text': footerText,
      'logo_light': logoLight,
      'logo_dark': logoDark,
      'font_family': fontFamily,
      'font_path': fontPath,
      'app_version': appVersion,
      'min_app_version': minAppVersion,
      'force_update': forceUpdate,
      'show_splash': showSplash,
      'enable_dark_mode': enableDarkMode,
      'splash_duration': splashDuration,
      'splash_logo_url': splashLogoUrl,
      'splash_background_color': splashBackgroundColor,
      'splash_text': splashText,
      'last_updated': lastUpdated,
    };
  }

  // Default branding (fallback)
  factory AppBranding.defaultBranding() {
    return AppBranding(
      appName: 'Brackodd Media',
      appTagline: 'Your trusted news source',
      logoText: '',
      footerText: '',
      logoLight: 'assets/images/logo.png',
      logoDark: 'assets/images/logoDark.png',
      fontFamily: 'Westack',
      fontPath: 'assets/fonts/westack',
      appVersion: '1.0.0',
      minAppVersion: '1.0.0',
      forceUpdate: false,
      showSplash: true,
      enableDarkMode: true,
      splashDuration: 3,
      splashLogoUrl: 'assets/images/logo.png',
      splashBackgroundColor: '#FFFFFF',
      splashText: '',
      lastUpdated: DateTime.now().toString(),
    );
  }
}
