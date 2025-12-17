import 'package:flutter/material.dart';
import 'package:news_app/models/custom_ad_model.dart';

import '../services/app_service.dart';
import '../utils/custom_cached_image.dart';

class CustomAdWidget extends StatelessWidget {
  const CustomAdWidget({super.key, required this.ad, this.radius});
  final CustomAdModel ad;
  final double? radius;

  @override
  Widget build(BuildContext context) {
    final String title = ad.title ?? '';
    final String actionText = ad.actionButtonText ?? '';
    final String adImage = ad.imageUrl ?? '';

    return InkWell(
      onTap: () => AppService().openLinkWithCustomTab(ad.target),
      child: Container(
        width: double.infinity,
        margin: const EdgeInsets.only(bottom: 20),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Column(
          children: [
            Stack(
              children: [
                adImage.isEmpty
                    ? const SizedBox.shrink()
                    : SizedBox(
                        height: 200,
                        width: MediaQuery.sizeOf(context).width,
                        child: CustomCacheImage(
                          imageUrl: adImage,
                          radius: radius ?? 5,
                          circularShape: false,
                        ),
                      ),
                title.isNotEmpty && actionText.isNotEmpty
                    ? const SizedBox.shrink()
                    : Align(
                        alignment: Alignment.topRight,
                        child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 3, horizontal: 10),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(0),
                            ),
                            child: const Text(
                              'Ad',
                              style: TextStyle(fontSize: 12, color: Colors.blueAccent),
                            )),
                      )
              ],
            ),
            title.isEmpty
                ? const SizedBox.shrink()
                : ListTile(
                    title: Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Text(
                        title,
                        maxLines: 3,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 20),
                      ),
                    ),
                    subtitle: Wrap(
                      children: [
                        Container(
                            margin: const EdgeInsets.only(top: 8),
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 1),
                            decoration: BoxDecoration(border: Border.all(color: Colors.grey, width: 0.5)),
                            child: const Text(
                              'Ad',
                              style: TextStyle(color: Colors.blueAccent),
                            )),
                      ],
                    ),
                  ),
            actionText.isEmpty
                ? const SizedBox.shrink()
                : Container(
                    height: 40,
                    width: MediaQuery.sizeOf(context).width,
                    margin: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
                    alignment: Alignment.center,
                    decoration: BoxDecoration(color: Theme.of(context).primaryColor),
                    child: Text(
                      actionText,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
                    ),
                  )
          ],
        ),
      ),
    );
  }
}
