<?php
// register_instructor.php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: instructor_dashboard.php");
    }
    exit();
}

require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($department)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Check if email already exists
        $check_query = "SELECT adminID FROM ADMINISTRATOR WHERE email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error = 'Email already registered. Please use a different email or login.';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default position if empty
            if (empty($position)) {
                $position = 'Instructor';
            }

            // Insert new instructor
            $insert_query = "INSERT INTO ADMINISTRATOR (full_name, email, password, position, department, status) 
                            VALUES (:full_name, :email, :password, :position, :department, 'active')";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':full_name', $full_name);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':password', $hashed_password);
            $insert_stmt->bindParam(':position', $position);
            $insert_stmt->bindParam(':department', $department);

            if ($insert_stmt->execute()) {
                $_SESSION['registration_success'] = 'Registration successful! Please login with your credentials.';
                header("Location: login.php");
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Registration - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1a1a1a;
            --primary-light: #2d2d2d;
            --accent: #404040;
            --accent-light: #595959;
            --bg-dark: #0a0a0a;
            --bg-light: #f5f5f5;
            --text-dark: #1a1a1a;
            --text-light: #f5f5f5;
            --border: #404040;
            --card-shadow: 0 8px 32px rgba(26, 26, 26, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .register-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: var(--card-shadow);
            max-width: 520px;
            width: 100%;
            border: 2px solid var(--border);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: white;
            box-shadow: 0 8px 24px rgba(26, 26, 26, 0.3);
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }

        .password-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(26, 26, 26, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 26, 26, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <div class="logo">S</div>
            <h1>Instructor Registration</h1>
            <p class="subtitle">Create your account to submit syllabi</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required placeholder="John Doe" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email" name="email" required placeholder="your.email@university.edu" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Position/Title</label>
                <input type="text" name="position" placeholder="e.g., Assistant Professor, Lecturer" 
                       value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>">
                <div class="password-hint">Optional - defaults to "Instructor"</div>
            </div>

            <div class="form-group">
                <label>Department *</label>
                <select name="department" required style="width: 100%; padding: 0.875rem 1rem; border: 2px solid #dee2e6; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 1rem; transition: all 0.3s ease;">
                    <option value="">Select Department</option>
                    <option value="BS Accountancy" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BS Accountancy') ? 'selected' : ''; ?>>BS Accountancy</option>
                    <option value="BS Business Administration" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BS Business Administration') ? 'selected' : ''; ?>>BS Business Administration</option>
                    <option value="BS Office Administration" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BS Office Administration') ? 'selected' : ''; ?>>BS Office Administration</option>
                    <option value="BS Information Technology" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BS Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required placeholder="Enter your password">
                <div class="password-hint">Minimum 8 characters</div>
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" required placeholder="Confirm your password">
            </div>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>
