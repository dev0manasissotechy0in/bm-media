/**
 * Cookie Consent & Tracking JavaScript
 * Handles cookie preferences and user interaction tracking
 */

class CookieManager {
    constructor() {
        this.preferences = this.loadPreferences();
        this.init();
    }
    
    init() {
        // Show banner if no preference saved
        if (!this.preferences) {
            this.showBanner();
        } else {
            // Apply saved preferences
            this.applyPreferences(this.preferences);
        }
        
        // Initialize tracking if consent given
        if (this.preferences && this.preferences.analytics) {
            this.initTracking();
        }
    }
    
    showBanner() {
        const banner = document.createElement('div');
        banner.id = 'cookie-consent-banner';
        banner.className = 'cookie-banner';
        banner.innerHTML = `
            <div class="cookie-banner-content">
                <div class="cookie-icon">🍪</div>
                <div class="cookie-text">
                    <h5>We use cookies</h5>
                    <p>We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.</p>
                </div>
                <div class="cookie-actions">
                    <button onclick="cookieManager.acceptAll()" class="btn btn-primary">Accept All</button>
                    <button onclick="cookieManager.acceptNecessary()" class="btn btn-outline-secondary">Necessary Only</button>
                    <button onclick="cookieManager.showPreferences()" class="btn btn-link">Customize</button>
                </div>
            </div>
        `;
        document.body.appendChild(banner);
        
        // Add CSS if not already present
        if (!document.getElementById('cookie-styles')) {
            const style = document.createElement('style');
            style.id = 'cookie-styles';
            style.textContent = this.getStyles();
            document.head.appendChild(style);
        }
    }
    
    showPreferences() {
        const modal = document.createElement('div');
        modal.id = 'cookie-preferences-modal';
        modal.className = 'cookie-modal';
        modal.innerHTML = `
            <div class="cookie-modal-content">
                <div class="cookie-modal-header">
                    <h4>Cookie Preferences</h4>
                    <button onclick="cookieManager.closeModal()" class="btn-close"></button>
                </div>
                <div class="cookie-modal-body">
                    <div class="cookie-category">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Necessary Cookies</h6>
                                <p class="text-muted small">Required for the website to function properly</p>
                            </div>
                            <input type="checkbox" id="necessary" checked disabled>
                        </div>
                    </div>
                    <div class="cookie-category">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Functional Cookies</h6>
                                <p class="text-muted small">Remember your preferences and settings</p>
                            </div>
                            <input type="checkbox" id="functional" checked>
                        </div>
                    </div>
                    <div class="cookie-category">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Analytics Cookies</h6>
                                <p class="text-muted small">Help us understand how visitors use our site</p>
                            </div>
                            <input type="checkbox" id="analytics">
                        </div>
                    </div>
                    <div class="cookie-category">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6>Marketing Cookies</h6>
                                <p class="text-muted small">Used to show you relevant ads</p>
                            </div>
                            <input type="checkbox" id="marketing">
                        </div>
                    </div>
                </div>
                <div class="cookie-modal-footer">
                    <button onclick="cookieManager.savePreferences()" class="btn btn-primary">Save Preferences</button>
                    <button onclick="cookieManager.acceptAll()" class="btn btn-success">Accept All</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    acceptAll() {
        const preferences = {
            necessary: true,
            functional: true,
            analytics: true,
            marketing: true,
            timestamp: new Date().toISOString()
        };
        this.savePreferencesToServer(preferences);
        this.applyPreferences(preferences);
        this.closeBanner();
        this.closeModal();
    }
    
    acceptNecessary() {
        const preferences = {
            necessary: true,
            functional: false,
            analytics: false,
            marketing: false,
            timestamp: new Date().toISOString()
        };
        this.savePreferencesToServer(preferences);
        this.applyPreferences(preferences);
        this.closeBanner();
    }
    
    savePreferences() {
        const preferences = {
            necessary: true, // Always true
            functional: document.getElementById('functional')?.checked || false,
            analytics: document.getElementById('analytics')?.checked || false,
            marketing: document.getElementById('marketing')?.checked || false,
            timestamp: new Date().toISOString()
        };
        this.savePreferencesToServer(preferences);
        this.applyPreferences(preferences);
        this.closeModal();
        this.closeBanner();
    }
    
    savePreferencesToServer(preferences) {
        // Save to localStorage
        localStorage.setItem('cookie_preferences', JSON.stringify(preferences));
        this.preferences = preferences;
        
        // Send to server
        fetch('/api/cookies/save-preferences.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(preferences)
        });
    }
    
    loadPreferences() {
        const stored = localStorage.getItem('cookie_preferences');
        return stored ? JSON.parse(stored) : null;
    }
    
    applyPreferences(preferences) {
        // Disable analytics scripts if not consented
        if (!preferences.analytics) {
            this.disableAnalytics();
        } else {
            this.initTracking();
        }
        
        // Disable marketing scripts if not consented
        if (!preferences.marketing) {
            this.disableMarketing();
        }
    }
    
    disableAnalytics() {
        // Disable Google Analytics, etc.
        window['ga-disable-UA-XXXXXX-X'] = true;
    }
    
    disableMarketing() {
        // Disable ad scripts
    }
    
    closeBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) banner.remove();
    }
    
    closeModal() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) modal.remove();
    }
    
    initTracking() {
        if (window.userTracker) {
            window.userTracker.enable();
        }
    }
    
    getStyles() {
        return `
            .cookie-banner {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #fff;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                padding: 20px;
                z-index: 9999;
                animation: slideUp 0.3s ease;
            }
            
            @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
            }
            
