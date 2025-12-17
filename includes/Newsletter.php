<?php
/**
 * Newsletter Management Helper Functions
 */

/**
 * Subscribe user to newsletter
 */
function subscribeToNewsletter($email, $name = null, $preferences = []) {
    $db = Database::getInstance();
    
    // Check if already subscribed
    $existing = $db->fetchOne("SELECT * FROM newsletter_subscribers WHERE email = ?", [$email]);
    
    if ($existing) {
        if ($existing['status'] === 'unsubscribed') {
            // Resubscribe
            $db->execute("
                UPDATE newsletter_subscribers 
                SET status = 'active', 
                    name = ?, 
                    preferences = ?, 
                    subscribed_at = NOW(),
                    updated_at = NOW()
                WHERE email = ?
            ", [$name, json_encode($preferences), $email]);
            
            return ['success' => true, 'message' => 'Successfully resubscribed to newsletter'];
        } else {
            return ['success' => false, 'message' => 'Email already subscribed'];
        }
    }
    
    // Generate verification token
    $token = bin2hex(random_bytes(32));
    
    // Insert new subscriber
    $db->execute("
        INSERT INTO newsletter_subscribers 
        (email, name, verification_token, preferences, status, subscribed_at, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
    ", [$email, $name, $token, json_encode($preferences)]);
    
    // Send verification email
    sendVerificationEmail($email, $name, $token);
    
    return ['success' => true, 'message' => 'Please check your email to verify subscription'];
}

/**
 * Unsubscribe from newsletter
 */
function unsubscribeFromNewsletter($email, $token = null) {
    $db = Database::getInstance();
    
    $conditions = ["email = ?"];
    $params = [$email];
    
    if ($token) {
        $conditions[] = "unsubscribe_token = ?";
        $params[] = $token;
    }
    
    $subscriber = $db->fetchOne(
        "SELECT * FROM newsletter_subscribers WHERE " . implode(' AND ', $conditions), 
        $params
    );
    
    if (!$subscriber) {
        return ['success' => false, 'message' => 'Subscriber not found'];
    }
    
    $db->execute("
        UPDATE newsletter_subscribers 
        SET status = 'unsubscribed', updated_at = NOW() 
        WHERE id = ?
    ", [$subscriber['id']]);
    
    return ['success' => true, 'message' => 'Successfully unsubscribed from newsletter'];
}

/**
 * Verify email subscription
 */
function verifySubscription($token) {
    $db = Database::getInstance();
    
    $subscriber = $db->fetchOne(
        "SELECT * FROM newsletter_subscribers WHERE verification_token = ?", 
        [$token]
    );
    
    if (!$subscriber) {
        return ['success' => false, 'message' => 'Invalid verification token'];
    }
    
    if ($subscriber['status'] === 'active') {
        return ['success' => true, 'message' => 'Email already verified'];
    }
    
    // Generate unsubscribe token
    $unsubscribe_token = bin2hex(random_bytes(32));
    
    $db->execute("
        UPDATE newsletter_subscribers 
        SET status = 'active', 
            email_verified = 1,
            unsubscribe_token = ?,
            updated_at = NOW() 
        WHERE id = ?
    ", [$unsubscribe_token, $subscriber['id']]);
    
    return ['success' => true, 'message' => 'Email verified successfully'];
}

/**
 * Send newsletter to subscribers
 */
function sendNewsletter($newsletter_id, $test_email = null) {
    $db = Database::getInstance();
    require_once __DIR__ . '/EmailService.php';
    $emailService = new EmailService();
    
    // Get newsletter details
    $newsletter = $db->fetchOne("SELECT * FROM newsletters WHERE id = ?", [$newsletter_id]);
    
    if (!$newsletter) {
        return ['success' => false, 'message' => 'Newsletter not found'];
    }
    
    // Get subscribers
    if ($test_email) {
        $subscribers = [['email' => $test_email, 'name' => 'Test User', 'id' => 0]];
    } else {
        $subscribers = $db->fetchAll("
            SELECT * FROM newsletter_subscribers 
            WHERE status = 'active' AND email_verified = 1
        ");
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($subscribers as $subscriber) {
        $personalized_content = personalizeContent($newsletter['content'], $subscriber);
        
        // Use EmailService with 'newsletter' purpose
        $result = $emailService->sendEmail(
            'newsletter',
            $subscriber['email'],
            $newsletter['subject'],
            $personalized_content,
            $subscriber['name'] ?? 'Subscriber'
        );
        
        if ($result) {
            $sent_count++;
            
            // Track send
            if (!$test_email) {
                $db->execute("
                    INSERT INTO newsletter_sends 
                    (newsletter_id, subscriber_id, sent_at) 
                    VALUES (?, ?, NOW())
                ", [$newsletter_id, $subscriber['id']]);
            }
        } else {
            $failed_count++;
        }
    }
    
    // Update newsletter stats
    if (!$test_email) {
        $db->execute("
            UPDATE newsletters 
            SET sent_count = sent_count + ?, 
                status = 'sent',
                sent_at = NOW() 
            WHERE id = ?
        ", [$sent_count, $newsletter_id]);
    }
    
    return [
        'success' => true, 
        'message' => "Newsletter sent to $sent_count recipients" . ($failed_count ? ", $failed_count failed" : '')
    ];
}

/**
 * Create newsletter from article
 */
function createNewsletterFromArticle($article_id) {
    $db = Database::getInstance();
    
    $article = $db->fetchOne("
        SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id = ?
    ", [$article_id]);
    
    if (!$article) {
        return ['success' => false, 'message' => 'Article not found'];
    }
    
    // Generate newsletter content from article
    $content = generateNewsletterTemplate([
        'title' => $article['title'],
        'description' => $article['description'],
        'thumbnail' => $article['thumbnail'],
        'category' => $article['category_name'],
        'url' => BASE_URL . '/article/' . $article['slug'],
        'date' => date('F j, Y', strtotime($article['published_at']))
    ]);
    
    // Create newsletter
    $newsletter_id = $db->insert("
        INSERT INTO newsletters 
        (subject, content, article_id, status, created_at) 
        VALUES (?, ?, ?, 'draft', NOW())
    ", [
        $article['title'],
        $content,
        $article_id
    ]);
    
    return [
        'success' => true, 
        'newsletter_id' => $newsletter_id,
        'message' => 'Newsletter created from article'
    ];
}

/**
 * Generate newsletter HTML template
 */
function generateNewsletterTemplate($data) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: white; padding: 30px; }
            .article-image { width: 100%; max-width: 600px; height: auto; }
            .article-title { font-size: 24px; font-weight: bold; margin: 20px 0; }
            .article-meta { color: #666; font-size: 14px; margin-bottom: 20px; }
            .article-description { font-size: 16px; line-height: 1.8; }
            .read-more { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?= SITE_NAME ?></h1>
                <p>Your Daily News Update</p>
            </div>
            <div class="content">
                <?php if (!empty($data['thumbnail'])): ?>
                <img src="<?= UPLOADS_URL . '/articles/' . $data['thumbnail'] ?>" alt="<?= htmlspecialchars($data['title']) ?>" class="article-image">
                <?php endif; ?>
                
                <h2 class="article-title"><?= htmlspecialchars($data['title']) ?></h2>
                
                <div class="article-meta">
                    <?php if (!empty($data['category'])): ?>
                    <span><?= htmlspecialchars($data['category']) ?></span> • 
                    <?php endif; ?>
                    <span><?= $data['date'] ?></span>
                </div>
                
                <div class="article-description">
                    <?= nl2br(htmlspecialchars($data['description'])) ?>
                </div>
                
                <a href="<?= $data['url'] ?>" class="read-more">Read Full Article</a>
            </div>
            <div class="footer">
                <p>You received this email because you subscribed to <?= SITE_NAME ?> newsletter.</p>
                <p><a href="{{unsubscribe_link}}">Unsubscribe</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

/**
 * Personalize newsletter content
 */
function personalizeContent($content, $subscriber) {
    $replacements = [
        '{{name}}' => $subscriber['name'] ?? 'Subscriber',
        '{{email}}' => $subscriber['email'],
        '{{unsubscribe_link}}' => BASE_URL . '/newsletter_unsubscribe.php?token=' . $subscriber['unsubscribe_token']
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $content);
}

/**
 * Send verification email
 */
function sendVerificationEmail($email, $name, $token) {
    $verify_url = BASE_URL . '/newsletter_verify.php?token=' . $token;
    
    $subject = 'Verify your newsletter subscription - ' . SITE_NAME;
    $message = "Hi " . ($name ?? '') . ",\n\n";
    $message .= "Thank you for subscribing to " . SITE_NAME . " newsletter!\n\n";
    $message .= "Please click the link below to verify your email address:\n";
    $message .= $verify_url . "\n\n";
    $message .= "If you didn't subscribe, you can safely ignore this email.\n\n";
    $message .= "Best regards,\n" . SITE_NAME;
    
    return sendEmail($email, $subject, $message);
}

/**
 * Simple email sender (can be replaced with PHPMailer, SendGrid, etc.)
 */
function sendEmail($to, $subject, $message, $from_name = null, $from_email = null) {
    $from_name = $from_name ?? SITE_NAME;
    $from_email = $from_email ?? SITE_EMAIL;
    
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
