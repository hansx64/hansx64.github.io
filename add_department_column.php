<?php
// add_department_column.php
// Migration script to add department column to ADMINISTRATOR and SYLLABUS tables

require_once 'config/database.php';

echo "Starting database migration to add department columns...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if department column exists in ADMINISTRATOR table
    $check_admin = $db->query("SHOW COLUMNS FROM ADMINISTRATOR LIKE 'department'");
    
    if ($check_admin->rowCount() == 0) {
        echo "Adding 'department' column to ADMINISTRATOR table...\n";
        $db->exec("ALTER TABLE ADMINISTRATOR ADD COLUMN department VARCHAR(100) DEFAULT 'General' AFTER position");
        echo "✓ Successfully added 'department' column to ADMINISTRATOR table\n\n";
    } else {
        echo "✓ 'department' column already exists in ADMINISTRATOR table\n\n";
    }
    
    // Check if department column exists in SYLLABUS table
    $check_syllabus = $db->query("SHOW COLUMNS FROM SYLLABUS LIKE 'department'");
    
    if ($check_syllabus->rowCount() == 0) {
        echo "Adding 'department' column to SYLLABUS table...\n";
        $db->exec("ALTER TABLE SYLLABUS ADD COLUMN department VARCHAR(100) DEFAULT 'General' AFTER course_code");
        echo "✓ Successfully added 'department' column to SYLLABUS table\n\n";
    } else {
        echo "✓ 'department' column already exists in SYLLABUS table\n\n";
    }
    
    // Update existing SYLLABUS records to inherit department from instructor
    echo "Updating existing syllabi with instructor departments...\n";
    $update_query = "UPDATE SYLLABUS s 
                     JOIN ADMINISTRATOR a ON s.instructorID = a.adminID 
                     SET s.department = a.department 
                     WHERE s.department IS NULL OR s.department = 'General'";
    $db->exec($update_query);
    echo "✓ Successfully updated existing syllabus records\n\n";
    
    // Create indexes for better performance
    echo "Creating indexes for department columns...\n";
    
    try {
        $db->exec("CREATE INDEX idx_admin_department ON ADMINISTRATOR(department)");
        echo "✓ Created index on ADMINISTRATOR.department\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index on ADMINISTRATOR.department already exists\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $db->exec("CREATE INDEX idx_syllabus_department ON SYLLABUS(department)");
        echo "✓ Created index on SYLLABUS.department\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index on SYLLABUS.department already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Commit transaction
    if ($db->inTransaction()) {
        $db->commit();
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nYou can now use department filtering in the application.\n";
    
} catch (PDOException $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
