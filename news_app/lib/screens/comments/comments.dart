import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/providers/user_data_provider.dart';
import 'package:news_app/screens/auth/login.dart';
import 'package:news_app/screens/comments/comment_tile.dart';
import 'package:news_app/screens/comments/comments_provider.dart';
import 'package:news_app/utils/empty_icon.dart';
import 'package:news_app/utils/loading_widget.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../components/loading_list_tile.dart';
import '../../models/article.dart';
import '../../models/comment.dart';
import '../../models/comment_user.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/snackbars.dart';

class Comments extends ConsumerStatefulWidget {
  const Comments(this.article, {super.key});

  final Article article;

  @override
  ConsumerState<Comments> createState() => _CommentsState();
}

class _CommentsState extends ConsumerState<Comments> {
  late ScrollController _controller;
  final textCtlr = TextEditingController();

  @override
  void initState() {
    _controller = ScrollController(initialScrollOffset: 0.0);
    _controller.addListener(_scrollListener);
    super.initState();
    Future.microtask(() => ref.read(commentsProvider.notifier).getData(widget.article.id, ref));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  _scrollListener() async {
    var isEnd = _controller.offset >= _controller.position.maxScrollExtent && !_controller.position.outOfRange;
    if (isEnd) {
      ref.read(commentsProvider.notifier).getData(widget.article.id, ref);
    }
  }

  _onRefresh() async {
    ref.read(hasCommentsProvider.notifier).update((state) => false);
    ref.read(isCommentsLoadingProvider.notifier).update((state) => true);
    ref.invalidate(commentsProvider);
    await ref.read(commentsProvider.notifier).getData(widget.article.id, ref);
  }

  _handleSubmit() async {
    final user = ref.read(userDataProvider)!;
    if (textCtlr.text.isNotEmpty) {
      final String id = FirebaseService.getUID('comments');
      final createdAt = DateTime.now();

      final commentUser = CommentUser(id: user.id, name: user.name, imageUrl: user.imageUrl);
      final Comment comment = Comment(
        id: id,
        articleId: widget.article.id,
        articleAuthorId: user.id,
        articleTitle: widget.article.title,
        commentUser: commentUser,
        createdAt: createdAt,
        comment: textCtlr.text,
      );
      // Save Review
      await FirebaseService().saveComment(comment);
      textCtlr.clear();
      if (!mounted) return;
      FocusScope.of(context).unfocus();

      //refresh comments
      _onRefresh();
    } else {
      openSnackbar(context, 'comment-empty');
    }
  }

  @override
  Widget build(BuildContext context) {
    final comments = ref.watch(commentsProvider);
    final hasData = ref.watch(hasCommentsProvider);
    final isLoading = ref.watch(isCommentsLoadingProvider);
    final user = ref.watch(userDataProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('comments').tr(),
        titleTextStyle: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
        centerTitle: true,
        elevation: 0,
        leading: IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(Icons.close)),
      ),
      body: Column(
        children: [
          Expanded(
            child: isLoading
                ? const LoadingListTile(
                    height: 130,
                  )
                : comments.isEmpty
                    ? EmptyPageWithIcon(icon: Icons.insert_comment_sharp, title: 'no-comments'.tr())
                    : RefreshIndicator(
                        onRefresh: () async => await _onRefresh(),
                        child: SingleChildScrollView(
                          padding: const EdgeInsets.only(left: 15, right: 15, bottom: 50, top: 15),
                          physics: const AlwaysScrollableScrollPhysics(),
                          controller: _controller,
                          child: Column(
                            children: [
                              ListView.separated(
                                physics: const NeverScrollableScrollPhysics(),
                                shrinkWrap: true,
                                itemCount: comments.length,
                                separatorBuilder: (context, index) => const Divider(height: 30),
                                itemBuilder: (BuildContext context, int index) {
                                  final Comment comment = comments[index];
                                  return CommentTile(comment: comment, onRefresh: _onRefresh);
                                },
                              ),
                              Opacity(
                                opacity: hasData ? 1.0 : 0.0,
                                child: const Padding(
                                  padding: EdgeInsets.symmetric(vertical: 30),
                                  child: LoadingIndicatorWidget(),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
          ),
          const Divider(
            height: 1,
            color: Colors.black26,
          ),
          user != null ? _commentTextField() : _loginContainer()
        ],
      ),
    );
  }

  Widget _commentTextField() {
    return SafeArea(
      child: Container(
        height: 60,
        decoration: BoxDecoration(color: Theme.of(context).cardColor),
        child: TextFormField(
          onTapOutside: (e) => FocusScope.of(context).unfocus(),
          decoration: InputDecoration(
              errorStyle: const TextStyle(fontSize: 0),
              contentPadding: const EdgeInsets.only(left: 15, top: 10, right: 5),
              border: InputBorder.none,
              hintText: 'write-comment'.tr(),
              suffixIcon: IconButton(
                icon: const Icon(
                  Icons.send,
                  size: 20,
                ),
                onPressed: () => _handleSubmit(),
              )),
          controller: textCtlr,
        ),
      ),
    );
  }

  Container _loginContainer() {
    return Container(
      height: 60,
      width: double.infinity,
      color: Theme.of(context).primaryColor,
      child: TextButton(
        onPressed: () => NextScreen.openBottomSheet(
            context,
            const LoginScreen(
              popUpScreen: true,
            )),
        child: Text(
          'login-to-make-comments',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white),
        ).tr(),
      ),
    );
  }
}
