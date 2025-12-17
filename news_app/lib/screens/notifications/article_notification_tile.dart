import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:news_app/models/notification_model.dart';
import 'package:news_app/services/app_service.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../services/hive_service.dart';
import '../../utils/custom_cached_image.dart';

// Small image at the left side

class ArticleNotiificationTile extends StatelessWidget {
  final NotificationModel notification;
  const ArticleNotiificationTile({super.key, required this.notification});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.start,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Flexible(
                flex: 2,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    Container(
                      height: 110,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(5.0),
                      ),
                      child: CustomCacheImage(imageUrl: notification.thumbnail, radius: 5.0),
                    ),
                  ],
                )),
            const SizedBox(
              width: 15,
            ),
            Flexible(
              flex: 4,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.start,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    notification.title,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(
                    height: 10,
                  ),
                  Row(
                    children: <Widget>[
                      const Icon(
                        CupertinoIcons.time,
                        size: 18,
                      ),
                      const SizedBox(
                        width: 3,
                      ),
                      Text(
                        AppService.getDate(notification.recievedAt),
                        style: const TextStyle(fontSize: 13),
                      ),
                      const Spacer(),
                      IconButton(
                        constraints: const BoxConstraints(minHeight: 40),
                        alignment: Alignment.centerRight,
                        padding: const EdgeInsets.only(left: 8),
                        icon: const Icon(
                          Icons.close,
                          size: 20,
                        ),
                        onPressed: () => HiveService().deleteNotificationData(notification.id),
                      )
                    ],
                  )
                ],
              ),
            )
          ],
        ),
      ),
      onTap: ()=> NextScreen.navigateToNotificationDetailsScreen(context, notification),
    );
  }
}
