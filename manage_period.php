<?php
// manage_period.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                // Create new submission period
                $stmt = $db->prepare("INSERT INTO SUBMISSION_PERIOD (period_name, start_date, end_date, description, is_active) VALUES (?, ?, ?, ?, ?)");
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt->execute([
                    $_POST['period_name'],
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $_POST['description'],
                    $is_active
                ]);
                
                $message = "Submission period created successfully!";
                $message_type = "success";
            } 
            elseif ($_POST['action'] === 'update') {
                // Update existing period
                $stmt = $db->prepare("UPDATE SUBMISSION_PERIOD SET period_name = ?, start_date = ?, end_date = ?, description = ?, is_active = ? WHERE periodID = ?");
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt->execute([
                    $_POST['period_name'],
                    $_POST['start_date'],
                    $_POST['end_date'],
                    $_POST['description'],
                    $is_active,
                    $_POST['period_id']
                ]);
                
                $message = "Submission period updated successfully!";
                $message_type = "success";
            }
            elseif ($_POST['action'] === 'delete') {
                // Delete period
                $stmt = $db->prepare("DELETE FROM SUBMISSION_PERIOD WHERE periodID = ?");
                $stmt->execute([$_POST['period_id']]);
                
                $message = "Submission period deleted successfully!";
                $message_type = "success";
            }
            elseif ($_POST['action'] === 'toggle_active') {
                // Toggle active status
                $stmt = $db->prepare("UPDATE SUBMISSION_PERIOD SET is_active = ? WHERE periodID = ?");
                $stmt->execute([
                    $_POST['is_active'],
                    $_POST['period_id']
                ]);
                
                $message = "Period status updated successfully!";
                $message_type = "success";
            }
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get all submission periods
$periods_query = "SELECT * FROM SUBMISSION_PERIOD ORDER BY created_at DESC";
$periods_stmt = $db->query($periods_query);
$periods = $periods_stmt->fetchAll();

