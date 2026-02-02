<?php
// view_my_syllabus.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$syllabus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($syllabus_id <= 0) {
    header("Location: instructor_dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get syllabus details with review information
$query = "SELECT 
    s.*,
    f.file_path,
    f.is_signed,
    r.decision,
    r.comments as review_comments,
    r.review_date,
    a.full_name as reviewed_by
FROM SYLLABUS s
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
LEFT JOIN REVIEW r ON s.syllabusID = r.syllabusID
LEFT JOIN ADMINISTRATOR a ON r.adminID = a.adminID
WHERE s.syllabusID = :syllabus_id AND s.instructorID = :instructor_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':syllabus_id', $syllabus_id);
$stmt->bindParam(':instructor_id', $_SESSION['user_id']);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: instructor_dashboard.php");
    exit();
}

$syllabus = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Syllabus - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Syllabus Details</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='instructor_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>

        <div class="content-section" style="max-width: 900px; margin: 0 auto;">
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
                    <div class="detail-label">Description:</div>
                    <div class="detail-value"><?php echo htmlspecialchars($syllabus['description'] ?: 'No description provided'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <?php 
                        $status_class = 'status-' . $syllabus['status'];
                        if ($syllabus['status'] === 'pending') {
                            $status_text = 'Under Review';
                            $status_class = 'status-submitted';
                        } elseif ($syllabus['status'] === 'rejected') {
                            $status_text = 'Needs Revision';
                            $status_class = 'status-rejected';
                        } else {
                            $status_text = ucfirst($syllabus['status']);
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Submitted:</div>
                    <div class="detail-value"><?php echo date('F d, Y g:i A', strtotime($syllabus['submitted_at'])); ?></div>
                </div>
                <?php if ($syllabus['approved_at']): ?>
                <div class="detail-row">
                    <div class="detail-label">Approved:</div>
                    <div class="detail-value"><?php echo date('F d, Y g:i A', strtotime($syllabus['approved_at'])); ?></div>
                </div>
                <?php endif; ?>
                <div class="detail-row">
                    <div class="detail-label">File:</div>
                    <div class="detail-value">
                        <?php if ($syllabus['file_path']): ?>
                            <a href="<?php echo htmlspecialchars($syllabus['file_path']); ?>" target="_blank" class="btn btn-sm btn-info">View PDF</a>
                            <?php if ($syllabus['is_signed']): ?>
                                <span style="margin-left: 1rem; color: #2d2d2d; font-weight: 600;">✓ Signed Copy</span>
                            <?php endif; ?>
                        <?php else: ?>
                            No file uploaded
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($syllabus['status'] === 'rejected' && $syllabus['review_comments']): ?>
            <div style="margin-top: 2rem; background: #f5f5f5; padding: 1.5rem; border-radius: 12px; border-left: 4px solid #595959;">
                <h4 style="color: var(--primary); margin-bottom: 1rem; font-family: 'Playfair Display', serif;">Review Feedback</h4>
                <p style="color: var(--text-dark); line-height: 1.6; margin-bottom: 1rem;">
                    <?php echo nl2br(htmlspecialchars($syllabus['review_comments'])); ?>
                </p>
                <?php if ($syllabus['reviewed_by']): ?>
                <p style="color: #6c757d; font-size: 0.9rem; margin: 0;">
                    <strong>Reviewed by:</strong> <?php echo htmlspecialchars($syllabus['reviewed_by']); ?>
                    on <?php echo date('F d, Y', strtotime($syllabus['review_date'])); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($syllabus['status'] === 'approved' && $syllabus['review_comments']): ?>
            <div style="margin-top: 2rem; background: #f5f5f5; padding: 1.5rem; border-radius: 12px; border-left: 4px solid #2d2d2d;">
                <h4 style="color: var(--primary); margin-bottom: 1rem; font-family: 'Playfair Display', serif;">Approval Comments</h4>
                <p style="color: var(--text-dark); line-height: 1.6; margin-bottom: 1rem;">
                    <?php echo nl2br(htmlspecialchars($syllabus['review_comments'])); ?>
                </p>
                <?php if ($syllabus['reviewed_by']): ?>
                <p style="color: #6c757d; font-size: 0.9rem; margin: 0;">
                    <strong>Reviewed by:</strong> <?php echo htmlspecialchars($syllabus['reviewed_by']); ?>
                    on <?php echo date('F d, Y', strtotime($syllabus['review_date'])); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; text-align: center;">
                <?php if ($syllabus['status'] === 'rejected'): ?>
                    <a href="resubmit_syllabus.php?id=<?php echo $syllabus_id; ?>" class="btn btn-accent">
                        Resubmit Syllabus
                    </a>
                <?php endif; ?>
                <?php if ($syllabus['status'] === 'approved' && $syllabus['is_signed']): ?>
                    <a href="download_syllabus.php?id=<?php echo $syllabus_id; ?>" class="btn btn-success">
                        Download Signed Copy
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>