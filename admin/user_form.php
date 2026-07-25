<?php
require_once("../middleware/admin.php");
require_once("../core.php");

// Get admin info
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['fullname'];

// Get user ID for editing
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $user_id > 0;
$pageTitle = $isEdit ? 'Edit User' : 'Add New User';

// Initialize variables
$fullname = '';
$email = '';
$role = 'user';
$status = 'active';
$password = '';
$confirm_password = '';
$message = '';
$messageType = '';

// If editing, fetch user data
if ($isEdit) {
    $stmt = $conn->prepare("SELECT id, fullname, email, role, status, xp, level FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        header("Location: users.php?error=User not found");
        exit();
    }
    
    $fullname = $user['fullname'];
    $email = $user['email'];
    $role = $user['role'];
    $status = $user['status'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (excluding current user if editing)
    $emailCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheck->bind_param("si", $email, $user_id);
    $emailCheck->execute();
    if ($emailCheck->get_result()->num_rows > 0) {
        $errors[] = "Email already exists. Please use a different email.";
    }
    
    // Password validation for new user or password change
    if (!$isEdit || !empty($password)) {
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    // If no errors, save user
    if (empty($errors)) {
        if ($isEdit) {
            // Update existing user
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $fullname, $email, $role, $status, $hashedPassword, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $fullname, $email, $role, $status, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = "User updated successfully!";
                $messageType = "success";
                
                // Redirect after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'users.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error updating user: " . $conn->error;
                $messageType = "error";
            }
        } else {
            // Create new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $hashedPassword, $role, $status);
            
            if ($stmt->execute()) {
                $message = "User created successfully!";
                $messageType = "success";
                
                // Clear form fields
                $fullname = '';
                $email = '';
                $role = 'user';
                $status = 'active';
                $password = '';
                $confirm_password = '';
                
                // Redirect after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'users.php';
                    }, 2000);
                </script>";
            } else {
                $message = "Error creating user: " . $conn->error;
                $messageType = "error";
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Panel</title>
    
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="../assets/admin.css">
    
    </head>
<body>
    <?php require_once("../includes/admin_sidebar.php"); ?>
    
    <div class="admin-content">
        <?php require_once("../includes/admin_navbar.php"); ?>
        
        <div style="margin-top: 20px;">
            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2 style="margin: 0; color: #1a1a2e;"><?php echo $pageTitle; ?></h2>
                <a href="users.php" class="btn-secondary" style="padding: 8px 20px; font-size: 14px;">
                    ← Back to Users
                </a>
            </div>
            
            <!-- Display Message -->
            <?php if ($message): ?>
            <div class="toast-message <?php echo $messageType; ?>">
                <span><?php echo $messageType == 'success' ? '✅' : ($messageType == 'info' ? 'ℹ️' : '❌'); ?></span>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Form -->
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name <span class="required">*</span></label>
                            <input type="text" id="fullname" name="fullname" 
                                   value="<?php echo htmlspecialchars($fullname); ?>" 
                                   placeholder="Enter full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="Enter email address" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role <span class="required">*</span></label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo $role == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="blocked" <?php echo $status == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">
                                <?php echo $isEdit ? 'New Password (leave blank to keep current)' : 'Password <span class="required">*</span>'; ?>
                            </label>
                            <input type="password" id="password" name="password" 
                                   placeholder="<?php echo $isEdit ? 'Enter new password' : 'Enter password'; ?>">
                            <div class="help-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <?php echo $isEdit ? 'Confirm New Password' : 'Confirm Password <span class="required">*</span>'; ?>
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="<?php echo $isEdit ? 'Confirm new password' : 'Confirm password'; ?>">
                        </div>
                    </div>
                    
                    <?php if ($isEdit): ?>
                    <div class="form-group" style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                            <div>
                                <strong>XP:</strong> <?php echo $user['xp'] ?? 0; ?>
                            </div>
                            <div>
                                <strong>Level:</strong> <?php echo $user['level'] ?? 1; ?>
                            </div>
                            <div>
                                <strong>User ID:</strong> #<?php echo $user_id; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <?php echo $isEdit ? '💾 Update User' : '➕ Create User'; ?>
                        </button>
                        <a href="users.php" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../javascript/script.js"></script>
   
    <script>
        // Password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            
            // Only validate if password field has value
            if (password.value || <?php echo $isEdit ? 'false' : 'true'; ?>) {
                if (password.value.length < 6 && password.value.length > 0) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long.');
                    password.focus();
                    return false;
                }
                
                if (password.value !== confirm.value) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    confirm.focus();
                    return false;
                }
            }
        });
        
        // Auto-hide toast messages after 5 seconds
        setTimeout(function() {
            const toast = document.querySelector('.toast-message');
            if (toast) {
                toast.style.display = 'none';
            }
        }, 5000);
    </script>
</body>
</html>