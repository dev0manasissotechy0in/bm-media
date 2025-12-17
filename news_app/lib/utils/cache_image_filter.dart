import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:line_icons/line_icons.dart';

class CustomCacheImageWithDarkFilterFull extends StatelessWidget {
  final String imageUrl;
  final double radius;
  final bool? circularShape;
  final bool? allPosition;
  const CustomCacheImageWithDarkFilterFull({super.key, required this.imageUrl, required this.radius, this.circularShape, this.allPosition});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.only(
          topLeft: Radius.circular(radius),
          topRight: Radius.circular(radius),
          bottomLeft: Radius.circular(circularShape == false ? 0 : radius),
          bottomRight: Radius.circular(circularShape == false ? 0 : radius)),
      child: Stack(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            child: CachedNetworkImage(
              imageUrl: imageUrl,
              fit: BoxFit.cover,
              height: MediaQuery.of(context).size.height,
              placeholder: (context, url) => Container(color: Colors.grey[300]),
              errorWidget: (context, url, error) => Container(
                color: Colors.grey[300],
                child: const Icon(Icons.error),
              ),
            ),
          ),
          Container(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.black38,
                  Colors.black38,
                  Colors.black38,
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CustomCacheImageWithDarkFilterTopBottom extends StatelessWidget {
  final String imageUrl;
  final double radius;
  final bool? circularShape;
  final bool? allPosition;
  const CustomCacheImageWithDarkFilterTopBottom({super.key, required this.imageUrl, required this.radius, this.circularShape, this.allPosition});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.only(
        topLeft: Radius.circular(radius),
        topRight: Radius.circular(radius),
        bottomLeft: Radius.circular(circularShape == false ? 0 : radius),
        bottomRight: Radius.circular(circularShape == false ? 0 : radius),
      ),
      child: Stack(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            child: CachedNetworkImage(
              imageUrl: imageUrl,
              fit: BoxFit.cover,
              height: MediaQuery.of(context).size.height,
              placeholder: (context, url) => Container(color: Colors.grey[300]),
              errorWidget: (context, url, error) => Container(
                color: Colors.grey[300],
                child: const Icon(Icons.error),
              ),
            ),
          ),
          Container(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0xcc000000),
                  Color(0x00000000),
                  Color(0x00000000),
                  Color(0xcc000000),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CustomCacheImageWithDarkFilterBottom extends StatelessWidget {
  final String? imageUrl;
  final double radius;
  final bool? circularShape;
  final bool? allPosition;
  const CustomCacheImageWithDarkFilterBottom({super.key, required this.imageUrl, required this.radius, this.circularShape, this.allPosition});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.only(
        topLeft: Radius.circular(radius),
        topRight: Radius.circular(radius),
        bottomLeft: Radius.circular(circularShape == false ? 0 : radius),
        bottomRight: Radius.circular(circularShape == false ? 0 : radius),
      ),
      child: Stack(
        children: [
          SizedBox(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            child: CachedNetworkImage(
              imageUrl: imageUrl.toString(),
              fit: BoxFit.cover,
              height: MediaQuery.of(context).size.height,
              placeholder: (context, url) => Container(color: Theme.of(context).secondaryHeaderColor),
              errorWidget: (context, url, error) => Container(
                color: Theme.of(context).primaryColor,
                child: const Icon(LineIcons.image),
              ),
            ),
          ),
          Container(
            height: MediaQuery.of(context).size.height,
            width: MediaQuery.sizeOf(context).width,
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Color(0x00000000),
                  Color(0x00000000),
                  Color(0x00000000),
                  Color(0xcc000000),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
