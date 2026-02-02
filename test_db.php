<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "✅ Database connection successful!<br>";
    
    $stmt = $db->query("SHOW TABLES LIKE 'ADMINISTRATOR'");
    if ($stmt->rowCount() > 0) {
        echo "✅ ADMINISTRATOR table exists!<br>";
        
        $users = $db->query("SELECT adminID, email, password FROM ADMINISTRATOR");
        foreach ($users as $user) {
            echo "User: " . $user['email'] . " | Password: " . $user['password'] . "<br>";
        }
    } else {
        echo "❌ ADMINISTRATOR table does not exist!";
    }
} else {
    echo "❌ Database connection failed!";
}
?>