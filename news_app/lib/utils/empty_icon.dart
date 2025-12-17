import 'package:flutter/material.dart';

class EmptyPageWithIcon extends StatelessWidget {
  const EmptyPageWithIcon({
    super.key,
    required this.icon,
    required this.title,
    this.description,
  });

  final IconData icon;
  final String title;
  final String? description;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            icon,
            color: Colors.grey,
            size: 120,
          ),
          const SizedBox(
            height: 20,
          ),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(fontSize: 18),
          ),
          const SizedBox(
            height: 20,
          ),
          description == null
              ? const SizedBox.shrink()
              : Text(
                  description ?? '',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontWeight: FontWeight.w500,
                    color: Colors.grey[600],
                    fontSize: 16,
                  ),
                )
        ],
      ),
    );
  }
}
