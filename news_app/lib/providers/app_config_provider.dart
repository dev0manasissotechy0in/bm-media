import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/app_config_model.dart';
import '../services/api_service.dart';

// App Config Provider
final appConfigProvider = StateNotifierProvider<AppConfigNotifier, AppConfig>((ref) {
  return AppConfigNotifier();
});

class AppConfigNotifier extends StateNotifier<AppConfig> {
  AppConfigNotifier() : super(AppConfig.defaultConfig()) {
    loadConfig();
  }

  Future<void> loadConfig() async {
    try {
      final apiService = ApiService();
      final response = await apiService.getAppConfig();
      
      if (response != null) {
        state = AppConfig.fromJson(response);
      }
    } catch (e) {
      // If fails, keep default config
      print('Failed to load app config: $e');
    }
  }

  void updateConfig(AppConfig config) {
    state = config;
  }
}
