import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:news_app/models/article.dart';

class PremiumTag extends StatelessWidget {
  const PremiumTag({
    super.key,
    required this.article,
    this.topMargin,
  });

  final Article article;
  final double? topMargin;

  @override
  Widget build(BuildContext context) {
    if (article.priceStatus == 'free') return const SizedBox.shrink();
    return Positioned(
      top: 0,
      right: 0,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        margin: EdgeInsets.only(top: topMargin ?? 0),
        decoration: BoxDecoration(
          color: Theme.of(context).primaryColor,
          borderRadius: const BorderRadius.only(topRight: Radius.circular(5), bottomLeft: Radius.circular(5)),
        ),
        child: Text(
          'premium',
          style: Theme.of(context).textTheme.titleSmall?.copyWith(color: Colors.white),
        ).tr(),
      ),
    );
  }
}
