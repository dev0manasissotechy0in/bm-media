<?php
/**
 * Ads Manager Helper Class
 * Handles displaying ads on frontend
 */

class AdsManager {
    private static $db = null;
    private static $adsense_settings = null;
    private static $custom_ads = null;

    /**
     * Initialize the ads manager
     */
    public static function init($db_instance = null) {
        if ($db_instance) {
            self::$db = $db_instance;
        } else {
            self::$db = Database::getInstance();
        }
        self::loadSettings();
    }

    /**
     * Load ads settings and data
     */
    private static function loadSettings() {
        if (self::$db) {
            try {
                // Check if ads_settings table exists
                $tables_check = self::$db->fetchOne("SHOW TABLES LIKE 'ads_settings'");
                
                if (!$tables_check) {
                    // Tables don't exist yet, silently return
                    self::$adsense_settings = null;
                    self::$custom_ads = [];
                    return;
                }
                
                // Load AdSense settings
                self::$adsense_settings = self::$db->fetchOne(
                    "SELECT * FROM ads_settings WHERE type = 'google_adsense' AND enabled = 1 LIMIT 1"
                );

                // Load active custom ads
                self::$custom_ads = self::$db->fetchAll(
                    "SELECT * FROM custom_ads WHERE status = 1 ORDER BY placement, position"
                ) ?: [];
            } catch (Exception $e) {
                // Silently fail if tables don't exist
                self::$adsense_settings = null;
                self::$custom_ads = [];
            }
        }
    }

    /**
     * Check if Google AdSense is enabled
     */
    public static function isAdsenseEnabled() {
        return !empty(self::$adsense_settings);
    }

    /**
     * Get Google AdSense script tag
     */
    public static function getAdsenseScript() {
        if (!self::isAdsenseEnabled() || empty(self::$adsense_settings['client_id'])) {
            return '';
        }

        $client_id = htmlspecialchars(self::$adsense_settings['client_id']);
        return <<<HTML
<!-- Google AdSense -->
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$client_id}"
     crossorigin="anonymous"></script>
HTML;
    }

    /**
     * Display AdSense banner ad
     */
    public static function showBannerAd() {
        if (!self::isAdsenseEnabled() || empty(self::$adsense_settings['ad_slot_banner'])) {
            return '';
        }

        $slot = htmlspecialchars(self::$adsense_settings['ad_slot_banner']);
        $client = htmlspecialchars(self::$adsense_settings['client_id']);

        return <<<HTML
<div class="ad-banner-wrapper mb-4">
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="{$client}"
         data-ad-slot="{$slot}"
         data-ad-format="auto"
         data-full-width-responsive="true"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>
HTML;
    }

    /**
     * Display AdSense sidebar ad
     */
    public static function showSidebarAd() {
        if (!self::isAdsenseEnabled() || empty(self::$adsense_settings['ad_slot_sidebar'])) {
            return '';
        }

        $slot = htmlspecialchars(self::$adsense_settings['ad_slot_sidebar']);
        $client = htmlspecialchars(self::$adsense_settings['client_id']);

        return <<<HTML
<div class="ad-sidebar-wrapper mb-4">
    <ins class="adsbygoogle"
         style="display:block"
         data-ad-client="{$client}"
         data-ad-slot="{$slot}"
         data-ad-format="vertical"
         data-full-width-responsive="true"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>
HTML;
    }

    /**
     * Display AdSense article/in-content ad
     */
    public static function showArticleAd() {
        if (!self::isAdsenseEnabled() || empty(self::$adsense_settings['ad_slot_article'])) {
            return '';
        }

        $slot = htmlspecialchars(self::$adsense_settings['ad_slot_article']);
        $client = htmlspecialchars(self::$adsense_settings['client_id']);

        return <<<HTML
<div class="ad-article-wrapper my-4">
    <ins class="adsbygoogle"
         style="display:block; text-align:center;"
         data-ad-layout="in-article"
         data-ad-format="fluid"
         data-ad-client="{$client}"
         data-ad-slot="{$slot}"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>
HTML;
    }

    /**
     * Get custom ads for specific placement
     */
    public static function getCustomAds($placement = null, $position = null) {
        $ads = self::$custom_ads;

        if ($placement) {
            $ads = array_filter($ads, function($ad) use ($placement) {
                return $ad['placement'] === $placement;
            });
        }

        if ($position) {
            $ads = array_filter($ads, function($ad) use ($position) {
                return $ad['position'] === $position;
            });
        }

        return array_values($ads);
    }

    /**
     * Display custom ad HTML
     */
    public static function showCustomAd($placement, $position = 'top') {
        $ads = self::getCustomAds($placement, $position);

        if (empty($ads)) {
            return '';
        }

        $ad = $ads[0]; // Get first matching ad
        $html = <<<HTML
<div class="custom-ad-wrapper custom-ad-{$ad['placement']} mb-4" data-ad-id="{$ad['id']}">
    {$ad['code']}
</div>
HTML;

        // Track impression
        self::trackImpression($ad['id'], 'custom');

        return $html;
    }

    /**
     * Display all custom ads for placement
     */
    public static function showAllCustomAds($placement) {
        $ads = self::getCustomAds($placement);

        if (empty($ads)) {
            return '';
        }

        $html = '';
        foreach ($ads as $ad) {
            $html .= <<<HTML
<div class="custom-ad-wrapper custom-ad-{$ad['placement']} mb-4" data-ad-id="{$ad['id']}">
    {$ad['code']}
</div>
HTML;
            // Track impression
            self::trackImpression($ad['id'], 'custom');
        }

        return $html;
    }

    /**
     * Track ad impression
     */
    private static function trackImpression($ad_id, $ad_type = 'custom') {
        if (!self::$db) {
            return;
        }

        try {
            // Check if ad_analytics table exists
            $table_check = self::$db->fetchOne("SHOW TABLES LIKE 'ad_analytics'");
            if (!$table_check) {
                return; // Table doesn't exist, skip tracking
            }
            
            self::$db->insert('ad_analytics', [
                'ad_id' => $ad_id,
                'ad_type' => $ad_type,
                'event_type' => 'impression',
                'page_url' => $_SERVER['REQUEST_URI'] ?? '',
                'user_ip' => self::getUserIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Silently fail to not break the page
        }
    }

    /**
     * Get user IP address
     */
    private static function getUserIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'UNKNOWN';
    }

    /**
     * Get ad statistics
     */
    public static function getAdStats($ad_id, $ad_type = 'custom') {
        if (!self::$db) {
            return null;
        }

        $impressions = self::$db->fetchOne(
            "SELECT COUNT(*) as count FROM ad_analytics WHERE ad_id = ? AND ad_type = ? AND event_type = 'impression'",
            [$ad_id, $ad_type]
        )['count'] ?? 0;

        $clicks = self::$db->fetchOne(
            "SELECT COUNT(*) as count FROM ad_analytics WHERE ad_id = ? AND ad_type = ? AND event_type = 'click'",
            [$ad_id, $ad_type]
        )['count'] ?? 0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0
        ];
    }
}
