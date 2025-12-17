import 'package:flutter/material.dart';
import '../../../models/category.dart';
import '../../../utils/cache_image_filter.dart';
import '../../../utils/next_screen.dart';
import '../../all_articles/articles_view.dart';
import '../../sub_categories.dart/sub_categories.dart';

class CategoryTile extends StatelessWidget {
  const CategoryTile({
    super.key,
    required this.category,
    required this.isGridTile,
    required this.categories,
  });

  final Category category;
  final bool isGridTile;
  final List<Category> categories;

  @override
  Widget build(BuildContext context) {
    final heroTag = UniqueKey();
    return InkWell(
      onTap: () {
        List<Category> subCategories = categories.where((element) => element.parentId == category.id).toList();
        if (subCategories.isEmpty) {
          NextScreen.iOS(
              context,
              AllArticlesView(
                articlesBy: ArticlesBy.category,
                title: category.name,
                categoryId: category.id,
                thumbnail: category.thumbnailUrl,
                heroTag: heroTag,
              ));
        } else {
          NextScreen.normal(context, SubCategories(category: category, subCategories: subCategories, allCategories: categories));
        }
      },
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5),
        ),
        child: Stack(
          children: [
            Hero(
              tag: heroTag,
              child: SizedBox(
                width: MediaQuery.sizeOf(context).width,
                child: isGridTile
                    ? CustomCacheImageWithDarkFilterBottom(imageUrl: category.thumbnailUrl, radius: 5.0)
                    : CustomCacheImageWithDarkFilterFull(imageUrl: category.thumbnailUrl, radius: 5.0),
              ),
            ),
            Align(
              alignment: isGridTile ? Alignment.bottomLeft : Alignment.center,
              child: Container(
                margin: isGridTile ? const EdgeInsets.only(left: 15, bottom: 15, right: 10) : EdgeInsets.zero,
                child: Text(
                  category.name,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w600),
                ),
              ),
            )
          ],
        ),
      ),
    );
  }
}
