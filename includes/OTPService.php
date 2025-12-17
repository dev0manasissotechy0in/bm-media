<?php
/**
 * OTP Authentication Service
 * Handles OTP generation, validation, and management
 */

class OTPService {
    private $db;
    private $emailService;
    private $otp_length = 6;
    private $otp_expiry = 600; // 10 minutes
    
    public function __construct() {
        $this->db = Database::getInstance();
        require_once __DIR__ . '/EmailService.php';
        $this->emailService = new EmailService();
    }
    
    /**
     * Generate alphanumeric OTP (characters + numbers)
     */
    public function generateOTP() {
        $characters = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Removed I, O to avoid confusion
        $otp = '';
        
        for ($i = 0; $i < $this->otp_length; $i++) {
            $otp .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $otp;
    }
    
    /**
     * Send OTP to email
     * 
     * @param string $email User email
     * @param string $purpose 'login', 'registration', or 'password_reset'
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendOTP($email, $purpose = 'login') {
        // Check if email exists (for login)
        if ($purpose === 'login') {
            $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
            if (!$user) {
                return ['success' => false, 'message' => 'Email not found'];
            }
            
            // Check if user has OTP enabled
            if (!$user['otp_enabled']) {
                return ['success' => false, 'message' => 'OTP login not enabled for this account'];
            }
        }
        
        // Rate limiting: Check recent OTP requests
        $recent_otp = $this->db->fetchOne(
            "SELECT * FROM otp_codes 
             WHERE email = ? AND purpose = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
             ORDER BY created_at DESC LIMIT 1",
            [$email, $purpose]
        );
        
        if ($recent_otp) {
            return ['success' => false, 'message' => 'Please wait before requesting another OTP'];
        }
        
        // Invalidate old OTPs
        $this->db->query(
            "UPDATE otp_codes SET is_used = 1 WHERE email = ? AND purpose = ? AND is_used = 0",
            [$email, $purpose]
        );
        
        // Generate new OTP
        $otp = $this->generateOTP();
        $expires_at = date('Y-m-d H:i:s', time() + $this->otp_expiry);
        
        // Save OTP to database
        $this->db->insert('otp_codes', [
            'email' => $email,
            'otp_code' => $otp,
            'purpose' => $purpose,
            'expires_at' => $expires_at,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
        // Send OTP via email
        $sent = $this->emailService->sendOTP($email, $otp, $purpose);
        
        if ($sent) {
            return ['success' => true, 'message' => 'OTP sent to your email'];
        } else {
            return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
        }
    }
    
    /**
     * Verify OTP code
     * 
     * @param string $email User email
     * @param string $otp OTP code to verify
     * @param string $purpose OTP purpose
     * @return array ['success' => bool, 'message' => string, 'user' => array|null]
     */
    public function verifyOTP($email, $otp, $purpose = 'login') {
        // Clean OTP (remove spaces, convert to uppercase)
        $otp = strtoupper(str_replace(' ', '', $otp));
        
        // Find valid OTP
        $otp_record = $this->db->fetchOne(
            "SELECT * FROM otp_codes 
             WHERE email = ? AND otp_code = ? AND purpose = ? 
             AND is_used = 0 AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1",
            [$email, $otp, $purpose]
        );
        
        if (!$otp_record) {
            // Log failed attempt
            $this->logFailedAttempt($email, $otp, $purpose);
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Mark OTP as used
        $this->db->update('otp_codes', ['is_used' => 1], 'id = ?', [$otp_record['id']]);
        
        // Get user data
        $user = $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Update last login
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
        
        return ['success' => true, 'message' => 'OTP verified successfully', 'user' => $user];
    }
    
    /**
     * Log failed OTP attempt
     */
    private function logFailedAttempt($email, $otp, $purpose) {
        // Could implement rate limiting or account locking here
        error_log("Failed OTP attempt - Email: $email, OTP: $otp, Purpose: $purpose");
    }
    
    /**
     * Clean expired OTPs (run periodically)
     */
    public function cleanExpiredOTPs() {
        return $this->db->query("DELETE FROM otp_codes WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
    
    /**
     * Enable OTP for user
     */
    public function enableOTPForUser($user_id) {
        return $this->db->update('users', ['otp_enabled' => 1], 'id = ?', [$user_id]);
    }
    
    /**
     * Disable OTP for user
     */
    public function disableOTPForUser($user_id) {
        return $this->db->update('users', ['otp_enabled' => 0], 'id = ?', [$user_id]);
    }
    
    /**
     * Get OTP statistics
     */
    public function getOTPStats($email = null) {
        if ($email) {
            return $this->db->fetchAll(
                "SELECT purpose, COUNT(*) as count, 
                 SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as used_count
                 FROM otp_codes 
                 WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY purpose",
                [$email]
            );
        } else {
            return $this->db->fetchAll(
                "SELECT DATE(created_at) as date, purpose, COUNT(*) as count
                 FROM otp_codes 
                 WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at), purpose
                 ORDER BY date DESC"
            );
        }
    }
}
