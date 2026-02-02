<?php
// upload_signed.php
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
$query = "SELECT s.*, a.full_name as instructor_name
          FROM SYLLABUS s
          JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
          WHERE s.syllabusID = :syllabus_id AND s.status = 'approved'";
$stmt = $db->prepare($query);
$stmt->bindParam(':syllabus_id', $syllabus_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$syllabus = $stmt->fetch();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['signed_file']) || $_FILES['signed_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a PDF file to upload';
    } else {
        $file = $_FILES['signed_file'];
        
        // Check file type
        $allowed_types = ['application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $error = 'Only PDF files are allowed';
        } elseif ($file['size'] > 10485760) { // 10MB
            $error = 'File size must not exceed 10MB';
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = 'uploads/syllabi/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'syllabus_signed_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    $db->beginTransaction();
                    
                    // Get current version number
                    $version_query = "SELECT MAX(version_no) as max_version FROM FILE WHERE syllabusID = :syllabus_id";
                    $version_stmt = $db->prepare($version_query);
                    $version_stmt->bindParam(':syllabus_id', $syllabus_id);
                    $version_stmt->execute();
                    $version_result = $version_stmt->fetch();
                    $new_version = ($version_result['max_version'] ?? 0) + 1;
                    
                    // Insert new file record with signed flag
                    $file_query = "INSERT INTO FILE (syllabusID, file_path, version_no, uploaded_at, is_signed) 
                                  VALUES (:syllabus_id, :file_path, :version_no, NOW(), 1)";
                    $file_stmt = $db->prepare($file_query);
                    $file_stmt->bindParam(':syllabus_id', $syllabus_id);
                    $file_stmt->bindParam(':file_path', $file_path);
                    $file_stmt->bindParam(':version_no', $new_version);
                    $file_stmt->execute();
                    
                    $db->commit();
                    
                    $message = 'Signed copy uploaded successfully!';
                    header("refresh:2;url=admin_dashboard.php");
                } catch (Exception $e) {
                    $db->rollBack();
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $error = 'Failed to upload signed copy: ' . $e->getMessage();
                }
            } else {
                $error = 'Failed to move uploaded file';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Signed Copy - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Upload Signed Copy</h2>
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
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Signed Syllabus Copy (PDF) *</label>
                    <div class="file-upload" onclick="document.getElementById('signedFile').click()">
                        <div class="file-upload-icon">📝</div>
                        <p><strong>Click to upload signed copy</strong></p>
                        <p style="color: #6c757d; font-size: 0.85rem; margin-top: 0.5rem;">PDF files only (Max 10MB)</p>
                    </div>
                    <input type="file" id="signedFile" name="signed_file" accept=".pdf" required style="display: none;" onchange="showFileName(this)">
                    <p id="fileName" style="margin-top: 0.5rem; color: #2d2d2d; font-weight: 600;"></p>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="window.location.href='admin_dashboard.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Upload Signed Copy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : '';
            document.getElementById('fileName').textContent = fileName ? 'Selected: ' + fileName : '';
        }
    </script>
</body>
</html>