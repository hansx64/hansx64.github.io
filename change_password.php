<?php
// change_password.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif ($current_password === $new_password) {
        $error = 'New password must be different from current password';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Get current password from database
        $query = "SELECT password FROM ADMINISTRATOR WHERE adminID = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Verify current password
            $password_correct = false;
            
            // Try password_verify first (for hashed passwords)
            if (password_verify($current_password, $user['password'])) {
                $password_correct = true;
            }
            // Try direct comparison (for unhashed passwords)
            elseif ($current_password === $user['password']) {
                $password_correct = true;
            }

            if ($password_correct) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update password
                $update_query = "UPDATE ADMINISTRATOR SET password = :password WHERE adminID = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':user_id', $_SESSION['user_id']);

                if ($update_stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        } else {
            $error = 'User not found';
        }
    }
}

// Determine dashboard URL based on role
$dashboard_url = ($_SESSION['role'] === 'admin') ? 'admin_dashboard.php' : 'instructor_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .password-card {
            max-width: 600px;
            margin: 2rem auto;
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
        }

        .password-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .password-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #a92f2fff;
            margin-bottom: 0.5rem;
        }

        .password-header p {
            color: #761e1eff;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #251717ff;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #bb86fc;
            box-shadow: 0 0 0 3px rgba(187, 134, 252, 0.1);
        }

        .password-hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .button-group .btn {
            flex: 1;
        }

        .user-info-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .user-info-box .user-name {
            font-weight: 600;
            color: #fdfdfdff;
            font-size: 1.1rem;
        }

        .user-info-box .user-email {
            color: #000000ff;
            font-size: 2rem;
            margin-top: 0.25rem;
        }

        .user-info-box .user-role {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: rgba(187, 134, 252, 0.2);
            color: #ffffffff;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Change Password</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='<?php echo $dashboard_url; ?>'">
                    Back to Dashboard
                </button>
            </div>
        </div>

        <div class="password-card">
            <div class="password-header">
                <h2>🔒 Update Your Password</h2>
                <p>Keep your account secure with a strong password</p>
            </div>

            <div class="user-info-box">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <a href="<?php echo $dashboard_url; ?>" style="color: #155724; font-weight: 600; text-decoration: underline;">
                        Return to Dashboard
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required placeholder="Enter your current password">
                </div>

                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required placeholder="Enter your new password">
                    <div class="password-hint">Minimum 8 characters</div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your new password">
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-accent">Change Password</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo $dashboard_url; ?>'">
                        Cancel
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
