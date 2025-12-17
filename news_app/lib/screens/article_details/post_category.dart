import 'package:flutter/material.dart';
import 'package:news_app/models/article.dart';

class PostCategory extends StatelessWidget {
  const PostCategory({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(5), color: Theme.of(context).secondaryHeaderColor),
      child: AnimatedPadding(
        duration: const Duration(milliseconds: 1000),
        padding: const EdgeInsets.only(left: 10, right: 10, top: 5, bottom: 5),
        child: Text(
          article.category?.name.toString().toUpperCase() ?? '',
          style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
        ),
      ),
    );
  }
}
