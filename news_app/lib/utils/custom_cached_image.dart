import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:line_icons/line_icons.dart';

class CustomCacheImage extends StatelessWidget {
  final String? imageUrl;
  final double radius;
  final bool? circularShape;
  const CustomCacheImage(
      {super.key, required this.imageUrl, required this.radius, this.circularShape});

  @override
  Widget build(BuildContext context) {
    // Log the image URL for debugging
    if (imageUrl != null && imageUrl!.isNotEmpty) {
      debugPrint('🖼️ Loading image: $imageUrl');
    }
    
    return ClipRRect(
      borderRadius: BorderRadius.only(
        topLeft: Radius.circular(radius),
        topRight: Radius.circular(radius),
        bottomLeft: Radius.circular(circularShape == false ? 0 : radius),
        bottomRight: Radius.circular(circularShape == false ? 0 : radius)

      ),
      child: CachedNetworkImage(
        imageUrl: imageUrl!,
        fit: BoxFit.cover,
        width: double.infinity,
        height: MediaQuery.of(context).size.height,
        placeholder: (context, url) => Container(
          color: Colors.grey[300],
          child: const Center(
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
        ),
        errorWidget: (context, url, error) {
          debugPrint('❌ Image load error: $url');
          debugPrint('❌ Error details: $error');
          return Container(
            color: Theme.of(context).secondaryHeaderColor,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(LineIcons.image, size: 48),
                const SizedBox(height: 8),
                if (url.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.all(8.0),
                    child: Text(
                      'Failed to load',
                      style: TextStyle(
                        fontSize: 10,
                        color: Colors.grey[600],
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}