            .cookie-banner-content {
                max-width: 1200px;
                margin: 0 auto;
                display: flex;
                align-items: center;
                gap: 20px;
                flex-wrap: wrap;
            }
            
            .cookie-icon {
                font-size: 2rem;
            }
            
            .cookie-text {
                flex: 1;
                min-width: 300px;
            }
            
            .cookie-text h5 {
                margin: 0 0 5px 0;
                font-size: 1.1rem;
            }
            
            .cookie-text p {
                margin: 0;
                font-size: 0.9rem;
                color: #666;
            }
            
            .cookie-actions {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .cookie-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s ease;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            .cookie-modal-content {
                background: #fff;
                border-radius: 8px;
                max-width: 600px;
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
            }
            
            .cookie-modal-header {
                padding: 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .cookie-modal-body {
                padding: 20px;
            }
            
            .cookie-category {
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin-bottom: 15px;
            }
            
            .cookie-category h6 {
                margin: 0 0 5px 0;
            }
            
            .cookie-modal-footer {
                padding: 20px;
                border-top: 1px solid #ddd;
                display: flex;
                gap: 10px;
                justify-content: flex-end;
            }
            
            @media (max-width: 768px) {
                .cookie-banner-content {
                    flex-direction: column;
                    text-align: center;
                }
                .cookie-actions {
                    width: 100%;
                    flex-direction: column;
                }
                .cookie-actions button {
                    width: 100%;
                }
            }
        `;
    }
}

// User Interaction Tracker
class UserTracker {
    constructor() {
        this.enabled = false;
        this.sessionId = this.getSessionId();
        this.interactions = [];
        this.currentArticle = null;
        this.readStartTime = null;
        this.maxScrollDepth = 0;
    }
    
    enable() {
        this.enabled = true;
        this.init();
    }
    
    init() {
        if (!this.enabled) return;
        
        // Track page view
        this.trackPageView();
        
        // Track article reading
        if (this.isArticlePage()) {
            this.trackArticleRead();
        }
        
        // Track clicks
        document.addEventListener('click', (e) => this.trackClick(e));
        
        // Track search
        const searchForm = document.querySelector('form[action*="search"]');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => this.trackSearch(e));
        }
        
        // Track scroll depth
        window.addEventListener('scroll', () => this.trackScrollDepth());
        
        // Track time on page
        window.addEventListener('beforeunload', () => this.sendInteractions());
        
        // Periodic send (every 30 seconds)
        setInterval(() => this.sendInteractions(), 30000);
    }
    
    getSessionId() {
        let sessionId = sessionStorage.getItem('tracker_session_id');
        if (!sessionId) {
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('tracker_session_id', sessionId);
        }
        return sessionId;
    }
    
    isArticlePage() {
        return window.location.pathname.includes('article.php');
    }
    
    trackPageView() {
        this.addInteraction({
            type: 'page_view',
            page_url: window.location.href,
            referrer: document.referrer
        });
    }
    
    trackArticleRead() {
        const articleId = new URLSearchParams(window.location.search).get('id');
        if (articleId) {
            this.currentArticle = articleId;
            this.readStartTime = Date.now();
            
            this.addInteraction({
                type: 'article_read',
                reference_type: 'article',
                reference_id: articleId,
                page_url: window.location.href
            });
        }
    }
    
    trackClick(e) {
        const link = e.target.closest('a');
        if (link) {
            const href = link.getAttribute('href');
            const text = link.textContent.trim().substring(0, 100);
            
            this.addInteraction({
                type: 'click',
                page_url: window.location.href,
                metadata: JSON.stringify({
                    link: href,
                    text: text,
                    element: link.className
                })
            });
        }
    }
    
    trackSearch(e) {
        const form = e.target;
        const query = form.querySelector('input[name="q"]')?.value;
        
        if (query) {
            this.addInteraction({
                type: 'search',
                page_url: window.location.href,
                metadata: JSON.stringify({ query: query })
            });
        }
    }
    
    trackScrollDepth() {
        const scrolled = window.scrollY;
        const height = document.documentElement.scrollHeight - window.innerHeight;
        const depth = Math.round((scrolled / height) * 100);
        
        if (depth > this.maxScrollDepth) {
            this.maxScrollDepth = depth;
        }
    }
    
    addInteraction(data) {
        const interaction = {
            ...data,
            session_id: this.sessionId,
            device_type: this.getDeviceType(),
            browser: this.getBrowser(),
            os: this.getOS(),
            timestamp: new Date().toISOString()
        };
        
        // Add read duration and scroll depth for article reads
        if (this.currentArticle && this.readStartTime) {
            interaction.read_duration = Math.round((Date.now() - this.readStartTime) / 1000);
            interaction.scroll_depth = this.maxScrollDepth;
        }
        
        this.interactions.push(interaction);
    }
    
    sendInteractions() {
        if (this.interactions.length === 0) return;
        
        const data = [...this.interactions];
        this.interactions = [];
        
        // Send to server
        fetch('/api/tracking/save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ interactions: data }),
            keepalive: true
        });
    }
    
    getDeviceType() {
        const ua = navigator.userAgent;
        if (/mobile/i.test(ua)) return 'mobile';
        if (/tablet|ipad/i.test(ua)) return 'tablet';
        return 'desktop';
    }
    
    getBrowser() {
        const ua = navigator.userAgent;
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Chrome')) return 'Chrome';
        if (ua.includes('Safari')) return 'Safari';
        if (ua.includes('Edge')) return 'Edge';
        return 'Other';
    }
    
    getOS() {
        const ua = navigator.userAgent;
        if (ua.includes('Windows')) return 'Windows';
        if (ua.includes('Mac')) return 'macOS';
        if (ua.includes('Linux')) return 'Linux';
        if (ua.includes('Android')) return 'Android';
        if (ua.includes('iOS')) return 'iOS';
        return 'Other';
    }
}

// Initialize on page load
window.cookieManager = new CookieManager();
window.userTracker = new UserTracker();

// Auto-enable tracking if analytics consent given
if (window.cookieManager.preferences?.analytics) {
    window.userTracker.enable();
}