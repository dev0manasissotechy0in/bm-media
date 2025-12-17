<!DOCTYPE html>
<html>
<head>
    <title>OTP & SMTP Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .status-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; font-weight: bold; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .test-btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #2563eb; }
        h2 { color: #1e293b; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
        .config-link { display: inline-block; margin: 10px 0; padding: 10px 15px; background: #3b82f6; color: white; text-decoration: none; border-radius: 4px; }
        .config-link:hover { background: #2563eb; }
    </style>
</head>
<body>
    <h1>🔐 OTP & SMTP Connection Diagnostic</h1>
    
    <?php
    require_once 'config/config.php';
    require_once 'includes/Database.php';
    require_once 'includes/Settings.php';
    require_once 'includes/EmailHelper.php';
    
    $db = Database::getInstance();
    
    // Test email from query parameter
    $testEmail = $_GET['test_email'] ?? '';
    ?>
    
    <!-- Configuration Links -->
    <div class="status-card">
        <h2>⚙️ Configuration Links</h2>
        <a href="/admin/smtp-multi-config.php" class="config-link" target="_blank">
            📧 Configure Auth SMTP (smtp-multi-config.php)
        </a>
        <a href="/admin/settings.php" class="config-link" target="_blank">
            🔒 Enable OTP (settings.php → Security Tab)
        </a>
    </div>
    
    <!-- SMTP Status -->
    <div class="status-card">
        <h2>📧 Auth SMTP Configuration Status</h2>
        <?php
        $smtp_enabled = Settings::get('auth_smtp_enabled', '0');
        $smtp_host = Settings::get('auth_smtp_host', '');
        $smtp_port = Settings::get('auth_smtp_port', '');
        $smtp_username = Settings::get('auth_smtp_username', '');
        $smtp_password = Settings::get('auth_smtp_password', '');
        $smtp_encryption = Settings::get('auth_smtp_encryption', '');
        $smtp_from_email = Settings::get('auth_smtp_from_email', '');
        $smtp_from_name = Settings::get('auth_smtp_from_name', '');
        
        $smtp_configured = !empty($smtp_host) && !empty($smtp_username) && !empty($smtp_password);
        ?>
        
        <p><strong>SMTP Enabled:</strong> 
            <?php if ($smtp_enabled === '1'): ?>
                <span class="success">✅ YES</span>
            <?php else: ?>
                <span class="error">❌ NO</span> - Enable in <a href="/admin/smtp-multi-config.php">smtp-multi-config.php</a>
            <?php endif; ?>
        </p>
        
        <p><strong>SMTP Host:</strong> 
            <?= !empty($smtp_host) ? "<span class='success'>✅ " . htmlspecialchars($smtp_host) . "</span>" : "<span class='error'>❌ Not Set</span>" ?>
        </p>
        
        <p><strong>SMTP Port:</strong> 
            <?= !empty($smtp_port) ? "<span class='success'>✅ " . htmlspecialchars($smtp_port) . "</span>" : "<span class='error'>❌ Not Set</span>" ?>
        </p>
        
        <p><strong>SMTP Username:</strong> 
            <?= !empty($smtp_username) ? "<span class='success'>✅ " . htmlspecialchars($smtp_username) . "</span>" : "<span class='error'>❌ Not Set</span>" ?>
        </p>
        
        <p><strong>SMTP Password:</strong> 
            <?= !empty($smtp_password) ? "<span class='success'>✅ Set (hidden)</span>" : "<span class='error'>❌ Not Set</span>" ?>
        </p>
        
        <p><strong>SMTP Encryption:</strong> 
            <?= !empty($smtp_encryption) ? "<span class='success'>✅ " . strtoupper(htmlspecialchars($smtp_encryption)) . "</span>" : "<span class='warning'>⚠️ Not Set (will use default)</span>" ?>
        </p>
        
        <p><strong>From Email:</strong> 
            <?= !empty($smtp_from_email) ? "<span class='success'>✅ " . htmlspecialchars($smtp_from_email) . "</span>" : "<span class='error'>❌ Not Set</span>" ?>
        </p>
        
        <p><strong>From Name:</strong> 
            <?= !empty($smtp_from_name) ? "<span class='info'>" . htmlspecialchars($smtp_from_name) . "</span>" : "<span class='warning'>⚠️ Not Set</span>" ?>
        </p>
        
        <hr>
        <p><strong>Overall SMTP Status:</strong> 
            <?php if ($smtp_configured && $smtp_enabled === '1'): ?>
                <span class="success">✅ CONFIGURED & ENABLED</span>
            <?php elseif ($smtp_configured): ?>
                <span class="warning">⚠️ CONFIGURED BUT NOT ENABLED</span>
            <?php else: ?>
                <span class="error">❌ NOT CONFIGURED</span>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- OTP System Status -->
    <div class="status-card">
        <h2>🔐 OTP System Status</h2>
        <?php
        $otp_enabled = Settings::get('otp_enabled', '0');
        $otp_expiry = Settings::get('otp_expiry_minutes', '10');
        
        $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'otp_codes'");
        $table_exists = !empty($tableCheck);
        
        if ($table_exists) {
            $activeCount = $db->fetchOne("SELECT COUNT(*) as count FROM otp_codes WHERE expires_at > NOW() AND is_used = 0");
            $active_otp_count = $activeCount['count'] ?? 0;
        }
        ?>
        
        <p><strong>OTP Enabled:</strong> 
            <?php if ($otp_enabled === '1'): ?>
                <span class="success">✅ YES</span>
            <?php else: ?>
                <span class="error">❌ NO</span> - Enable in <a href="/admin/settings.php">settings.php → Security Tab</a>
            <?php endif; ?>
        </p>
        
        <p><strong>OTP Expiry:</strong> <span class="info"><?= htmlspecialchars($otp_expiry) ?> minutes</span></p>
        
        <p><strong>OTP Table Exists:</strong> 
            <?php if ($table_exists): ?>
                <span class="success">✅ YES</span>
            <?php else: ?>
                <span class="error">❌ NO</span> - Run database/migration_advanced_features.sql
            <?php endif; ?>
        </p>
        
        <?php if ($table_exists): ?>
        <p><strong>Active OTPs:</strong> <span class="info"><?= $active_otp_count ?></span></p>
        <?php endif; ?>
    </div>
    
    <!-- Test OTP Generation -->
    <div class="status-card">
        <h2>🧪 Generate Test OTP (Database Only)</h2>
        <form method="get" style="margin: 20px 0;">
            <label>Enter Email to Test:</label><br>
            <input type="email" name="test_email" value="<?= htmlspecialchars($testEmail) ?>" 
                   placeholder="your@email.com" style="padding: 8px; width: 300px; margin: 10px 0;">
            <button type="submit" class="test-btn">🔑 Generate OTP</button>
        </form>
        
        <?php if (!empty($testEmail)): ?>
            <?php
            try {
                // Generate test OTP
                $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Save to database
                $db->delete('otp_codes', 'email = ? AND purpose = ?', [$testEmail, 'login']);
                $inserted = $db->insert('otp_codes', [
                    'email' => $testEmail,
                    'otp_code' => $otp,
                    'purpose' => 'login',
                    'user_type' => 'user',
                    'expires_at' => $expires_at
                ]);
                
                if ($inserted) {
                    echo "<div style='background: #dcfce7; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
                    echo "<p class='success' style='font-size: 18px; margin: 0 0 10px 0;'>✅ OTP Generated Successfully!</p>";
                    echo "<p style='margin: 10px 0;'><strong>Email:</strong> " . htmlspecialchars($testEmail) . "</p>";
                    echo "<p style='margin: 10px 0;'><strong>OTP:</strong> <code style='background: #fef3c7; padding: 8px 15px; font-size: 24px; font-weight: bold; border-radius: 4px;'>$otp</code></p>";
                    echo "<p style='margin: 10px 0;'><strong>Expires:</strong> $expires_at</p>";
                    echo "<p style='margin: 10px 0; color: #059669;'><strong>✅ Use this OTP to login in your app or website!</strong></p>";
                    echo "</div>";
                    
                    if (!$smtp_configured || $smtp_enabled !== '1') {
                        echo "<div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
                        echo "<p class='warning'>⚠️ SMTP not configured - Email NOT sent</p>";
                        echo "<p><small>Configure SMTP in <a href='/admin/smtp-multi-config.php'>smtp-multi-config.php</a> to enable email sending</small></p>";
                        echo "</div>";
                    } else {
                        echo "<div style='background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
                        echo "<p class='info'>ℹ️ SMTP is configured. To actually send email, use: <a href='/api/auth/test-otp-send.php?email=" . urlencode($testEmail) . "' target='_blank'>test-otp-send.php</a></p>";
                        echo "</div>";
                    }
                } else {
                    echo "<p class='error'>❌ Failed to save OTP to database</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        <?php endif; ?>
    </div>
    
    <!-- Quick Links -->
    <div class="status-card">
        <h2>🔗 Quick Links</h2>
        <ul>
            <li><a href="/api/auth/check-otp-status.php" target="_blank">View Full JSON Diagnostic</a></li>
            <li><a href="/api/auth/get-otp-debug.php?email=<?= urlencode($testEmail ?: 'your@email.com') ?>" target="_blank">Get Latest OTP from Database (Debug)</a></li>
            <li><a href="/register.php" target="_blank">Test Website Registration</a></li>
            <li><a href="/login-otp.php" target="_blank">Test Website OTP Login</a></li>
        </ul>
    </div>
    
    <!-- Hostinger SMTP Settings -->
    <div class="status-card">
        <h2>📮 Hostinger SMTP Settings Reference</h2>
        <pre>SMTP Host: smtp.hostinger.com
SMTP Port: 587 (TLS) or 465 (SSL)
SMTP Username: your-email@yourdomain.com
SMTP Password: Your email password
SMTP Encryption: TLS (recommended) or SSL
From Email: your-email@yourdomain.com
From Name: Your Website Name</pre>
        
        <p><strong>Configure at:</strong> <a href="/admin/smtp-multi-config.php" target="_blank">smtp-multi-config.php → Auth SMTP Tab</a></p>
    </div>
    
</body>
</html>
