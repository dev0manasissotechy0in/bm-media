import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../constants/custom_colors.dart';
import '../services/app_service.dart';

class LoadingGridTile extends StatelessWidget {
  const LoadingGridTile({super.key, required this.isGridStyle});
  final bool isGridStyle;

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      padding: const EdgeInsets.all(15),
      physics: const NeverScrollableScrollPhysics(),
      shrinkWrap: true,
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: isGridStyle ? 2 : 1,
        mainAxisSpacing: 10,
        crossAxisSpacing: 10,
        childAspectRatio: isGridStyle ? 1.1 : 2.5,
      ),
      itemCount: 10,
      itemBuilder: (BuildContext context, int index) {
        return Shimmer.fromColors(
          baseColor: AppService.isDarkMode(context) ? CustomColor.shimmerBaseColorDark : CustomColor.shimmerBaseColor,
          highlightColor: AppService.isDarkMode(context) ? CustomColor.shimmerhighlightColorDark : CustomColor.shimmerHighlightColor,
          child: Container(
            color: Colors.white,
          ),
        );
      },
    );
  }
}
