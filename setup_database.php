<?php
session_start();

// Only allow from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("Access denied!");
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Create tables
    $tables_sql = "
    CREATE TABLE IF NOT EXISTS ADMINISTRATOR (
        adminID INT PRIMARY KEY AUTO_INCREMENT,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        position VARCHAR(50),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS SYLLABUS (
        syllabusID INT PRIMARY KEY AUTO_INCREMENT,
        instructorID INT,
        title VARCHAR(200) NOT NULL,
        course_code VARCHAR(50) NOT NULL,
        description TEXT,
        status ENUM('pending', 'approved', 'rejected', 'archived') DEFAULT 'pending',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        FOREIGN KEY (instructorID) REFERENCES ADMINISTRATOR(adminID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS FILE (
        fileID INT PRIMARY KEY AUTO_INCREMENT,
        syllabusID INT,
        file_path VARCHAR(500) NOT NULL,
        version_no INT DEFAULT 1,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_signed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (syllabusID) REFERENCES SYLLABUS(syllabusID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS REVIEW (
        reviewID INT PRIMARY KEY AUTO_INCREMENT,
        syllabusID INT,
        adminID INT,
        decision ENUM('approved', 'rejected') NOT NULL,
        comments TEXT,
        review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (syllabusID) REFERENCES SYLLABUS(syllabusID) ON DELETE CASCADE,
        FOREIGN KEY (adminID) REFERENCES ADMINISTRATOR(adminID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS ARCHIVE (
        archiveID INT PRIMARY KEY AUTO_INCREMENT,
        adminID INT,
        archive_code VARCHAR(50) UNIQUE NOT NULL,
        location VARCHAR(200) NOT NULL,
        archived_by VARCHAR(100) NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (adminID) REFERENCES ADMINISTRATOR(adminID) ON DELETE CASCADE
    );
    ";

    $db->exec($tables_sql);
    echo "✅ Tables created successfully!<br>";

    // Hash passwords
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $instructor_password = password_hash('instructor123', PASSWORD_DEFAULT);

    // Insert users
    $users = [
        ['Admin User', 'admin@university.edu', $admin_password, 'Administrator'],
        ['Instructor User', 'instructor@university.edu', $instructor_password, 'Professor']
    ];

    foreach ($users as $user) {
        $check = $db->prepare("SELECT adminID FROM ADMINISTRATOR WHERE email = ?");
        $check->execute([$user[1]]);
        
        if ($check->rowCount() == 0) {
            $stmt = $db->prepare("INSERT INTO ADMINISTRATOR (full_name, email, password, position) VALUES (?, ?, ?, ?)");
            $stmt->execute($user);
            echo "✅ User added: " . $user[1] . "<br>";
        } else {
            $stmt = $db->prepare("UPDATE ADMINISTRATOR SET password = ? WHERE email = ?");
            $stmt->execute([$user[2], $user[1]]);
            echo "✅ Password updated for: " . $user[1] . "<br>";
        }
    }

    echo "<hr><h3>Test Credentials:</h3>";
    echo "Admin: admin@university.edu / admin123<br>";
    echo "Instructor: instructor@university.edu / instructor123<br>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>