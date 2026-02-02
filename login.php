<?php
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

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role']; // 'admin' or 'instructor'

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // FIXED: Single query that works for both roles
        $query = "SELECT adminID as id, full_name, email, password, position 
                 FROM ADMINISTRATOR 
                 WHERE email = :email AND status = 'active'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // FIXED: Check password with multiple methods
            $password_correct = false;
            
            // Method 1: password_verify (for hashed passwords)
            if (password_verify($password, $user['password'])) {
                $password_correct = true;
            }
            // Method 2: Direct comparison (if password isn't hashed yet)
            elseif ($password === $user['password']) {
                $password_correct = true;
            }
            
            if ($password_correct) {
                // FIXED: Determine role based on position
                $user_role = 'instructor'; // Default to instructor
                
                // If position contains "admin" or is Administrator, set as admin
                $position_lower = strtolower($user['position']);
                if (strpos($position_lower, 'admin') !== false || 
                    strpos($position_lower, 'administrator') !== false) {
                    $user_role = 'admin';
                }
                
                // Check if selected role matches user's actual role
                if ($role !== $user_role) {
                    $error = 'Please select the correct role for this account';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['role'] = $user_role;

                    // Redirect based on role
                    if ($user_role === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: instructor_dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Account not found or inactive';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Keep your existing CSS styles */
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

        .login-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: var(--card-shadow);
            max-width: 480px;
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

        .login-header {
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

        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-btn {
            padding: 1rem;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'DM Sans', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
        }

        .role-btn:hover {
            border-color: var(--primary);
            background: #f5f5f5;
        }

        .role-btn.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(26, 26, 26, 0.2);
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
        
        .test-credentials {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        
        .test-credentials h4 {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .credential {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo">S</div>
            <h1>Syllabus Repository</h1>
            <p class="subtitle">Secure Access Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="role-selector">
                <input type="radio" name="role" value="admin" id="role-admin" checked hidden>
                <label for="role-admin" class="role-btn active" onclick="selectRole('admin', this)">Administrator</label>
                
                <input type="radio" name="role" value="instructor" id="role-instructor" hidden>
                <label for="role-instructor" class="role-btn" onclick="selectRole('instructor', this)">Instructor</label>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required >
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="register-link" id="registerLink" style="display: none;">
            Don't have an account? <a href="register_instructor.php">Register as Instructor</a>
        </div>
        
       

    <script>
        function selectRole(role, element) {
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('role-' + role).checked = true;
            
            // Show/hide registration link based on role
            const registerLink = document.getElementById('registerLink');
            if (role === 'instructor') {
                registerLink.style.display = 'block';
            } else {
                registerLink.style.display = 'none';
            }
        }
    </script>
</body>
</html>