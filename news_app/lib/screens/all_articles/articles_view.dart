import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/ad_manager.dart';
import 'package:news_app/ads/banner_ad.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/utils/empty_animation.dart';
import '../../components/article_tiles/article_tile1.dart';
import '../../components/loading_list_tile.dart';
import '../../models/article.dart';
import '../../services/firestore_stub.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/cache_image_filter.dart';
import '../../utils/loading_widget.dart';

enum ArticlesBy {
  category,
  tag,
  author,
  popular,
  latest,
}

class AllArticlesView extends ConsumerStatefulWidget {
  const AllArticlesView({
    super.key,
    required this.articlesBy,
    this.categoryId,
    this.tagId,
    this.authorId,
    required this.title,
    this.heroTag,
    this.thumbnail,
  });

  final String title;
  final ArticlesBy articlesBy;
  final String? categoryId, tagId, authorId, thumbnail;
  final Object? heroTag;

  @override
  ConsumerState<AllArticlesView> createState() => _AllArticlesViewState();
}

class _AllArticlesViewState extends ConsumerState<AllArticlesView> {
  List<Article> _articles = [];
  bool _hasData = false;
  bool _isLoading = true;
  DocumentSnapshot? _lastDocument;
  late ScrollController _controller;

  @override
  void initState() {
    _controller = ScrollController(initialScrollOffset: 0.0);
    _controller.addListener(_scrollListener);
    super.initState();
    _getData();
  }

  _scrollListener() async {
    var isEnd = _controller.offset >= _controller.position.maxScrollExtent && !_controller.position.outOfRange;
    if (isEnd) {
      _getData();
    }
  }

  Future<QuerySnapshot> _getArticleQuery() async {
    QuerySnapshot snapshot;
    if (widget.articlesBy == ArticlesBy.popular) {
      snapshot = await FirebaseService().getArticlesSnapshotByPopular(lastDocument: _lastDocument);
    } else if (widget.articlesBy == ArticlesBy.category) {
      snapshot = await FirebaseService().getArticlesSnapshotByCategory(categoryId: widget.categoryId!, lastDocument: _lastDocument);
    } else if (widget.articlesBy == ArticlesBy.tag) {
      snapshot = await FirebaseService().getArticlesSnapshotByTag(tagId: widget.tagId!, lastDocument: _lastDocument);
    } else if (widget.articlesBy == ArticlesBy.author) {
      snapshot = await FirebaseService().getArticlesSnapshotByAuhtor(authorId: widget.authorId!, lastDocument: _lastDocument);
    } else {
      snapshot = await FirebaseService().getArticlesSnapshotByPopular(lastDocument: _lastDocument);
    }
    return snapshot;
  }

  _getData() async {
    if (_lastDocument == null) {
      await _getArticleQuery().then((QuerySnapshot snapshot) {
        _articles = snapshot.docs.map((e) => Article.fromFirestore(e)).toList();
        _lastDocument = snapshot.docs.last;
        _isLoading = false;
        setState(() {});
      }).catchError((e) => _handleError(e.toString()));
    } else {
      _hasData = true;
      setState(() {});
      await _getArticleQuery().then((QuerySnapshot? snapshot) {
        _articles.addAll(snapshot!.docs.map((e) => Article.fromFirestore(e)).toList());
        _lastDocument = snapshot.docs.last;
        _hasData = false;
        setState(() {});
      }).catchError((e) => _handleError(e.toString()));
    }
  }

  _handleError(String error) {
    setState(() {
      _isLoading = false;
      _hasData = false;
    });
    debugPrint(error);
  }

  _onRefresh() async {
    _isLoading = true;
    _articles.clear();
    _hasData = false;
    _lastDocument = null;
    setState(() {});
    await _getData();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: RefreshIndicator(
        onRefresh: () async => _onRefresh(),
        child: CustomScrollView(
          controller: _controller,
          slivers: <Widget>[
            SliverAppBar(
              automaticallyImplyLeading: false,
              pinned: true,
              actions: <Widget>[
                IconButton(
                  icon: const Icon(
                    Icons.close,
                    color: Colors.white,
                  ),
                  onPressed: () {
                    Navigator.pop(context);
                  },
                )
              ],
              backgroundColor: Theme.of(context).primaryColor,
              expandedHeight: MediaQuery.of(context).size.height * 0.15,
              elevation: 0.5,
              flexibleSpace: _flexibleSpaceBar(),
            ),
            SliverToBoxAdapter(
              child: SingleChildScrollView(
                child: Column(
                  children: [
                    _isLoading
                        ? const LoadingListTile()
                        : _articles.isEmpty
                            ? ListView(
                                shrinkWrap: true,
                                children: [
                                  SizedBox(height: MediaQuery.of(context).size.height * 0.10),
                                  EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr()),
                                ],
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.all(15),
                                physics: const NeverScrollableScrollPhysics(),
                                shrinkWrap: true,
                                itemCount: _articles.length,
                                separatorBuilder: (context, index) => const SizedBox(height: 20),
                                itemBuilder: (context, index) {
                                  final Article article = _articles[index];
                                  return Column(
                                    children: [
                                      InlineAds(ref: ref, index: index),
                                      ArticleTile(article: article),
                                    ],
                                  );
                                },
                              ),
                    Opacity(opacity: _hasData ? 1.0 : 0.0, child: const LoadingIndicatorWidget()),
                  ],
                ),
              ),
            )
          ],
        ),
      ),
    );
  }

  FlexibleSpaceBar _flexibleSpaceBar() {
    return FlexibleSpaceBar(
      centerTitle: false,
      background: widget.heroTag != null && widget.thumbnail != null
          ? Container(
              color: Theme.of(context).primaryColor,
              width: double.infinity,
              child: Hero(
                tag: widget.heroTag ?? '',
                child: CustomCacheImageWithDarkFilterBottom(imageUrl: widget.thumbnail, radius: 0.0),
              ),
            )
          : null,
      title: Text(
        widget.title.toString(),
        style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, color: Colors.white),
      ),
      titlePadding: const EdgeInsets.only(left: 20, bottom: 15, right: 20),
    );
  }
}
