<?php
// add_submission_period_table.php
// Run this file once to add the SUBMISSION_PERIOD table to your database

session_start();

// Only allow from localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("Access denied!");
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Create SUBMISSION_PERIOD table
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS SUBMISSION_PERIOD (
        periodID INT PRIMARY KEY AUTO_INCREMENT,
        period_name VARCHAR(200) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($create_table_sql);
    echo "✅ SUBMISSION_PERIOD table created successfully!<br>";
    
    // Check if there are any existing periods
    $check_stmt = $db->query("SELECT COUNT(*) as count FROM SUBMISSION_PERIOD");
    $result = $check_stmt->fetch();
    
    if ($result['count'] == 0) {
        // Insert a sample submission period
        $insert_sample = "
        INSERT INTO SUBMISSION_PERIOD (period_name, start_date, end_date, description, is_active) 
        VALUES (
            'Spring 2026 Submission Period',
            '2026-02-01',
            '2026-03-31',
            'Spring semester syllabus submission period for all instructors.',
            1
        )";
        
        $db->exec($insert_sample);
        echo "✅ Sample submission period added!<br>";
    }
    
    echo "<hr>";
    echo "<h3>Database Update Complete!</h3>";
    echo "<p>The SUBMISSION_PERIOD table has been added to your database.</p>";
    echo "<a href='manage_period.php'>Go to Manage Submission Period</a> | ";
    echo "<a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
