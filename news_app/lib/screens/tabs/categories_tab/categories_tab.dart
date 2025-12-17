import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/loading_grid_tile.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/constants/app_constants.dart';
import 'package:news_app/models/category.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/services/api_service.dart';
import 'package:news_app/utils/empty_animation.dart';
import 'category_tile.dart';

final categoriesProvier = FutureProvider<List<Category>>((ref) async {
  final List<Category> categories = await ApiService().getCategories();
  return categories;
});

class CategoriesTab extends ConsumerWidget {
  const CategoriesTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final categoriesRef = ref.watch(categoriesProvier);
    final settings = ref.watch(appSettingsProvider);
    final bool isGridTile = settings?.categoryTileLayout == categoryTileLayoutTypes.keys.elementAt(0) ? true : false;

    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        centerTitle: false,
        automaticallyImplyLeading: false,
        title: const Text('categories').tr(),
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        elevation: 0,
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.refresh(categoriesProvier),
        child: categoriesRef.when(
          skipLoadingOnRefresh: false,
          data: (categories) {
            if (categories.isEmpty) return EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr());
            final List<Category> mainCategories = categories.where((element) => element.parentId == null).toList();
            return GridView.builder(
              padding: const EdgeInsets.all(15),
              gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: isGridTile ? 2 : 1,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: isGridTile ? 1.1 : 2.5,
              ),
              itemCount: mainCategories.length,
              itemBuilder: (context, int index) {
                final Category category = mainCategories[index];
                return CategoryTile(category: category, isGridTile: isGridTile, categories: categories);
              },
            );
          },
          error: (error, stackTrace) => EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr()),
          loading: () => LoadingGridTile(isGridStyle: isGridTile),
        ),
      ),
    );
  }
}
