import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/branding_service.dart';
import '../models/app_branding.dart';

// Provider for app branding data
final brandingProvider = FutureProvider<AppBranding>((ref) async {
  return await BrandingService().getAppBranding();
});

// Provider with auto-refresh capability
final brandingNotifierProvider = StateNotifierProvider<BrandingNotifier, AsyncValue<AppBranding>>((ref) {
  return BrandingNotifier();
});

class BrandingNotifier extends StateNotifier<AsyncValue<AppBranding>> {
  BrandingNotifier() : super(const AsyncValue.loading()) {
    loadBranding();
  }

  Future<void> loadBranding() async {
    state = const AsyncValue.loading();
    try {
      final branding = await BrandingService().getAppBranding();
      state = AsyncValue.data(branding);
    } catch (error, stackTrace) {
      state = AsyncValue.error(error, stackTrace);
    }
  }

  Future<void> refresh() async {
    await loadBranding();
  }
}
