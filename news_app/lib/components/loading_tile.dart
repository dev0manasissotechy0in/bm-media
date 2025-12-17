import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../constants/custom_colors.dart';
import '../services/app_service.dart';

class LoadingTile extends StatelessWidget {
  const LoadingTile({super.key, this.height, this.padding, this.borderRadius});

  final double? height;
  final double? padding;
  final double? borderRadius;

  @override
  Widget build(BuildContext context) {
    final isDarkMode = AppService.isDarkMode(context);
    return Padding(
      padding: EdgeInsets.all(padding ?? 20),
      child: Shimmer.fromColors(
        baseColor: isDarkMode ? CustomColor.shimmerBaseColorDark : CustomColor.shimmerBaseColor,
        highlightColor: isDarkMode ? CustomColor.shimmerhighlightColorDark : CustomColor.shimmerHighlightColor,
        child: Container(
          height: height ?? 200,
          decoration: BoxDecoration(color: Theme.of(context).canvasColor, borderRadius: BorderRadius.circular(borderRadius ?? 0)),
        ),
      ),
    );
  }
}
