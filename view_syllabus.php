<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$syllabus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($syllabus_id <= 0) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: instructor_dashboard.php");
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get syllabus details
$query = "SELECT 
    s.*,
    a.full_name as instructor_name,
    a.email as instructor_email,
    f.file_path,
    f.is_signed
FROM SYLLABUS s
JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
WHERE s.syllabusID = :syllabus_id";

// Add permission check based on role
if ($_SESSION['role'] === 'instructor') {
    $query .= " AND s.instructorID = :instructor_id";
}

$stmt = $db->prepare($query);
$stmt->bindParam(':syllabus_id', $syllabus_id);
if ($_SESSION['role'] === 'instructor') {
    $stmt->bindParam(':instructor_id', $_SESSION['user_id']);
}

$stmt->execute();

if ($stmt->rowCount() === 0) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: instructor_dashboard.php");
    }
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
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <button class="btn btn-secondary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
                <?php else: ?>
                    <button class="btn btn-secondary" onclick="window.location.href='instructor_dashboard.php'">Back to Dashboard</button>
                <?php endif; ?>
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
        </div>
    </div>
</body>
</html>