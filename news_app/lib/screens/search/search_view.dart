import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../../services/api_service.dart';
import '../../utils/empty_icon.dart';
import 'recent_searches.dart';
import 'search_bar.dart';
import 'searched_articles.dart';

final searchTextCtlrProvider = Provider.autoDispose((ref) => TextEditingController());
final searchStartedProvider = StateProvider.autoDispose<bool>((ref) => false);
final recentSearchDataProvider = StateProvider<List<String>>((ref) => []);

final searchedArticlesProvider = FutureProvider.autoDispose<List<Article>>((ref) async {
  final value = ref.watch(searchTextCtlrProvider).text;
  final allArticles = await ApiService().getAllArticles();
  final List<Article> filteredArticles = allArticles
      .where((article) =>
          article.title.toLowerCase().contains(value.toLowerCase()) ||
          article.summary.toString().toLowerCase().contains(value.toLowerCase()) ||
          article.description.toString().toLowerCase().contains(value.toLowerCase()) ||
          article.author!.name.toString().toLowerCase().contains(value.toLowerCase()))
      .toList();

  return filteredArticles;
});

class SearchScreen extends ConsumerWidget {
  const SearchScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final searchFieldCtrl = ref.watch(searchTextCtlrProvider);
    final bool searchStarted = ref.watch(searchStartedProvider);
    final recentSearchList = ref.watch(recentSearchDataProvider);

    Widget buildView() {
      if (searchStarted) {
        return const SearchedArticles();
      } else {
        if (recentSearchList.isNotEmpty) {
          return const RecentSearches();
        } else {
          return EmptyPageWithIcon(icon: FeatherIcons.search, title: 'search-articles'.tr());
        }
      }
    }

    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        elevation: 0,
        titleSpacing: 0,
        title: SearchAppBar(searchTextCtlr: searchFieldCtrl),
        centerTitle: false,
        leading: IconButton(
          icon: const Icon(FeatherIcons.chevronLeft),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: Column(
        children: [
          const Divider(height: 1),
          Expanded(child: buildView()),
        ],
      ),
    );
  }
}
