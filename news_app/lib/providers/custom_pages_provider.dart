import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/custom_page.dart';
import '../services/custom_pages_service.dart';

final customPagesProvider = FutureProvider<List<CustomPage>>((ref) async {
  final service = CustomPagesService();
  return await service.getAppPages();
});

final customPagesStreamProvider = StreamProvider<List<CustomPage>>((ref) async* {
  final service = CustomPagesService();
  
  // Initial load
  yield await service.getAppPages();
  
  // Refresh every 30 minutes
  while (true) {
    await Future.delayed(const Duration(minutes: 30));
    yield await service.getAppPages();
  }
});
