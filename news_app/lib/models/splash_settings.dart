class SplashSettings {
  final bool success;
  final bool showDynamic;
  final String? dynamicReason;
  final DefaultSplash defaultSplash;
  final DynamicSplash? dynamicSplash;

  SplashSettings({
    required this.success,
    required this.showDynamic,
    this.dynamicReason,
    required this.defaultSplash,
    this.dynamicSplash,
  });

  factory SplashSettings.fromJson(Map<String, dynamic> json) {
    return SplashSettings(
      success: json['success'] ?? false,
      showDynamic: json['show_dynamic'] ?? false,
      dynamicReason: json['dynamic_reason'],
      defaultSplash: DefaultSplash.fromJson(json['default_splash'] ?? {}),
      dynamicSplash: json['dynamic_splash'] != null
          ? DynamicSplash.fromJson(json['dynamic_splash'])
          : null,
    );
  }
}

class DefaultSplash {
  final String logo;
  final String backgroundColor;
  final String textColor;
  final String tagline;
  final String animationType;

  DefaultSplash({
    required this.logo,
    required this.backgroundColor,
    required this.textColor,
    required this.tagline,
    required this.animationType,
  });

  factory DefaultSplash.fromJson(Map<String, dynamic> json) {
    return DefaultSplash(
      logo: json['logo'] ?? 'assets/images/logo.png',
      backgroundColor: json['background_color'] ?? '#FFFFFF',
      textColor: json['text_color'] ?? '#000000',
      tagline: json['tagline'] ?? 'Your Trusted News Source',
      animationType: json['animation_type'] ?? 'fade',
    );
  }
}

class DynamicSplash {
  final String image;
  final String backgroundColor;
  final String textColor;
  final String title;
  final String subtitle;
  final String buttonText;
  final String buttonAction;
  final int displayDuration;

  DynamicSplash({
    required this.image,
    required this.backgroundColor,
    required this.textColor,
    required this.title,
    required this.subtitle,
    required this.buttonText,
    required this.buttonAction,
    required this.displayDuration,
  });

  factory DynamicSplash.fromJson(Map<String, dynamic> json) {
    return DynamicSplash(
      image: json['image'] ?? '',
      backgroundColor: json['background_color'] ?? '#FFFFFF',
      textColor: json['text_color'] ?? '#000000',
      title: json['title'] ?? '',
      subtitle: json['subtitle'] ?? '',
      buttonText: json['button_text'] ?? '',
      buttonAction: json['button_action'] ?? '',
      displayDuration: json['display_duration'] ?? 3,
    );
  }
}
