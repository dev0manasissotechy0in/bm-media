<?php
/**
 * Settings Helper Class
 */

class Settings {
    private static $settings = null;
    
    public static function get($key, $default = null) {
        if (self::$settings === null) {
            self::load();
        }
        return self::$settings[$key] ?? $default;
    }
    
    public static function set($key, $value) {
        $db = Database::getInstance();
        
        // Check if setting exists
        $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        
        if ($existing) {
            $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
        } else {
            $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
        }
        
        // Reload settings
        self::load();
    }
    
    public static function load() {
        $db = Database::getInstance();
        $settings = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
        
        self::$settings = [];
        foreach ($settings as $setting) {
            self::$settings[$setting['setting_key']] = $setting['setting_value'];
        }
    }
    
    public static function isGoogleLoginEnabled() {
        return self::get('google_login_enabled', '0') === '1';
    }
    
    public static function isFacebookLoginEnabled() {
        return self::get('facebook_login_enabled', '0') === '1';
    }
    
    public static function isOtpEnabled() {
        return self::get('otp_enabled', '1') === '1';
    }
}
