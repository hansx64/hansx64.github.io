<?php
// review_syllabus.php
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
$query = "SELECT 
    s.*,
    a.full_name as instructor_name,
    a.email as instructor_email,
    a.position as instructor_position,
    f.file_path,
    f.is_signed
FROM SYLLABUS s
JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
WHERE s.syllabusID = :syllabus_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':syllabus_id', $syllabus_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$syllabus = $stmt->fetch();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'];
    $comments = trim($_POST['comments']);
    
    if (!in_array($decision, ['approved', 'rejected'])) {
        $error = 'Invalid decision';
    } elseif ($decision === 'rejected' && empty($comments)) {
        $error = 'Comments are required for rejection';
    } else {
        try {
            $db->beginTransaction();
            
            // Insert review record
            $review_query = "INSERT INTO REVIEW (syllabusID, adminID, decision, comments, review_date) 
                           VALUES (:syllabus_id, :admin_id, :decision, :comments, NOW())";
            $review_stmt = $db->prepare($review_query);
            $review_stmt->bindParam(':syllabus_id', $syllabus_id);
            $review_stmt->bindParam(':admin_id', $_SESSION['user_id']);
            $review_stmt->bindParam(':decision', $decision);
            $review_stmt->bindParam(':comments', $comments);
            $review_stmt->execute();
            
            // Update syllabus status
            if ($decision === 'approved') {
                $update_query = "UPDATE SYLLABUS SET status = 'approved', approved_at = NOW() WHERE syllabusID = :syllabus_id";
            } else {
                $update_query = "UPDATE SYLLABUS SET status = 'rejected' WHERE syllabusID = :syllabus_id";
            }
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':syllabus_id', $syllabus_id);
            $update_stmt->execute();
            
            $db->commit();
            
            $message = 'Syllabus ' . $decision . ' successfully!';
            header("refresh:2;url=admin_dashboard.php");
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to submit review: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Syllabus - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Review Syllabus</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>

        <div class="content-section" style="max-width: 900px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="syllabus-detail">
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
                    <div class="detail-label">Email:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['instructor_email']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['description'] ?: 'No description provided'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-badge status-<?php echo $syllabus['status']; ?>">
                            <?php echo ucfirst($syllabus['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Submitted:</div>
                    <div class="detail-value"><?php echo date('F d, Y g:i A', strtotime($syllabus['submitted_at'])); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">File:</div>
                    <div class="detail-value">
                        <?php if ($syllabus['file_path']): ?>
                            <a href="<?php echo htmlspecialchars($syllabus['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View PDF</a>
                        <?php else: ?>
                            No file uploaded
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($syllabus['status'] === 'pending'): ?>
            <form method="POST" action="" style="margin-top: 2rem;">
                <div class="form-group">
                    <label>Review Comments</label>
                    <textarea name="comments" placeholder="Enter your review comments here..." rows="6" required></textarea>
                    <small style="color: #6c757d; display: block; margin-top: 0.5rem;">
                        * Comments are required for rejection. For approval, you can provide optional feedback.
                    </small>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" name="decision" value="approved" class="btn btn-success" style="flex: 1;">
                        Approve Syllabus
                    </button>
                    <button type="submit" name="decision" value="rejected" class="btn btn-danger" style="flex: 1;">
                        Reject Syllabus
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div style="margin-top: 2rem; text-align: center; padding: 2rem; background: #f5f5f5; border-radius: 12px;">
                    <p style="color: #6c757d; font-size: 1.1rem;">
                        This syllabus has already been reviewed.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>