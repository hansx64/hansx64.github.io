<?php
// archive_syllabus.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$syllabus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

if ($syllabus_id <= 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get syllabus details
$query = "SELECT s.*, a.full_name as instructor_name, f.is_signed
          FROM SYLLABUS s
          JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
          LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
          WHERE s.syllabusID = :syllabus_id AND s.status = 'approved'";
$stmt = $db->prepare($query);
$stmt->bindParam(':syllabus_id', $syllabus_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$syllabus = $stmt->fetch();

// Check if signed copy exists
if (!$syllabus['is_signed']) {
    $error = 'Cannot archive: Signed copy must be uploaded first';
}

// Handle archive submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $syllabus['is_signed']) {
    $location = trim($_POST['location']);
    $archived_by = trim($_POST['archived_by']);
    
    if (empty($location)) {
        $error = 'Archive location is required';
    } else {
        try {
            $db->beginTransaction();
            
            // Generate archive code
            $archive_code = 'ARC-' . date('Y') . '-' . str_pad($syllabus_id, 5, '0', STR_PAD_LEFT);
            
            // Insert archive record
            $archive_query = "INSERT INTO ARCHIVE (adminID, archive_code, location, archived_by, archived_at) 
                            VALUES (:admin_id, :archive_code, :location, :archived_by, NOW())";
            $archive_stmt = $db->prepare($archive_query);
            $archive_stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $archive_stmt->bindParam(':archive_code', $archive_code);
            $archive_stmt->bindParam(':location', $location);
            $archive_stmt->bindParam(':archived_by', $archived_by);
            $archive_stmt->execute();
            
            // Update syllabus status
            $update_query = "UPDATE SYLLABUS SET status = 'archived' WHERE syllabusID = :syllabus_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':syllabus_id', $syllabus_id);
            $update_stmt->execute();
            
            $db->commit();
            
            $message = 'Syllabus archived successfully! Archive Code: ' . $archive_code;
            header("refresh:3;url=admin_dashboard.php");
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to archive syllabus: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Syllabus - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Archive Syllabus</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>

        <div class="content-section" style="max-width: 800px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="syllabus-detail" style="margin-bottom: 2rem;">
                <div class="detail-row">
                    <div class="detail-label">Course Code:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['course_code']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Title:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['title']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Instructor:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['instructor_name']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Approved On:</div>
                    <div class="detail-value"><?php echo date('F d, Y', strtotime($syllabus['approved_at'])); ?></div>
                </div>
            </div>

            <?php if ($syllabus['is_signed']): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Archive Location *</label>
                    <input type="text" name="location" required placeholder="e.g., Physical Archive - Room 101" 
                           value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Archived By *</label>
                    <input type="text" name="archived_by" required placeholder="Name of person archiving"
                           value="<?php echo isset($_POST['archived_by']) ? htmlspecialchars($_POST['archived_by']) : htmlspecialchars($_SESSION['user_name']); ?>">
                </div>

                <div style="background: #f5f5f5; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                    <p style="margin-bottom: 0.5rem; font-weight: 600; color: var(--text-dark);">⚠️ Important:</p>
                    <p style="color: #6c757d; margin: 0;">
                        Archiving this syllabus will mark it as archived in the system. 
                        Make sure the signed physical copy is properly stored in the specified location.
                    </p>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="window.location.href='admin_dashboard.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;" onclick="return confirm('Are you sure you want to archive this syllabus?')">
                        Archive Syllabus
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; background: #f5f5f5; border-radius: 12px;">
                    <p style="color: #6c757d; font-size: 1.1rem; margin-bottom: 1rem;">
                        Cannot archive this syllabus.
                    </p>
                    <p style="color: #595959;">
                        A signed copy must be uploaded before archiving.
                    </p>
                    <a href="upload_signed.php?id=<?php echo $syllabus_id; ?>" class="btn btn-accent" style="display: inline-block; margin-top: 1rem;">
                        Upload Signed Copy
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>