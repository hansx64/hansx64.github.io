<?php
// admin_dashboard.php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];

if (!empty($department_filter)) {
    $where_conditions[] = "s.department = :department";
    $params[':department'] = $department_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(s.course_code LIKE :search OR s.title LIKE :search OR a.full_name LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN s.status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN s.status = 'archived' THEN 1 ELSE 0 END) as archived
FROM SYLLABUS s
JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
$where_clause";
$stats_stmt = $db->prepare($stats_query);
foreach ($params as $key => $value) {
    $stats_stmt->bindValue($key, $value);
}
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Get all syllabi with instructor information
$syllabi_query = "SELECT 
    s.syllabusID,
    s.title,
    s.course_code,
    s.department,
    s.description,
    s.status,
    s.submitted_at,
    s.approved_at,
    a.full_name as instructor_name,
    a.email as instructor_email,
    f.file_path,
    f.is_signed
FROM SYLLABUS s
JOIN ADMINISTRATOR a ON s.instructorID = a.adminID
LEFT JOIN FILE f ON s.syllabusID = f.syllabusID
$where_clause
ORDER BY s.submitted_at DESC";
$syllabi_stmt = $db->prepare($syllabi_query);
foreach ($params as $key => $value) {
    $syllabi_stmt->bindValue($key, $value);
}
$syllabi_stmt->execute();
$syllabi = $syllabi_stmt->fetchAll();

// Define departments
$departments = [
    'BS Accountancy',
    'BS Business Administration',
    'BS Office Administration',
    'BS Information Technology'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filters-container {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 2px solid #e0e0e0;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.75rem 1rem;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .active-filters {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #1a1a1a;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .filter-tag button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
            padding: 0;
            margin-left: 0.25rem;
        }
        
        .filter-tag button:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Administrator Dashboard</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-accent" onclick="window.location.href='manage_period.php'">
                    Manage Submission Period
                </button>
                <button class="btn btn-info" onclick="window.location.href='change_password.php'">
                    Change Password
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-container">
            <form method="GET" action="" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department" id="departmentFilter" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Departments</option>
                            <option value="BS Accountancy" <?php echo ($department_filter === 'BS Accountancy') ? 'selected' : ''; ?>>BS Accountancy</option>
                            <option value="BS Business Administration" <?php echo ($department_filter === 'BS Business Administration') ? 'selected' : ''; ?>>BS Business Administration</option>
                            <option value="BS Office Administration" <?php echo ($department_filter === 'BS Office Administration') ? 'selected' : ''; ?>>BS Office Administration</option>
                            <option value="BS Information Technology" <?php echo ($department_filter === 'BS Information Technology') ? 'selected' : ''; ?>>BS Information Technology</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" id="searchInput" 
                               placeholder="Search by course code, title, or instructor..." 
                               value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <button type="button" class="btn btn-secondary" onclick="clearFilters()">Clear</button>
                    </div>
                </div>
            </form>
            
            <?php if (!empty($department_filter) || !empty($search_query)): ?>
                <div class="active-filters">
                    <?php if (!empty($department_filter)): ?>
                        <span class="filter-tag">
                            Department: <?php echo htmlspecialchars($department_filter); ?>
                            <button onclick="removeFilter('department')" title="Remove filter">×</button>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($search_query)): ?>
                        <span class="filter-tag">
                            Search: "<?php echo htmlspecialchars($search_query); ?>"
                            <button onclick="removeFilter('search')" title="Remove filter">×</button>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Submissions</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Review</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Approved</div>
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Archived</div>
                <div class="stat-value"><?php echo $stats['archived']; ?></div>
            </div>
        </div>

        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">Submitted Syllabi</h3>
            </div>

            <?php if (count($syllabi) > 0): ?>
                <?php 
                // Group syllabi by department
                $syllabi_by_dept = [];
                foreach ($syllabi as $syllabus) {
                    $dept = $syllabus['department'] ?? 'Unassigned';
                    if (!isset($syllabi_by_dept[$dept])) {
                        $syllabi_by_dept[$dept] = [];
                    }
                    $syllabi_by_dept[$dept][] = $syllabus;
                }
                
                // Sort departments
                ksort($syllabi_by_dept);
                
                // Display each department's syllabi
                foreach ($syllabi_by_dept as $dept_name => $dept_syllabi):
                ?>
                    <div class="department-section" style="margin-bottom: 2rem;">
                        <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px 12px 0 0; margin-bottom: 0;">
                            <h4 style="margin: 0; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem;">
                                <span style="background: rgba(255,255,255,0.2); padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                                    <?php echo count($dept_syllabi); ?>
                                </span>
                                <?php echo htmlspecialchars($dept_name); ?>
                            </h4>
                        </div>
                        
                        <div class="table-container" style="margin-top: 0; border-radius: 0 0 12px 12px;">
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
                                    <?php foreach ($dept_syllabi as $syllabus): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($syllabus['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($syllabus['title']); ?></td>
                                        <td><?php echo htmlspecialchars($syllabus['instructor_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($syllabus['submitted_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $status_class = 'status-' . $syllabus['status'];
                                            $status_text = ucfirst($syllabus['status']);
                                            ?>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-info">View</a>
                                                <?php if ($syllabus['status'] === 'pending'): ?>
                                                    <a href="review_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-success">Review</a>
                                                <?php endif; ?>
                                                <?php if ($syllabus['status'] === 'approved' && !$syllabus['is_signed']): ?>
                                                    <a href="upload_signed.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-accent">Upload Signed</a>
                                                <?php endif; ?>
                                                <?php if ($syllabus['status'] === 'approved' && $syllabus['is_signed']): ?>
                                                    <a href="archive_syllabus.php?id=<?php echo $syllabus['syllabusID']; ?>" class="btn btn-sm btn-secondary">Archive</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Instructor</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: #6c757d;">
                                    No syllabi found matching your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function clearFilters() {
            window.location.href = 'admin_dashboard.php';
        }
        
        function removeFilter(filterName) {
            const form = document.getElementById('filterForm');
            const input = form.querySelector(`[name="${filterName}"]`);
            if (input) {
                input.value = '';
                form.submit();
            }
        }
    </script>
</body>
</html>