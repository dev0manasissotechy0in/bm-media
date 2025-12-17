/// Model for app configuration from backend
class AppConfig {
  final bool googleLoginEnabled;
  final bool facebookLoginEnabled;
  final String googleClientId;
  final bool otpEnabled;
  final bool commentsEnabled;
  final bool userRegistrationEnabled;
  final String siteName;
  final String siteTagline;

  AppConfig({
    required this.googleLoginEnabled,
    required this.facebookLoginEnabled,
    required this.googleClientId,
    required this.otpEnabled,
    required this.commentsEnabled,
    required this.userRegistrationEnabled,
    required this.siteName,
    required this.siteTagline,
  });

  factory AppConfig.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final socialLogin = data['social_login'];
    final features = data['features'];
    final appInfo = data['app_info'];
    
    return AppConfig(
      googleLoginEnabled: socialLogin['google_enabled'] == true || socialLogin['google_enabled'] == 1,
      facebookLoginEnabled: socialLogin['facebook_enabled'] == true || socialLogin['facebook_enabled'] == 1,
      googleClientId: socialLogin['google_client_id'] ?? '',
      otpEnabled: features['otp_enabled'] == true || features['otp_enabled'] == 1,
      commentsEnabled: features['comments_enabled'] == true || features['comments_enabled'] == 1,
      userRegistrationEnabled: features['user_registration_enabled'] == true || features['user_registration_enabled'] == 1,
      siteName: appInfo['site_name'] ?? 'News App',
      siteTagline: appInfo['site_tagline'] ?? '',
    );
  }

  // Default configuration
  factory AppConfig.defaultConfig() {
    return AppConfig(
      googleLoginEnabled: false,
      facebookLoginEnabled: false,
      googleClientId: '',
      otpEnabled: true,
      commentsEnabled: true,
      userRegistrationEnabled: true,
      siteName: 'News App',
      siteTagline: '',
    );
  }
}
