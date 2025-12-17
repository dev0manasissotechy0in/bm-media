import 'package:flutter/material.dart';
import 'package:easy_localization/easy_localization.dart';
import '../../services/case_api_service.dart';
import '../../models/case_thread.dart';
import '../../configs/app_assets.dart';
import '../../utils/empty_animation.dart';
import 'case_thread_detail_screen.dart';

class CaseThreadsListScreen extends StatefulWidget {
  const CaseThreadsListScreen({super.key});

  @override
  State<CaseThreadsListScreen> createState() => _CaseThreadsListScreenState();
}

class _CaseThreadsListScreenState extends State<CaseThreadsListScreen> {
  List<CaseThread> cases = [];
  bool isLoading = true;
  String? selectedCategory;
  String? selectedStatus;
  String searchQuery = '';

  @override
  void initState() {
    super.initState();
    loadCases();
  }

  Future<void> loadCases() async {
    setState(() => isLoading = true);

    try {
      final result = await CaseApiService.getCases(
        category: selectedCategory,
        status: selectedStatus,
        search: searchQuery.isNotEmpty ? searchQuery : null,
      );

      setState(() {
        cases = result['cases'];
        isLoading = false;
      });
    } catch (e) {
      setState(() => isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load cases: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Case Threads', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: showFilterDialog,
          ),
          IconButton(
            icon: const Icon(Icons.search),
            onPressed: showSearchDialog,
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : cases.isEmpty
              ? EmptyAnimation(
                  animationString: emptyAnimation,
                  title: 'No case threads available'.tr(),
                )
              : RefreshIndicator(
                  onRefresh: loadCases,
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: cases.length,
                    itemBuilder: (context, index) {
                      final caseThread = cases[index];
                      return CaseCard(caseThread: caseThread);
                    },
                  ),
                ),
    );
  }

  void showFilterDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Filter Cases', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 20),
            const Text('Category', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: ['All', 'Crime', 'War', 'Corporate', 'Political', 'Environmental']
                  .map((cat) => FilterChip(
                        label: Text(cat),
                        selected: cat == 'All' ? selectedCategory == null : selectedCategory == cat.toLowerCase(),
                        onSelected: (selected) {
                          setState(() {
                            selectedCategory = cat == 'All' ? null : cat.toLowerCase();
                          });
                          Navigator.pop(context);
                          loadCases();
                        },
                      ))
                  .toList(),
            ),
            const SizedBox(height: 20),
            const Text('Status', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              children: ['All', 'Active', 'Concluded', 'Archived']
                  .map((stat) => FilterChip(
                        label: Text(stat),
                        selected: stat == 'All' ? selectedStatus == null : selectedStatus == stat.toLowerCase(),
                        onSelected: (selected) {
                          setState(() {
                            selectedStatus = stat == 'All' ? null : stat.toLowerCase();
                          });
                          Navigator.pop(context);
                          loadCases();
                        },
                      ))
                  .toList(),
            ),
          ],
        ),
      ),
    );
  }

  void showSearchDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Search Cases'),
        content: TextField(
          decoration: const InputDecoration(
            hintText: 'Enter case name or keyword...',
            border: OutlineInputBorder(),
          ),
          onChanged: (value) => searchQuery = value,
          onSubmitted: (value) {
            Navigator.pop(context);
            loadCases();
          },
        ),
        actions: [
          TextButton(
            onPressed: () {
              searchQuery = '';
              Navigator.pop(context);
              loadCases();
            },
            child: const Text('Clear'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              loadCases();
            },
            child: const Text('Search'),
          ),
        ],
      ),
    );
  }
}

class CaseCard extends StatelessWidget {
  final CaseThread caseThread;

  const CaseCard({super.key, required this.caseThread});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      elevation: 2,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => CaseThreadDetailScreen(caseId: caseThread.id),
            ),
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (caseThread.thumbnail != null)
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                child: Image.network(
                  caseThread.thumbnail!,
                  width: double.infinity,
                  height: 180,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) {
                    return Container(
                      width: double.infinity,
                      height: 180,
                      color: Colors.grey[300],
                      child: const Icon(Icons.broken_image, size: 50, color: Colors.grey),
                    );
                  },
                ),
              ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: _getCategoryColor(caseThread.category),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          caseThread.category.toUpperCase(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: _getStatusColor(caseThread.status),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          caseThread.status.toUpperCase(),
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    caseThread.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    caseThread.shortDescription,
                    style: TextStyle(color: Colors.grey[600], fontSize: 14),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      const Icon(Icons.article, size: 16, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text('${caseThread.totalArticles} articles',
                          style: const TextStyle(color: Colors.grey, fontSize: 12)),
                      const SizedBox(width: 16),
                      const Icon(Icons.people, size: 16, color: Colors.grey),
                      const SizedBox(width: 4),
                      Text('${caseThread.totalFollowers} followers',
                          style: const TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _getCategoryColor(String category) {
    switch (category.toLowerCase()) {
      case 'crime':
        return Colors.red;
      case 'war':
        return Colors.orange;
      case 'corporate':
        return Colors.purple;
      case 'political':
        return Colors.blue;
      case 'environmental':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active':
        return Colors.green;
      case 'concluded':
        return Colors.grey;
      case 'archived':
        return Colors.orange;
      default:
        return Colors.blue;
    }
  }
}
