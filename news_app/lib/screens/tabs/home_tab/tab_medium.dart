import 'package:flutter/material.dart';
import 'package:news_app/models/app_settings_model.dart';

import 'custom_category_tab.dart';
import 'tab0/tab0.dart';

class TabMedium extends StatefulWidget {
  final ScrollController sc;
  final TabController tc;
  final List<HomeCategory> homeCategories;
  final AppSettingsModel settings;
  const TabMedium({super.key, required this.sc, required this.tc, required this.homeCategories, required this.settings});

  @override
  State<TabMedium> createState() => _TabMediumState();
}

class _TabMediumState extends State<TabMedium> {
  late List<Widget> _categoryTabs;
  late final List<Widget> _childrens = [];

  @override
  void initState() {
    _categoryTabs = widget.homeCategories
        .map((e) => CustomCategoryTab(
              category: e,
              sc: widget.sc,
              key: UniqueKey(),
            ))
        .toList();
    _childrens.insert(
        0,
        Tab0(
          key: UniqueKey(),
          sc: widget.sc,
          settings: widget.settings,
        ));
    _childrens.addAll(_categoryTabs);
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return TabBarView(
      key: UniqueKey(),
      controller: widget.tc,
      children: _childrens,
    );
  }
}