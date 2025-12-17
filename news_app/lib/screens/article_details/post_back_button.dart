import 'package:flutter/material.dart';

class PostBackButton extends StatelessWidget {
  const PostBackButton({super.key});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(left: 10),
      child: CircleAvatar(
        backgroundColor: Theme.of(context).secondaryHeaderColor,
        radius: 18,
        child: IconButton(
          alignment: Alignment.center,
          icon: const Icon(Icons.arrow_back_ios_sharp, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
      ),
    );
  }
}
