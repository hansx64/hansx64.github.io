<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow only from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("Access denied!");
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Reset Instructor Login</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email) || empty($password)) {
        echo "❌ Please enter both email and password";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if user exists
        $check = $db->prepare("SELECT adminID FROM ADMINISTRATOR WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            // Update existing user
            $stmt = $db->prepare("UPDATE ADMINISTRATOR SET password = ?, status = 'active' WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            echo "✅ Password updated for: " . $email . "<br>";
            echo "New password: " . $password . "<br>";
            echo "Hash: " . $hashed_password . "<br>";
        } else {
            // Create new user
            $full_name = "Instructor User";
            $position = "Professor";
            
            $stmt = $db->prepare("INSERT INTO ADMINISTRATOR (full_name, email, password, position) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed_password, $position]);
            echo "✅ Created new instructor user: " . $email . "<br>";
            echo "Password: " . $password . "<br>";
        }
        
        echo "<br><a href='login.php'>Go to Login</a>";
    }
}
?>

<form method="POST">
    <div style="margin-bottom: 1rem;">
        <label>Instructor Email:</label><br>
        <input type="email" name="email" value="instructor@university.edu" required style="padding: 8px; width: 300px;">
    </div>
    
    <div style="margin-bottom: 1rem;">
        <label>New Password:</label><br>
        <input type="text" name="password" value="instructor123" required style="padding: 8px; width: 300px;">
    </div>
    
    <button type="submit" style="padding: 10px 20px; background: #1a1a1a; color: white; border: none; cursor: pointer;">
        Reset Instructor Password
    </button>
</form>

<hr>
<h3>Or use this quick reset:</h3>
<a href="reset_instructor.php?quick=1" style="padding: 10px; background: #2d2d2d; color: white; text-decoration: none;">Quick Reset Instructor to: instructor@university.edu / instructor123</a>

<?php
// Quick reset via GET
if (isset($_GET['quick'])) {
    $email = 'instructor@university.edu';
    $password = 'instructor123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $check = $db->prepare("SELECT adminID FROM ADMINISTRATOR WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() > 0) {
        $stmt = $db->prepare("UPDATE ADMINISTRATOR SET password = ?, status = 'active' WHERE email = ?");
        $stmt->execute([$hashed, $email]);
        echo "<script>alert('Instructor password reset!'); window.location.href='login.php';</script>";
    } else {
        $stmt = $db->prepare("INSERT INTO ADMINISTRATOR (full_name, email, password, position) VALUES ('Instructor User', ?, ?, 'Professor')");
        $stmt->execute([$email, $hashed]);
        echo "<script>alert('Instructor user created!'); window.location.href='login.php';</script>";
    }
}
?>