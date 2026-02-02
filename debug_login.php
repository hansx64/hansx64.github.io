<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Login Debug Page</h2>";

// Test database connection
if (!$db) {
    echo "❌ Database connection failed!<br>";
    exit();
}
echo "✅ Database connected<br>";

// Check if administrator table exists
$tables = $db->query("SHOW TABLES")->fetchAll();
echo "<h3>Tables in database:</h3>";
foreach ($tables as $table) {
    echo current($table) . "<br>";
}

// Check for instructor user
echo "<h3>Checking for instructor user:</h3>";
$email = 'instructor@university.edu';
$stmt = $db->prepare("SELECT * FROM ADMINISTRATOR WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch();
    echo "✅ Instructor user found:<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Full Name: " . $user['full_name'] . "<br>";
    echo "Position: " . $user['position'] . "<br>";
    echo "Password Hash: " . $user['password'] . "<br>";
    echo "Password Length: " . strlen($user['password']) . " characters<br>";
    
    // Test password
    $test_password = 'instructor123';
    echo "<br>Testing password 'instructor123': ";
    if (password_verify($test_password, $user['password'])) {
        echo "✅ Password is CORRECT<br>";
    } else {
        echo "❌ Password is WRONG<br>";
        echo "Trying plain text comparison: ";
        if ($test_password === $user['password']) {
            echo "✅ Works (but password is plain text)<br>";
        } else {
            echo "❌ Also fails<br>";
        }
    }
} else {
    echo "❌ No instructor user found with email: " . $email . "<br>";
    
    // Show all users
    $all_users = $db->query("SELECT email, full_name, position FROM ADMINISTRATOR");
    echo "<h4>All users in database:</h4>";
    foreach ($all_users as $user) {
        echo "Email: " . $user['email'] . " | Name: " . $user['full_name'] . " | Position: " . $user['position'] . "<br>";
    }
}

// Test the actual login query
echo "<h3>Testing login.php query logic:</h3>";

$role = 'instructor'; // Try with 'admin' too
$email = 'instructor@university.edu';

echo "Testing with role: " . $role . "<br>";
echo "Testing with email: " . $email . "<br><br>";

if ($role === 'admin') {
    $query = "SELECT adminID as id, full_name, email, password, position, 'admin' as role 
             FROM ADMINISTRATOR 
             WHERE email = :email AND status = 'active'";
    echo "Query for admin:<br>" . $query . "<br><br>";
} else {
    $query = "SELECT adminID as id, full_name, email, password, position, 'instructor' as role 
             FROM ADMINISTRATOR 
             WHERE email = :email AND status = 'active'";
    echo "Query for instructor:<br>" . $query . "<br><br>";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch();
    echo "✅ Query returned user:<br>";
    print_r($user);
} else {
    echo "❌ Query returned NO results!<br>";
    echo "This means either:<br>";
    echo "1. Email doesn't exist<br>";
    echo "2. User status is not 'active'<br>";
}

echo "<hr><h3>Quick Fix Solution:</h3>";
echo "<a href='reset_instructor.php'>Click here to reset instructor password</a>";
?>