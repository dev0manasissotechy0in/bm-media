<?php
/**
 * Contact Page
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

$page_title = 'Contact Us';
$page_description = 'Get in touch with us. We\'d love to hear from you!';

$success = false;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters long';
    }
    
    // Rate limiting (optional)
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $recent_submission = $db->fetchOne(
        "SELECT id FROM contact_queries WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        [$ip_address]
    );
    
    if ($recent_submission) {
        $errors[] = 'Please wait 5 minutes before submitting another message';
    }
    
    if (empty($errors)) {
        // Save to database
        $query_data = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'ip_address' => $ip_address,
            'source' => 'website',
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('contact_queries', $query_data);
        
        // Send email notification using EmailService with 'contact' purpose
        require_once 'includes/EmailService.php';
        $emailService = new EmailService();
        
        $contactData = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'ip_address' => $ip_address,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $emailService->sendContactNotification($contactData);
        
        $success = true;
        
        // Clear form data
        $_POST = [];
    }
}

include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold">Contact Us</h1>
                <p class="lead text-muted">Have a question or feedback? We'd love to hear from you!</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Message Sent!</h5>
                <p class="mb-0">Thank you for contacting us. We'll get back to you as soon as possible.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h3 class="mb-4"><i class="bi bi-envelope"></i> Send us a Message</h3>
                            
                            <form method="post" class="needs-validation" novalidate>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Your Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                                               required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Your Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                               required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                                        <input type="text" name="subject" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" 
                                               required>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Message <span class="text-danger">*</span></label>
                                        <textarea name="message" class="form-control" rows="6" 
                                                  required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                                        <small class="text-muted">Minimum 10 characters</small>
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send"></i> Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-lg-4">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-info-circle"></i> Contact Information</h5>
                            
                            <?php
                            $contact_email_result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'contact_email'");
                            $contact_email = $contact_email_result['setting_value'] ?? '';
                            ?>
                            
                            <?php if ($contact_email): ?>
                            <p>
                                <strong><i class="bi bi-envelope"></i> Email:</strong><br>
                                <a href="mailto:<?= htmlspecialchars($contact_email) ?>">
                                    <?= htmlspecialchars($contact_email) ?>
                                </a>
                            </p>
                            <?php endif; ?>
                            
                            <p class="mb-0">
                                <strong><i class="bi bi-clock"></i> Response Time:</strong><br>
                                We typically respond within 24-48 hours
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-question-circle"></i> FAQ</h5>
                            <p class="small">Before contacting us, you might find answers to common questions in our FAQ section.</p>
                            <a href="<?= BASE_URL ?>" class="btn btn-outline-primary btn-sm w-100">
                                <i class="bi bi-house"></i> Visit FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * Send contact email via SMTP
 */
function sendContactEmail($name, $email, $subject, $message) {
    $db = Database::getInstance();
    
    // Get SMTP settings
    $settings = [];
    $setting_keys = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name', 'contact_email'];
    
    foreach ($setting_keys as $key) {
        $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        $settings[$key] = $result['setting_value'] ?? '';
    }
    
    // Use PHPMailer or basic mail() function
    // For simplicity, using mail() function here
    // In production, consider using PHPMailer for better SMTP support
    
    $to = $settings['contact_email'];
    $email_subject = "Contact Form: " . $subject;
    $email_body = "Name: $name\n";
    $email_body .= "Email: $email\n\n";
    $email_body .= "Message:\n$message\n";
    
    $headers = "From: " . $settings['smtp_from_name'] . " <" . $settings['smtp_from_email'] . ">\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // In production, implement proper SMTP sending using PHPMailer
    @mail($to, $email_subject, $email_body, $headers);
}
?>