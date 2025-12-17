import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:icons_plus/icons_plus.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/components/article_tiles/article_tile1.dart';
import 'package:news_app/screens/search/search_view.dart';
import 'package:news_app/screens/sub_categories.dart/sub_categories_provider.dart';
import 'package:news_app/screens/tabs/categories_tab/category_tile.dart';
import 'package:news_app/utils/loading_widget.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../models/category.dart';

class SubCategories extends StatefulWidget {
  const SubCategories({super.key, required this.category, required this.subCategories, required this.allCategories});
  final Category category;
  final List<Category> subCategories;
  final List<Category> allCategories;

  @override
  State<SubCategories> createState() => _SubCategoriesState();
}

class _SubCategoriesState extends State<SubCategories> {
  ScrollController scrollController = ScrollController();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        title: Text(widget.category.name.toString()),
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        actions: [
          IconButton(
            style: IconButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 15)),
            icon: const Icon(AntDesign.search_outline, size: 22),
            onPressed: () => NextScreen.normal(context, const SearchScreen()),
          ),
        ],
      ),
      body: SingleChildScrollView(
        controller: scrollController,
        child: Column(
          children: [
            GridView.builder(
              physics: const NeverScrollableScrollPhysics(),
              shrinkWrap: true,
              padding: const EdgeInsets.all(15),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 1,
                crossAxisSpacing: 10,
                mainAxisSpacing: 10,
                childAspectRatio: 2.5,
              ),
              itemCount: widget.subCategories.length,
              itemBuilder: (BuildContext context, int index) {
                return CategoryTile(category: widget.subCategories[index], isGridTile: false, categories: widget.allCategories);
              },
            ),
            const Divider(),
            _CategoryArticles(
              sc: scrollController,
              category: widget.category,
            )
          ],
        ),
      ),
    );
  }
}

class _CategoryArticles extends ConsumerStatefulWidget {
  const _CategoryArticles({required this.sc, required this.category});

  final ScrollController sc;
  final Category category;

  @override
  ConsumerState<_CategoryArticles> createState() => __CategoryArticlesState();
}

class __CategoryArticlesState extends ConsumerState<_CategoryArticles> {
  @override
  void initState() {
    widget.sc.addListener(_scrollListener);
    Future.microtask(() {
      ref.read(subcategoryArticlesProvider.notifier).getData(widget.category.id, ref);
    });
    super.initState();
  }

  _scrollListener() async {
    bool isEnd = widget.sc.offset >= widget.sc.position.maxScrollExtent && !widget.sc.position.outOfRange;
    // debugPrint('isEnd: $isEnd');
    if (isEnd) {
      ref.read(subcategoryArticlesProvider.notifier).getData(widget.category.id, ref);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool hasData = ref.watch(hasDataSubcategoriesProvider);
    final bool isLoading = ref.watch(isLoadingSubcategoriesProvider);
    final articles = ref.watch(subcategoryArticlesProvider);
    return isLoading
        ? const CircularProgressIndicator()
        : articles.isEmpty
            ? const SizedBox.shrink()
            : Column(
                children: [
                  ListView.separated(
                    padding: const EdgeInsets.all(15),
                    physics: const NeverScrollableScrollPhysics(),
                    shrinkWrap: true,
                    itemCount: articles.length,
                    separatorBuilder: (context, index) => const SizedBox(height: 20),
                    itemBuilder: (BuildContext context, int index) {
                      return Column(
                        children: [
                          InlineAds(ref: ref, index: index),
                          ArticleTile(article: articles[index]),
                        ],
                      );
                    },
                  ),
                  Opacity(opacity: hasData ? 1.0 : 0.0, child: const LoadingIndicatorWidget()),
                ],
              );
  }
}
