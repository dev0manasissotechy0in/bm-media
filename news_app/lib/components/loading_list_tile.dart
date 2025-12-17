import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import '../constants/custom_colors.dart';
import '../services/app_service.dart';

class LoadingListTile extends StatelessWidget {
  const LoadingListTile({super.key, this.height, this.count, this.padding});

  final double? height;
  final int? count;
  final double? padding;

  @override
  Widget build(BuildContext context) {
    final isDarkMode = AppService.isDarkMode(context);

    return ListView.separated(
      physics: const NeverScrollableScrollPhysics(),
      padding: EdgeInsets.all(padding ?? 15),
      itemCount: count ?? 6,
      shrinkWrap: true,
      separatorBuilder: (context, index) => const SizedBox(height: 20),
      itemBuilder: (context, index) {
        return Shimmer.fromColors(
          baseColor: isDarkMode ? CustomColor.shimmerBaseColorDark : CustomColor.shimmerBaseColor,
          highlightColor: isDarkMode ? CustomColor.shimmerhighlightColorDark : CustomColor.shimmerHighlightColor,
          child: Container(
            height: height ?? 200,
            color: Theme.of(context).canvasColor,
          ),
        );
      },
    );
  }
}