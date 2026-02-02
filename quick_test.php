<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Try to login programmatically
$email = 'admin@university.edu';
$password = 'admin123';

$stmt = $db->prepare("SELECT * FROM ADMINISTRATOR WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch();
    echo "<h3>Testing Login for: " . $user['email'] . "</h3>";
    echo "Password hash in DB: " . $user['password'] . "<br>";
    
    // Test password
    echo "Testing password 'admin123': ";
    if (password_verify($password, $user['password'])) {
        echo "✅ CORRECT<br>";
        echo "You can login with these credentials!";
    } else {
        echo "❌ WRONG<br>";
        
        // Try plain text (if password isn't hashed)
        echo "Trying plain text comparison: ";
        if ($password === $user['password']) {
            echo "✅ WORKS (but password is plain text - needs hashing)<br>";
            echo "Run setup_database.php to fix this!";
        } else {
            echo "❌ ALSO FAILS<br>";
            echo "Run setup_database.php to reset passwords!";
        }
    }
} else {
    echo "❌ No user found with email: " . $email . "<br>";
    echo "Run setup_database.php to create users!";
}
?>