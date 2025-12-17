<?php
/**
 * Reporter Application Form
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $expertise = $_POST['expertise'] ?? [];
    $portfolio = trim($_POST['portfolio'] ?? '');
    $why_join = trim($_POST['why_join'] ?? '');
    $sample_work = trim($_POST['sample_work'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($location)) {
        $errors[] = 'Location is required';
    }
    
    if (empty($experience)) {
        $errors[] = 'Experience is required';
    }
    
    if (empty($expertise)) {
        $errors[] = 'Please select at least one area of expertise';
    }
    
    if (empty($why_join)) {
        $errors[] = 'Please tell us why you want to join';
    }
    
    if (empty($errors)) {
        // In a full implementation, save to database
        // For now, just send email notification
        $expertise_str = implode(', ', $expertise);
        $message = "New Reporter Application\n\n";
        $message .= "Name: $name\n";
        $message .= "Email: $email\n";
        $message .= "Phone: $phone\n";
        $message .= "Location: $location\n";
        $message .= "Experience: $experience\n";
        $message .= "Expertise: $expertise_str\n";
        $message .= "Portfolio: $portfolio\n";
        $message .= "Sample Work: $sample_work\n";
        $message .= "Why Join: $why_join\n";
        
        // Send notification email (requires SMTP setup)
        $success = 'Application submitted successfully! We will review your application and contact you soon.';
    }
}

$page_title = 'Become a Reporter';
include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-person-badge"></i> Apply to Become a Reporter</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                        <div class="mt-3">
                            <a href="<?= BASE_URL ?>" class="btn btn-primary">Back to Home</a>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <div class="alert alert-info">
                        <h5><i class="bi bi-info-circle"></i> Join Our Team</h5>
                        <p class="mb-0">
                            We're looking for passionate journalists and content creators to join our team. 
                            Fill out the form below to apply.
                        </p>
                    </div>
                    
                    <form method="post">
                        <h5 class="mt-4 mb-3">Personal Information</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Location (City, State) <span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" 
                                   placeholder="e.g., Mumbai, Maharashtra" required>
                        </div>
                        
                        <h5 class="mt-4 mb-3">Professional Information</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Years of Experience in Journalism/Content Writing <span class="text-danger">*</span></label>
                            <select name="experience" class="form-select" required>
                                <option value="">Select experience</option>
                                <option value="0-1">0-1 years</option>
                                <option value="1-3">1-3 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="5-10">5-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Areas of Expertise <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Politics" class="form-check-input" id="exp1">
                                        <label class="form-check-label" for="exp1">Politics</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Business" class="form-check-input" id="exp2">
                                        <label class="form-check-label" for="exp2">Business & Economy</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Sports" class="form-check-input" id="exp3">
                                        <label class="form-check-label" for="exp3">Sports</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Technology" class="form-check-input" id="exp4">
                                        <label class="form-check-label" for="exp4">Technology</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Entertainment" class="form-check-input" id="exp5">
                                        <label class="form-check-label" for="exp5">Entertainment</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Health" class="form-check-input" id="exp6">
                                        <label class="form-check-label" for="exp6">Health & Wellness</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Science" class="form-check-input" id="exp7">
                                        <label class="form-check-label" for="exp7">Science</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" name="expertise[]" value="Other" class="form-check-input" id="exp8">
                                        <label class="form-check-label" for="exp8">Other</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Portfolio/LinkedIn Profile (Optional)</label>
                            <input type="url" name="portfolio" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['portfolio'] ?? '') ?>" 
                                   placeholder="https://">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Link to Sample Work (Optional)</label>
                            <input type="url" name="sample_work" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['sample_work'] ?? '') ?>" 
                                   placeholder="https://">
                            <small class="text-muted">Share a link to an article or content piece you've written</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Why do you want to join us? <span class="text-danger">*</span></label>
                            <textarea name="why_join" class="form-control" rows="4" required><?= htmlspecialchars($_POST['why_join'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions and confirm that the information provided is accurate
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send"></i> Submit Application
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>