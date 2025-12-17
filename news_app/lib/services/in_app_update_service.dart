import 'package:flutter/material.dart';
import 'package:in_app_update/in_app_update.dart';

class InAppUpdateService {
  static final InAppUpdateService _instance = InAppUpdateService._internal();
  factory InAppUpdateService() => _instance;
  InAppUpdateService._internal();

  /// Check for available updates and show update dialog
  Future<void> checkForUpdates(BuildContext context) async {
    try {
      debugPrint('🔄 Checking for app updates...');

      // Check if update is available
      final updateInfo = await InAppUpdate.checkForUpdate();

      if (updateInfo.updateAvailability == UpdateAvailability.updateAvailable) {
        debugPrint('✅ Update available');
        debugPrint('   Available Version Code: ${updateInfo.availableVersionCode}');
        debugPrint('   Update Priority: ${updateInfo.updatePriority}');
        debugPrint('   Immediate Update Allowed: ${updateInfo.immediateUpdateAllowed}');
        debugPrint('   Flexible Update Allowed: ${updateInfo.flexibleUpdateAllowed}');

        // High priority updates should be immediate
        if (updateInfo.updatePriority >= 4 && updateInfo.immediateUpdateAllowed) {
          await _performImmediateUpdate();
        } else if (updateInfo.flexibleUpdateAllowed) {
          await _performFlexibleUpdate(context);
        }
      } else {
        debugPrint('✅ App is up to date');
      }
    } catch (e) {
      debugPrint('Error checking for updates: $e');
    }
  }

  /// Perform immediate update (blocks app usage)
  Future<void> _performImmediateUpdate() async {
    try {
      debugPrint('🚀 Starting immediate update...');
      await InAppUpdate.performImmediateUpdate();
      debugPrint('✅ Immediate update completed');
    } catch (e) {
      debugPrint('Error performing immediate update: $e');
    }
  }

  /// Perform flexible update (background download)
  Future<void> _performFlexibleUpdate(BuildContext context) async {
    try {
      debugPrint('📥 Starting flexible update...');

      // Show update dialog
      final shouldUpdate = await _showUpdateDialog(context);
      if (!shouldUpdate) {
        debugPrint('User declined update');
        return;
      }

      // Start flexible update
      await InAppUpdate.startFlexibleUpdate();
      debugPrint('✅ Flexible update started');

      // Show install prompt when download completes
      await InAppUpdate.completeFlexibleUpdate();
      debugPrint('✅ Flexible update completed');
    } catch (e) {
      debugPrint('Error performing flexible update: $e');
    }
  }

  /// Show update dialog
  Future<bool> _showUpdateDialog(BuildContext context) async {
    return await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
        ),
        title: Row(
          children: [
            Icon(
              Icons.system_update,
              color: Theme.of(context).primaryColor,
            ),
            const SizedBox(width: 12),
            const Text('Update Available'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'A new version of the app is available with improvements and bug fixes.',
              style: TextStyle(fontSize: 15),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: Colors.blue.withOpacity(0.3),
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.info_outline,
                    size: 20,
                    color: Theme.of(context).primaryColor,
                  ),
                  const SizedBox(width: 8),
                  const Expanded(
                    child: Text(
                      'Update will download in the background',
                      style: TextStyle(fontSize: 13),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Later'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: const Text('Update'),
          ),
        ],
      ),
    ) ?? false;
  }

  /// Check for critical updates (on app launch)
  Future<void> checkForCriticalUpdates(BuildContext context) async {
    try {
      final updateInfo = await InAppUpdate.checkForUpdate();

      // Force immediate update for critical updates (priority >= 5)
      if (updateInfo.updateAvailability == UpdateAvailability.updateAvailable &&
          updateInfo.updatePriority >= 5 &&
          updateInfo.immediateUpdateAllowed) {
        debugPrint('⚠️ Critical update required');
        await _performImmediateUpdate();
      }
    } catch (e) {
      debugPrint('Error checking for critical updates: $e');
    }
  }
}