// Get current active period
$active_query = "SELECT * FROM SUBMISSION_PERIOD WHERE is_active = 1 ORDER BY start_date DESC LIMIT 1";
$active_stmt = $db->query($active_query);
$active_period = $active_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Submission Period - Syllabus Repository</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .period-form {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #000000ff;
        }
        
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            color: #020202ff;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-group input[type="checkbox"] {
            margin: 8px;
            color: #000000ff;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .active-period-card {
            background: linear-gradient(135deg, rgba(31, 0, 83, 0.2), rgba(156, 39, 176, 0.2));
            border: 1px solid rgba(103, 58, 183, 0.5);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .active-period-card h3 {
            margin-top: 0;
            color: #2f1a48ff;
        }
        
        .period-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        
        .period-info-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 8px;
        }
        
        .period-info-label {
            font-size: 14px;
            color: #000000ff;
            margin-bottom: 4px;
        }
        
        .period-info-value {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }
        
        .period-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }
        
        .period-card:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }
        
        .period-card.active {
            border-color: rgba(103, 58, 183, 0.5);
            background: rgba(103, 58, 183, 0.1);
        }
        
        .period-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }
        
        .period-title {
            font-size: 18px;
            font-weight: 700;
            color: #000000ff;
            margin: 0;
        }
        
        .period-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .period-status.active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }
        
        .period-status.inactive {
            background: rgba(158, 158, 158, 0.2);
            color: #9e9e9e;
        }
        
        .period-dates {
            display: flex;
            gap: 24px;
            margin-bottom: 12px;
            font-size: 14px;
            color: #000000ff;
        }
        
        .period-description {
            color: #000000ff;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .period-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            transition: 0.4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #673ab7;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h2>Manage Submission Period</h2>
                <p class="user-info"><?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" onclick="window.location.href='admin_dashboard.php'">Back to Dashboard</button>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($active_period): ?>
        <div class="active-period-card">
            <h3>📅 Current Active Period</h3>
            <div class="period-info">
                <div class="period-info-item">
                    <div class="period-info-label">Period Name</div>
                    <div class="period-info-value"><?php echo htmlspecialchars($active_period['period_name']); ?></div>
                </div>
                <div class="period-info-item">
                    <div class="period-info-label">Start Date</div>
                    <div class="period-info-value"><?php echo date('M d, Y', strtotime($active_period['start_date'])); ?></div>
                </div>
                <div class="period-info-item">
                    <div class="period-info-label">End Date</div>
                    <div class="period-info-value"><?php echo date('M d, Y', strtotime($active_period['end_date'])); ?></div>
                </div>
                <div class="period-info-item">
                    <div class="period-info-label">Status</div>
                    <div class="period-info-value">
                        <?php
                        $now = new DateTime();
                        $start = new DateTime($active_period['start_date']);
                        $end = new DateTime($active_period['end_date']);
                        
                        if ($now < $start) {
                            echo "Upcoming";
                        } elseif ($now > $end) {
                            echo "Ended";
                        } else {
                            echo "Active";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">Create New Submission Period</h3>
            </div>

            <form method="POST" class="period-form">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="period_name">Period Name *</label>
                    <input type="text" id="period_name" name="period_name" required placeholder="e.g., Fall 2026 Submission">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date *</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Optional description for this submission period"></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="is_active">
                        <span>Set as active period</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-accent">Create Submission Period</button>
            </form>
        </div>

        <div class="content-section">
            <div class="section-header">
                <h3 class="section-title">All Submission Periods</h3>
            </div>

            <?php if (empty($periods)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">No submission periods created yet.</p>
            <?php else: ?>
                <?php foreach ($periods as $period): ?>
                <div class="period-card <?php echo $period['is_active'] ? 'active' : ''; ?>">
                    <div class="period-header">
                        <h4 class="period-title"><?php echo htmlspecialchars($period['period_name']); ?></h4>
                        <span class="period-status <?php echo $period['is_active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $period['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>

                    <div class="period-dates">
                        <div>
                            <strong>Start:</strong> <?php echo date('M d, Y', strtotime($period['start_date'])); ?>
                        </div>
                        <div>
                            <strong>End:</strong> <?php echo date('M d, Y', strtotime($period['end_date'])); ?>
                        </div>
                    </div>

                    <?php if ($period['description']): ?>
                    <div class="period-description">
                        <?php echo htmlspecialchars($period['description']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="period-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="period_id" value="<?php echo $period['periodID']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $period['is_active'] ? 0 : 1; ?>">
                            <button type="submit" class="btn btn-sm btn-info">
                                <?php echo $period['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>

                        <button class="btn btn-sm btn-accent" onclick="editPeriod(<?php echo htmlspecialchars(json_encode($period)); ?>)">
                            Edit
                        </button>

                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this period?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="period_id" value="<?php echo $period['periodID']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: #1a1a2e; padding: 32px; border-radius: 16px; max-width: 600px; width: 90%;">
            <h3 style="margin-top: 0;">Edit Submission Period</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="period_id" id="edit_period_id">
                
                <div class="form-group">
                    <label for="edit_period_name">Period Name *</label>
                    <input type="text" id="edit_period_name" name="period_name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_start_date">Start Date *</label>
                        <input type="date" id="edit_start_date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_end_date">End Date *</label>
                        <input type="date" id="edit_end_date" name="end_date" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="edit_is_active">
                        <span>Set as active period</span>
                    </label>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 24px;">
                    <button type="submit" class="btn btn-accent">Update Period</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editPeriod(period) {
            document.getElementById('edit_period_id').value = period.periodID;
            document.getElementById('edit_period_name').value = period.period_name;
            document.getElementById('edit_start_date').value = period.start_date;
            document.getElementById('edit_end_date').value = period.end_date;
            document.getElementById('edit_description').value = period.description || '';
            document.getElementById('edit_is_active').checked = period.is_active == 1;
            
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>