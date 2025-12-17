import 'package:easy_localization/easy_localization.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/firebase_options.dart';
import 'core/app.dart';
import 'configs/language_config.dart';
import 'services/app_service.dart';
import 'services/hive_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase (handle duplicate initialization gracefully)
  try {
    await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
    
    // Subscribe to FCM 'all' topic to receive notifications
    await FirebaseMessaging.instance.subscribeToTopic('all');
    print('✅ Subscribed to FCM topic: all');
  } catch (e) {
    if (e.toString().contains('duplicate-app')) {
      print('Firebase already initialized');
    } else {
      rethrow;
    }
  }
  
  await EasyLocalization.ensureInitialized();
  HiveService.initHive();
  AppService.svgPrecacheImage();
  runApp(
    ProviderScope(
      child: EasyLocalization(
        supportedLocales: LanguageConfig.supportedLocales,
        path: 'assets/translations',
        fallbackLocale: LanguageConfig.fallbackLocale,
        startLocale: LanguageConfig.startLocale,
        child: const MyApp(),
      ),
    ),
  );
}
