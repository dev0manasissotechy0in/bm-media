import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/app_settings_provider.dart';
import '../services/app_service.dart';

class PrivacyInfo extends ConsumerWidget {
  const PrivacyInfo({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(appSettingsProvider);
    final String privacyUrl = settings?.privacyUrl ?? '';
    final String termsUrl = settings?.termsOfUseUrl ?? '';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 30),
      child: Column(
        children: [
          const Text(
            'By SigningUp/Logging In, You agree to our',
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              InkWell(
                onTap: ()=> AppService().openLinkWithCustomTab(termsUrl),
                child: const Text('terms-of-use', style: TextStyle(decoration: TextDecoration.underline, color: Colors.blue),).tr()
              ),
              const SizedBox(width: 5,),
              const Text('and'),
              const SizedBox(width: 5,),
              InkWell(
                onTap: ()=> AppService().openLinkWithCustomTab(privacyUrl),
                child: const Text('privacy-policy', style: TextStyle(decoration: TextDecoration.underline, color: Colors.blue),).tr()
              )
            ],
          )
        ],
      ),
    );
  }
}