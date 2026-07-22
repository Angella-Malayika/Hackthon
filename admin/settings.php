<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Initialize settings array with defaults
$settings = [
    'site_name' => 'Internet Governance & Awareness Platform',
    'site_tagline' => 'Empowering Digital Citizens',
    'site_logo' => '🌐',
    'primary_color' => '#03a60c',
    'secondary_color' => '#028c0a',
    'default_avatar' => 'default.png',
    'xp_per_lesson' => '50',
    'xp_per_quest' => '30',
    'certificate_template' => 'default',
    'maintenance_mode' => '0',
    'allow_registration' => '1',
    'facebook_url' => '',
    'twitter_url' => '',
    'linkedin_url' => '',
    'youtube_url' => '',
    'footer_text' => '© 2026 Internet Governance & Awareness Platform. All rights reserved.'
];

// Load settings from database if settings table exists
try {
    $result = $conn->query("SELECT * FROM settings");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Settings table might not exist yet
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    // Get all form values
    $site_name = trim($_POST['site_name']);
    $site_tagline = trim($_POST['site_tagline']);
    $site_logo = trim($_POST['site_logo']);
    $primary_color = trim($_POST['primary_color']);
    $secondary_color = trim($_POST['secondary_color']);
    $default_avatar = trim($_POST['default_avatar']);
    $xp_per_lesson = intval($_POST['xp_per_lesson']);
    $xp_per_quest = intval($_POST['xp_per_quest']);
    $certificate_template = trim($_POST['certificate_template']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $allow_registration = isset($_POST['allow_registration']) ? '1' : '0';
    $facebook_url = trim($_POST['facebook_url']);
    $twitter_url = trim($_POST['twitter_url']);
    $linkedin_url = trim($_POST['linkedin_url']);
    $youtube_url = trim($_POST['youtube_url']);
    $footer_text = trim($_POST['footer_text']);
    
    // Validation
    $errors = [];
    
    if (empty($site_name)) {
        $errors[] = "Site name is required";
    }
    
    if ($xp_per_lesson < 0) {
        $errors[] = "XP per lesson must be a positive number";
    }
    
    if ($xp_per_quest < 0) {
        $errors[] = "XP per quest must be a positive number";
    }
    
    // If no errors, save settings
    if (empty($errors)) {
        // Prepare data for saving
        $settingsData = [
            'site_name' => $site_name,
            'site_tagline' => $site_tagline,
            'site_logo' => $site_logo,
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'default_avatar' => $default_avatar,
            'xp_per_lesson' => $xp_per_lesson,
            'xp_per_quest' => $xp_per_quest,
            'certificate_template' => $certificate_template,
            'maintenance_mode' => $maintenance_mode,
            'allow_registration' => $allow_registration,
            'facebook_url' => $facebook_url,
            'twitter_url' => $twitter_url,
            'linkedin_url' => $linkedin_url,
            'youtube_url' => $youtube_url,
            'footer_text' => $footer_text
        ];
        
        // Check if settings table exists, create if not
        $tableCheck = $conn->query("SHOW TABLES LIKE 'settings'");
        if ($tableCheck->num_rows == 0) {
            // Create settings table
            $conn->query("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
        }
        
        // Save each setting
        $success = true;
        foreach ($settingsData as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                    ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $message = "Settings saved successfully!";
            $messageType = "success";
            
            // Update the settings array with new values
            $settings = array_merge($settings, $settingsData);
            
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'settings.php';
                }, 1500);
            </script>";
        } else {
            $message = "Error saving settings: " . $conn->error;
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Check if settings table exists and get all settings
try {
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (Exception $e) {
    // Settings table doesn't exist yet
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../assets/style.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
    
</head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <div class="settings-container" style="margin-top: 20px;">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; color: #1a1a2e;">⚙️ Platform Settings</h2>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <!-- General Settings -->
                <div class="settings-section">
                    <h3>🌐 General Settings</h3>
                    <p class="section-desc">Basic platform information and branding</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="site_name">Site Name <span class="required">*</span></label>
                            <input type="text" id="site_name" name="site_name" 
                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>" 
                                   placeholder="Enter site name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_tagline">Site Tagline</label>
                            <input type="text" id="site_tagline" name="site_tagline" 
                                   value="<?php echo htmlspecialchars($settings['site_tagline']); ?>" 
                                   placeholder="Enter site tagline">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_logo">Site Logo (Emoji)</label>
                        <div class="current-logo-display">
                            <div class="logo-icon" id="logoPreview"><?php echo htmlspecialchars($settings['site_logo']); ?></div>
                            <div class="logo-info">
                                <strong>Current Logo:</strong> <span id="logoText"><?php echo htmlspecialchars($settings['site_logo']); ?></span>
                            </div>
                        </div>
                        <input type="text" id="site_logo" name="site_logo" 
                               value="<?php echo htmlspecialchars($settings['site_logo']); ?>" 
                               placeholder="e.g., 🌐, 🛡️, 📚"
                               oninput="document.getElementById('logoPreview').textContent = this.value || '🌐'; document.getElementById('logoText').textContent = this.value || '🌐';">
                        <div class="help-text">Use an emoji as your site logo</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="footer_text">Footer Text</label>
                        <input type="text" id="footer_text" name="footer_text" 
                               value="<?php echo htmlspecialchars($settings['footer_text']); ?>" 
                               placeholder="Enter footer text">
                    </div>
                </div>
                
                <!-- Theme Settings -->
                <div class="settings-section">
                    <h3>🎨 Theme Settings</h3>
                    <p class="section-desc">Customize the platform colors</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="primary_color">Primary Color</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" id="primary_color" name="primary_color" 
                                       value="<?php echo htmlspecialchars($settings['primary_color']); ?>">
                                <input type="text" id="primary_color_text" 
                                       value="<?php echo htmlspecialchars($settings['primary_color']); ?>" 
                                       style="flex: 1;"
                                       oninput="document.getElementById('primary_color').value = this.value;">
                            </div>
                            <div class="help-text">Main brand color</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="secondary_color">Secondary Color</label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" id="secondary_color" name="secondary_color" 
                                       value="<?php echo htmlspecialchars($settings['secondary_color']); ?>">
                                <input type="text" id="secondary_color_text" 
                                       value="<?php echo htmlspecialchars($settings['secondary_color']); ?>" 
                                       style="flex: 1;"
                                       oninput="document.getElementById('secondary_color').value = this.value;">
                            </div>
                            <div class="help-text">Secondary brand color</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="default_avatar">Default Avatar</label>
                        <input type="text" id="default_avatar" name="default_avatar" 
                               value="<?php echo htmlspecialchars($settings['default_avatar']); ?>" 
                               placeholder="default.png">
                        <div class="help-text">Filename of the default avatar image (must exist in assets/images/)</div>
                    </div>
                </div>
                
                <!-- XP Settings -->
                <div class="settings-section">
                    <h3>⭐ XP Settings</h3>
                    <p class="section-desc">Configure experience points earned by users</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="xp_per_lesson">XP per Lesson</label>
                            <input type="number" id="xp_per_lesson" name="xp_per_lesson" 
                                   value="<?php echo htmlspecialchars($settings['xp_per_lesson']); ?>" 
                                   min="1" max="1000">
                            <div class="help-text">How much XP a user earns for completing a lesson</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="xp_per_quest">XP per Quest</label>
                            <input type="number" id="xp_per_quest" name="xp_per_quest" 
                                   value="<?php echo htmlspecialchars($settings['xp_per_quest']); ?>" 
                                   min="1" max="1000">
                            <div class="help-text">How much XP a user earns for completing a quest</div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media Settings -->
                <div class="settings-section">
                    <h3>📱 Social Media Links</h3>
                    <p class="section-desc">Add links to your social media profiles</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="facebook_url">Facebook</label>
                            <input type="url" id="facebook_url" name="facebook_url" 
                                   value="<?php echo htmlspecialchars($settings['facebook_url']); ?>" 
                                   placeholder="https://facebook.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label for="twitter_url">Twitter/X</label>
                            <input type="url" id="twitter_url" name="twitter_url" 
                                   value="<?php echo htmlspecialchars($settings['twitter_url']); ?>" 
                                   placeholder="https://twitter.com/yourhandle">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="linkedin_url">LinkedIn</label>
                            <input type="url" id="linkedin_url" name="linkedin_url" 
                                   value="<?php echo htmlspecialchars($settings['linkedin_url']); ?>" 
                                   placeholder="https://linkedin.com/company/yourcompany">
                        </div>
                        
                        <div class="form-group">
                            <label for="youtube_url">YouTube</label>
                            <input type="url" id="youtube_url" name="youtube_url" 
                                   value="<?php echo htmlspecialchars($settings['youtube_url']); ?>" 
                                   placeholder="https://youtube.com/c/yourchannel">
                        </div>
                    </div>
                </div>
                
                <!-- Certificate Settings -->
                <div class="settings-section">
                    <h3>🎓 Certificate Settings</h3>
                    <p class="section-desc">Configure certificate templates</p>
                    
                    <div class="form-group">
                        <label for="certificate_template">Certificate Template</label>
                        <select id="certificate_template" name="certificate_template">
                            <option value="default" <?php echo $settings['certificate_template'] == 'default' ? 'selected' : ''; ?>>Default</option>
                            <option value="modern" <?php echo $settings['certificate_template'] == 'modern' ? 'selected' : ''; ?>>Modern</option>
                            <option value="classic" <?php echo $settings['certificate_template'] == 'classic' ? 'selected' : ''; ?>>Classic</option>
                            <option value="premium" <?php echo $settings['certificate_template'] == 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="minimal" <?php echo $settings['certificate_template'] == 'minimal' ? 'selected' : ''; ?>>Minimal</option>
                        </select>
                        <div class="help-text">Choose the certificate design template</div>
                    </div>
                </div>
                
                <!-- Platform Settings -->
                <div class="settings-section">
                    <h3>🔧 Platform Settings</h3>
                    <p class="section-desc">Configure platform behavior and features</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="maintenance_mode" value="1" 
                                       <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                Enable Maintenance Mode
                            </label>
                            <div class="help-text">When enabled, only admins can access the platform</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_registration" value="1" 
                                       <?php echo $settings['allow_registration'] == '1' ? 'checked' : ''; ?>>
                                Allow User Registration
                            </label>
                            <div class="help-text">Allow new users to register on the platform</div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="settings-section" style="border: none; box-shadow: none; padding: 0;">
                    <div class="form-actions">
                        <button type="submit" name="save_settings" class="btn-primary">
                            💾 Save All Settings
                        </button>
                        <button type="reset" class="btn-secondary">↺ Reset Form</button>
                        <button type="button" class="btn-danger" onclick="resetSettings()">🔄 Reset to Defaults</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
   
</body>
</html>