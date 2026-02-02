<?php
// upload_syllabus.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $course_code = trim($_POST['course_code']);
    $description = trim($_POST['description']);
    
    // Validate inputs
    if (empty($title) || empty($course_code)) {
        $error = 'Title and course code are required';
    } elseif (!isset($_FILES['syllabus_file']) || $_FILES['syllabus_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a PDF file to upload';
    } else {
        $file = $_FILES['syllabus_file'];
        
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
            $new_filename = 'syllabus_' . time() . '_' . uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $new_filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    // Start transaction
                    $db->beginTransaction();
                    
                    // Get instructor's department
                    $dept_query = "SELECT department FROM ADMINISTRATOR WHERE adminID = :instructor_id";
                    $dept_stmt = $db->prepare($dept_query);
                    $dept_stmt->bindParam(':instructor_id', $_SESSION['user_id']);
                    $dept_stmt->execute();
                    $instructor_dept = $dept_stmt->fetch(PDO::FETCH_ASSOC);
                    $department = $instructor_dept['department'] ?? 'General';
                    
                    // Insert syllabus record
                    $syllabus_query = "INSERT INTO SYLLABUS (instructorID, title, course_code, department, description, status, submitted_at) 
                                      VALUES (:instructor_id, :title, :course_code, :department, :description, 'pending', NOW())";
                    $syllabus_stmt = $db->prepare($syllabus_query);
                    $syllabus_stmt->bindParam(':instructor_id', $_SESSION['user_id']);
                    $syllabus_stmt->bindParam(':title', $title);
                    $syllabus_stmt->bindParam(':course_code', $course_code);
                    $syllabus_stmt->bindParam(':department', $department);
                    $syllabus_stmt->bindParam(':description', $description);
                    $syllabus_stmt->execute();
                    
                    $syllabus_id = $db->lastInsertId();
                    
                    // Insert file record
                    $file_query = "INSERT INTO FILE (syllabusID, file_path, version_no, uploaded_at, is_signed) 
                                  VALUES (:syllabus_id, :file_path, 1, NOW(), 0)";
                    $file_stmt = $db->prepare($file_query);
                    $file_stmt->bindParam(':syllabus_id', $syllabus_id);
                    $file_stmt->bindParam(':file_path', $file_path);
                    $file_stmt->execute();
                    
                    // Commit transaction
                    $db->commit();
                    
                    $message = 'Syllabus uploaded successfully!';
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=instructor_dashboard.php");
                } catch (Exception $e) {
                    $db->rollBack();
                    // Delete uploaded file if database insert fails
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $error = 'Failed to upload syllabus: ' . $e->getMessage();
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
    <title>Upload Syllabus - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Upload Syllabus</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='instructor_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>

        <div class="content-section" style="max-width: 800px; margin: 0 auto;">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Course Code *</label>
                    <input type="text" name="course_code" required placeholder="e.g., CS-301" 
                           value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Course Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Data Structures & Algorithms"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Brief description of the course..." rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Syllabus File (PDF) *</label>
                    <div class="file-upload" onclick="document.getElementById('syllabusFile').click()">
                        <div class="file-upload-icon">📄</div>
                        <p><strong>Click to upload</strong> or drag and drop</p>
                        <p style="color: #6c757d; font-size: 0.85rem; margin-top: 0.5rem;">PDF files only (Max 10MB)</p>
                    </div>
                    <input type="file" id="syllabusFile" name="syllabus_file" accept=".pdf" required style="display: none;" onchange="showFileName(this)">
                    <p id="fileName" style="margin-top: 0.5rem; color: #2d2d2d; font-weight: 600;"></p>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" style="flex: 1;" onclick="window.location.href='instructor_dashboard.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Syllabus</button>
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