import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:introduction_screen/introduction_screen.dart';
import 'package:easy_localization/easy_localization.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/screens/splash.dart';
import 'package:news_app/services/sp_service.dart';
import '../utils/next_screen.dart';

class IntroScreen extends StatefulWidget {
  const IntroScreen({super.key});

  @override
  State<IntroScreen> createState() => _IntroScreenState();
}

class _IntroScreenState extends State<IntroScreen> {
  void _onIntroComeplete() async{
    await SPService().setIntroDone();
    if (!mounted) return;
    NextScreen.replaceAnimation(context, const SplashScreen());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IntroductionScreen(
        pages: [
          _introView(context, 'intro-title1', 'intro-description1', introImage1),
          _introView(context, 'intro-title2', 'intro-description2', introImage2),
          _introView(context, 'intro-title3', 'intro-description3', introImage3)
        ],
        onDone: () => _onIntroComeplete(),
        onSkip: () => _onIntroComeplete(),
        globalBackgroundColor: Theme.of(context).scaffoldBackgroundColor,
        showSkipButton: true,
        skip: const Text('skip', style: TextStyle(fontWeight: FontWeight.w600, color: Colors.grey)).tr(),
        next: const Icon(Icons.navigate_next),
        done: const Text("done", style: TextStyle(fontWeight: FontWeight.w600)).tr(),
        dotsDecorator: DotsDecorator(
          size: const Size.square(7.0),
          activeSize: const Size(20.0, 5.0),
          activeColor: Theme.of(context).primaryColor,
          color: Colors.black26,
          spacing: const EdgeInsets.symmetric(horizontal: 3.0),
          activeShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5.0)),
        ),
      ),
    );
  }
}

PageViewModel _introView(context, String title, String subtitle, String image) {
  return PageViewModel(
    titleWidget: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: <Widget>[
        Text(
          title,
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
        ).tr(),
        const SizedBox(
          height: 8,
        ),
        Container(
          height: 3,
          width: 100,
          decoration: BoxDecoration(color: Theme.of(context).primaryColor, borderRadius: BorderRadius.circular(10)),
        )
      ],
    ),
    body: subtitle.tr(),
    image: Center(child: SvgPicture.asset(image)),
    decoration: const PageDecoration(
      bodyTextStyle: TextStyle(fontSize: 18, fontWeight: FontWeight.w400),
      bodyPadding: EdgeInsets.only(left: 30, right: 30),
      imagePadding: EdgeInsets.only(left: 30, right: 30, top: 60),
      bodyAlignment: Alignment.center,
      imageAlignment: Alignment.center,
      bodyFlex: 0,
    ),
  );
}
