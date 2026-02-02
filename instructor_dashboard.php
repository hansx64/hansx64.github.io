<?php
// instructor_dashboard.php
session_start();

// Check if user is logged in and is instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$instructor_id = $_SESSION['user_id'];

// Get instructor's department
$dept_query = "SELECT department FROM ADMINISTRATOR WHERE adminID = :instructor_id";
$dept_stmt = $db->prepare($dept_query);
$dept_stmt->bindParam(':instructor_id', $instructor_id);
$dept_stmt->execute();
$instructor_info = $dept_stmt->fetch(PDO::FETCH_ASSOC);
$instructor_department = $instructor_info['department'] ?? 'General';

// Get search parameter
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause for search
$where_conditions = ["s.department = :department"];
$params = [':department' => $instructor_department];

if (!empty($search_query)) {
    $where_conditions[] = "(s.course_code LIKE :search OR s.title LIKE :search OR a.full_name LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get instructor's personal statistics
$my_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN f.is_signed = 1 THEN 1 ELSE 0 END) as downloadable
FROM SYLLABUS s
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
WHERE s.instructorID = :instructor_id";
$my_stats_stmt = $db->prepare($my_stats_query);
$my_stats_stmt->bindParam(':instructor_id', $instructor_id);
$my_stats_stmt->execute();
$my_stats = $my_stats_stmt->fetch();

// Get department-wide statistics
$dept_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
FROM SYLLABUS s
WHERE s.department = :department";
$dept_stats_stmt = $db->prepare($dept_stats_query);
$dept_stats_stmt->bindParam(':department', $instructor_department);
$dept_stats_stmt->execute();
$dept_stats = $dept_stats_stmt->fetch();

// Get all syllabi from instructor's department
$syllabi_query = "SELECT 
    s.syllabusID,
    s.instructorID,
    s.title,
    s.course_code,
    s.description,
    s.status,
    s.submitted_at,
    s.approved_at,
    a.full_name as instructor_name,
    f.file_path,
    f.is_signed,
    r.decision,
    r.comments as review_comments
FROM SYLLABUS s
JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
LEFT JOIN REVIEW r ON s.syllabusID = r.syllabusID
$where_clause
ORDER BY s.submitted_at DESC";
$syllabi_stmt = $db->prepare($syllabi_query);
foreach ($params as $key => $value) {
    $syllabi_stmt->bindValue($key, $value);
}
$syllabi_stmt->execute();
$syllabi = $syllabi_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 2px solid #e0e0e0;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .search-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .search-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .search-group input {
            padding: 0.75rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .search-group input:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }
        
        .search-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .my-submission-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .my-submission-row {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        .department-badge {
            display: inline-flex;
            align-items: center;
            background: #1a1a1a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Instructor Dashboard</h2>
                <p class="user-info">
                    <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                    <span class="department-badge"><?php echo htmlspecialchars($instructor_department); ?></span>
                </p>
            </div>
            <div class="header-right">
                <button class="btn btn-accent" onclick="window.location.href='upload_syllabus.php'">
                    Upload Syllabus
                </button>
                <button class="btn btn-info" onclick="window.location.href='change_password.php'">
                    Change Password
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-container">
            <form method="GET" action="" id="searchForm" class="search-form">
                <div class="search-group">
                    <label>Search Department Syllabi</label>
                    <input type="text" name="search" id="searchInput" 
                           placeholder="Search by course code, title, or instructor..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="search-buttons">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <button type="button" class="btn btn-secondary" onclick="clearSearch()">Clear</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">My Submissions</div>
                <div class="stat-value"><?php echo $my_stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Under Review</div>
                <div class="stat-value"><?php echo $my_stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">My Approved</div>
                <div class="stat-value"><?php echo $my_stats['approved']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Department Total</div>
                <div class="stat-value"><?php echo $dept_stats['total']; ?></div>
            </div>
        </div>

        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">
                    <?php echo htmlspecialchars($instructor_department); ?> Department Syllabi
                </h3>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Instructor</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($syllabi) > 0): ?>
                            <?php foreach ($syllabi as $syllabus): ?>
                            <tr class="<?php echo ($syllabus['instructorID'] == $instructor_id) ? 'my-submission-row' : ''; ?>">
                                <td><?php echo htmlspecialchars($syllabus['course_code']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($syllabus['title']); ?>
                                    <?php if ($syllabus['instructorID'] == $instructor_id): ?>
                                        <span class="my-submission-badge">My Submission</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($syllabus['instructor_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($syllabus['submitted_at'])); ?></td>
                                <td>
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
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($syllabus['instructorID'] == $instructor_id): ?>
                                            <a href="view_my_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-info">
                                                <?php echo $syllabus['status'] === 'rejected' ? 'View Feedback' : 'View'; ?>
                                            </a>
                                            <?php if ($syllabus['status'] === 'approved' && $syllabus['is_signed']): ?>
                                                <a href="download_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-success">Download</a>
                                            <?php endif; ?>
                                            <?php if ($syllabus['status'] === 'rejected'): ?>
                                                <a href="resubmit_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-accent">Resubmit</a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="view_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-info">View</a>
                                            <?php if ($syllabus['status'] === 'approved' && $syllabus['is_signed']): ?>
                                                <a href="download_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-success">Download</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #6c757d;">
                                    <?php if (!empty($search_query)): ?>
                                        No syllabi found matching your search.
                                    <?php else: ?>
                                        No syllabi in your department yet. Click "Upload Syllabus" to get started.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function clearSearch() {
            window.location.href = 'instructor_dashboard.php';
        }
    </script>
</body>
</html>