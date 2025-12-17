# Westack Font Integration Guide

## Font Files Required

Place the following Westack font files in the directory:
`news_app/assets/fonts/westack/`

### Required Font Files:
1. **Westack-Regular.ttf** - Regular weight (400)
2. **Westack-Medium.ttf** - Medium weight (500)
3. **Westack-Bold.ttf** - Bold weight (700)
4. **Westack-Light.ttf** - Light weight (300)

## Directory Structure

```
news_app/
├── assets/
│   ├── fonts/
│   │   └── westack/
│   │       ├── Westack-Regular.ttf
│   │       ├── Westack-Medium.ttf
│   │       ├── Westack-Bold.ttf
│   │       └── Westack-Light.ttf
│   ├── images/
│   └── animation_files/
```

## Configuration

The font is already configured in `pubspec.yaml`:

```yaml
fonts:
  - family: Westack
    fonts:
      - asset: assets/fonts/westack/Westack-Regular.ttf
      - asset: assets/fonts/westack/Westack-Bold.ttf
        weight: 700
      - asset: assets/fonts/westack/Westack-Medium.ttf
        weight: 500
      - asset: assets/fonts/westack/Westack-Light.ttf
        weight: 300
```

## Usage

### App Logo
The AppLogo widget now uses Westack font for displaying the app name:

```dart
Text(
  branding.appName,
  style: TextStyle(
    fontFamily: 'Westack',
    fontSize: 24,
    fontWeight: FontWeight.bold,
  ),
);
```

### Category Names
To use Westack font for category names, update your category display widgets:

```dart
Text(
  categoryName,
  style: TextStyle(
    fontFamily: 'Westack',
    fontSize: 16,
    fontWeight: FontWeight.w500,
  ),
);
```

## Steps to Complete Integration

1. **Obtain Westack font files** (.ttf format)
2. **Create directory**: `news_app/assets/fonts/westack/`
3. **Copy font files** into the westack directory
4. **Run**: `flutter pub get` to register the new assets
5. **Restart the app** to see changes

## Verification

After adding the font files:

1. Check that all 4 font files exist in the correct directory
2. Run `flutter pub get`
3. Build the app: `flutter run`
4. The app logo should display using Westack font
5. Category names should use Westack font

## Troubleshooting

### Font Not Showing
- Ensure font files are in the correct directory
- Run `flutter clean` then `flutter pub get`
- Verify font file names match exactly in pubspec.yaml
- Restart the app completely

### Font Files Missing
If you don't have the Westack font files:
- Contact the font provider or designer
- Use a similar font as alternative (e.g., Poppins, Montserrat)
- Or use system fonts by removing fontFamily property

## Fallback
If Westack fonts are not available, the app will automatically fallback to system default fonts.
