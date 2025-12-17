import 'package:flutter/material.dart';

import '../configs/app_assets.dart';

class SplashLogo extends StatelessWidget {
  const SplashLogo({super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      child: Image.asset(
        splash,
        height: 130,
        width: 130,
        fit: BoxFit.contain,
      ),
    );
  }
}
