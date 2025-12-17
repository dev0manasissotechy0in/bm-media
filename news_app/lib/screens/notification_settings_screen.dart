import 'package:flutter/material.dart';
import '../services/enhanced_notification_service.dart';

/// Notification Settings Screen - Manage notification preferences
class NotificationSettingsScreen extends StatefulWidget {
  const NotificationSettingsScreen({Key? key}) : super(key: key);

  @override
  State<NotificationSettingsScreen> createState() =>
      _NotificationSettingsScreenState();
}

class _NotificationSettingsScreenState
    extends State<NotificationSettingsScreen> {
  final EnhancedNotificationService _notificationService =
      EnhancedNotificationService();

  bool _isLoading = true;
  bool _isSaving = false;

  Map<String, bool> _preferences = {
    'news_enabled': true,
    'breaking_enabled': true,
    'case_study_enabled': true,
    'case_study_update_enabled': true,
    'general_enabled': true,
  };

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final prefs = await _notificationService.getPreferences();
      setState(() {
        _preferences = prefs;
        _isLoading = false;
      });
    } catch (e) {
      debugPrint('Error loading preferences: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _savePreferences() async {
    setState(() {
      _isSaving = true;
    });

    try {
      final success = await _notificationService.updatePreferences(_preferences);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              success
                  ? 'Preferences saved successfully'
                  : 'Failed to save preferences',
            ),
            backgroundColor: success ? Colors.green : Colors.red,
          ),
        );
      }
    } catch (e) {
      debugPrint('Error saving preferences: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Error saving preferences'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      setState(() {
        _isSaving = false;
      });
    }
  }

  void _updatePreference(String key, bool value) {
    setState(() {
      _preferences[key] = value;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notification Settings'),
        actions: [
          if (_isSaving)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16.0),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                  ),
                ),
              ),
            )
          else
            IconButton(
              icon: const Icon(Icons.check),
              tooltip: 'Save',
              onPressed: _savePreferences,
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              children: [
                _buildSectionHeader(
                  'Notification Types',
                  'Choose which notifications you want to receive',
                ),
                _buildSettingTile(
                  icon: Icons.article,
                  iconColor: Colors.blue,
                  title: 'News Articles',
                  subtitle: 'Get notified when new articles are published',
                  value: _preferences['news_enabled'] ?? true,
                  onChanged: (value) => _updatePreference('news_enabled', value),
                ),
                _buildDivider(),
                _buildSettingTile(
                  icon: Icons.bolt,
                  iconColor: Colors.red,
                  title: 'Breaking News',
                  subtitle: 'Instant alerts for breaking news stories',
                  value: _preferences['breaking_enabled'] ?? true,
                  onChanged: (value) =>
                      _updatePreference('breaking_enabled', value),
                ),
                _buildDivider(),
                _buildSettingTile(
                  icon: Icons.folder_special,
                  iconColor: Colors.purple,
                  title: 'Case Studies',
                  subtitle: 'Notifications for new case studies',
                  value: _preferences['case_study_enabled'] ?? true,
                  onChanged: (value) =>
                      _updatePreference('case_study_enabled', value),
                ),
                _buildDivider(),
                _buildSettingTile(
                  icon: Icons.update,
                  iconColor: Colors.orange,
                  title: 'Case Study Updates',
                  subtitle: 'Get notified when case studies are updated',
                  value: _preferences['case_study_update_enabled'] ?? true,
                  onChanged: (value) =>
                      _updatePreference('case_study_update_enabled', value),
                ),
                _buildDivider(),
                _buildSettingTile(
                  icon: Icons.notifications,
                  iconColor: Colors.grey,
                  title: 'General Notifications',
                  subtitle: 'Other announcements and updates',
                  value: _preferences['general_enabled'] ?? true,
                  onChanged: (value) =>
                      _updatePreference('general_enabled', value),
                ),
                const SizedBox(height: 24),
                _buildSectionHeader(
                  'Device Information',
                  'Your device token for push notifications',
                ),
                _buildDeviceInfo(),
                const SizedBox(height: 24),
                _buildAboutSection(),
              ],
            ),
    );
  }

  Widget _buildSectionHeader(String title, String subtitle) {
    return Container(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey[600],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSettingTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required bool value,
    required Function(bool) onChanged,
  }) {
    return ListTile(
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: iconColor.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, color: iconColor, size: 24),
      ),
      title: Text(
        title,
        style: const TextStyle(
          fontSize: 15,
          fontWeight: FontWeight.w500,
        ),
      ),
      subtitle: Text(
        subtitle,
        style: TextStyle(
          fontSize: 13,
          color: Colors.grey[600],
        ),
      ),
      trailing: Switch(
        value: value,
        onChanged: onChanged,
        activeColor: Colors.blue,
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
    );
  }

  Widget _buildDivider() {
    return Divider(
      height: 1,
      indent: 72,
      endIndent: 16,
      color: Colors.grey[300],
    );
  }

  Widget _buildDeviceInfo() {
    final fcmToken = _notificationService.fcmToken;
    
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.phone_android, color: Colors.grey[700], size: 20),
              const SizedBox(width: 8),
              Text(
                'Device Token',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[700],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            fcmToken ?? 'Not available',
            style: TextStyle(
              fontSize: 11,
              color: Colors.grey[600],
              fontFamily: 'monospace',
            ),
            maxLines: 3,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Icon(
                _notificationService.isInitialized
                    ? Icons.check_circle
                    : Icons.error,
                color: _notificationService.isInitialized
                    ? Colors.green
                    : Colors.red,
                size: 16,
              ),
              const SizedBox(width: 6),
              Text(
                _notificationService.isInitialized
                    ? 'Connected'
                    : 'Not Connected',
                style: TextStyle(
                  fontSize: 13,
                  color: _notificationService.isInitialized
                      ? Colors.green
                      : Colors.red,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAboutSection() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.blue[50],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.blue[200]!),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.info_outline, color: Colors.blue[700], size: 20),
              const SizedBox(width: 8),
              Text(
                'About Notifications',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: Colors.blue[700],
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'You can customize which types of notifications you receive. Changes are saved automatically to your account and synced across all your devices.',
            style: TextStyle(
              fontSize: 13,
              color: Colors.grey[700],
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }
}
