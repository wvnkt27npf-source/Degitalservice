<?php 
include './config.php';
include 'header.php';

// Ensure the admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$successMessage = $errorMessage = '';

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build base query
$baseQuery = "SELECT id, name, phone_number, message, status, created_at FROM contact_form_submissions WHERE 1=1";

// Apply search filter
if (!empty($search)) {
    $baseQuery .= " AND (name LIKE '%$search%' OR phone_number LIKE '%$search%' OR message LIKE '%$search%')";
}

// Apply status filter
if ($statusFilter !== 'all') {
    $baseQuery .= " AND status = '$statusFilter'";
}

// Apply sorting
$validSortFields = ['id', 'name', 'phone_number', 'status', 'created_at'];
$sortField = in_array($sortField, $validSortFields) ? $sortField : 'created_at';
$sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
$baseQuery .= " ORDER BY $sortField $sortOrder";

// Execute query
$submissionsResult = $conn->query($baseQuery);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update submission status
    if (isset($_POST['update_status'])) {
        $submissionId = $_POST['submission_id'];
        $newStatus = $_POST['new_status'];

        $updateQuery = "UPDATE contact_form_submissions SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $submissionId);

        if ($stmt->execute()) {
            $successMessage = 'Submission status updated successfully.';
        } else {
            $errorMessage = 'There was an error updating the status.';
        }

        $stmt->close();
    }

    // Delete submission
    if (isset($_POST['delete_submission'])) {
        $submissionId = $_POST['submission_id'];

        $deleteQuery = "DELETE FROM contact_form_submissions WHERE id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $submissionId);

        if ($stmt->execute()) {
            $successMessage = 'Submission deleted successfully.';
        } else {
            $errorMessage = 'There was an error deleting the submission.';
        }

        $stmt->close();
    }

    // Refresh to show changes
    header("Location: manage_submissions.php?search=$search&status=$statusFilter&sort=$sortField&order=$sortOrder");
    exit();
}

function getSortLink($field, $currentField, $currentOrder) {
    $newOrder = ($currentField === $field && $currentOrder === 'DESC') ? 'ASC' : 'DESC';
    return "?search=" . urlencode($_GET['search'] ?? '') . 
           "&status=" . urlencode($_GET['status'] ?? 'all') . 
           "&sort=$field&order=$newOrder";
}

function getSortIcon($field, $currentField, $currentOrder) {
    if ($field !== $currentField) return '';
    return $currentOrder === 'ASC' ? '↑' : '↓';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Submissions | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Styling */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 25px;
            font-size: 24px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Controls Section */
        .controls-section {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #777;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        select, button {
            padding: 10px 15px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
        }

        select {
            border: 1px solid #ddd;
            background-color: white;
        }

        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
        }

        .submissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .submissions-table th {
            background-color: #3498db;
            color: white;
            text-align: left;
            padding: 12px 15px;
        }

        .submissions-table th a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .submissions-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .submissions-table tr:hover {
            background-color: #f5f5f5;
        }

        /* Status Badges */
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }

        .status.pending {
            background-color: #f39c12;
            color: white;
        }

        .status.complete {
            background-color: #27ae60;
            color: white;
        }

        .status.failed {
            background-color: #e74c3c;
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-buttons form {
            display: inline-block;
            margin: 0;
        }

        .action-buttons select {
            min-width: 120px;
            padding: 8px 10px;
        }

        .action-buttons button {
            padding: 8px 12px;
            border-radius: 3px;
            font-size: 13px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .submissions-table {
                font-size: 13px;
            }
            
            .submissions-table th, 
            .submissions-table td {
                padding: 8px 10px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="admin-container">
    <h1><i class="fas fa-envelope-open-text"></i> Contact Form Submissions</h1>

    <!-- Display messages -->
    <?php if (!empty($successMessage)): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i> <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <!-- Search and Filter Controls -->
    <div class="controls-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <form method="GET" action="manage_submissions.php">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone or message...">
            </form>
        </div>
        
        <div class="filter-group">
            <form method="GET" action="manage_submissions.php">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortField); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="complete" <?php echo $statusFilter === 'complete' ? 'selected' : ''; ?>>Complete</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </form>
            
            <button class="btn" onclick="window.location.href='manage_submissions.php'">
                <i class="fas fa-sync-alt"></i> Reset
            </button>
        </div>
    </div>

    <!-- Results Table -->
    <div class="table-container">
        <table class="submissions-table">
            <thead>
                <tr>
                    <th>
                        <a href="<?php echo getSortLink('id', $sortField, $sortOrder); ?>">
                            ID <?php echo getSortIcon('id', $sortField, $sortOrder); ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo getSortLink('name', $sortField, $sortOrder); ?>">
                            Name <?php echo getSortIcon('name', $sortField, $sortOrder); ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo getSortLink('phone_number', $sortField, $sortOrder); ?>">
                            Phone <?php echo getSortIcon('phone_number', $sortField, $sortOrder); ?>
                        </a>
                    </th>
                    <th>Message</th>
                    <th>
                        <a href="<?php echo getSortLink('status', $sortField, $sortOrder); ?>">
                            Status <?php echo getSortIcon('status', $sortField, $sortOrder); ?>
                        </a>
                    </th>
                    <th>
                        <a href="<?php echo getSortLink('created_at', $sortField, $sortOrder); ?>">
                            Date <?php echo getSortIcon('created_at', $sortField, $sortOrder); ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($submissionsResult->num_rows > 0): ?>
                    <?php while ($submission = $submissionsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['id']); ?></td>
                            <td><?php echo htmlspecialchars($submission['name']); ?></td>
                            <td><?php echo htmlspecialchars($submission['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars(substr($submission['message'], 0, 50)); ?><?php echo strlen($submission['message']) > 50 ? '...' : ''; ?></td>
                            <td>
                                <span class="status <?php echo strtolower($submission['status']); ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y H:i', strtotime($submission['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <form method="POST" action="manage_submissions.php">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortField); ?>">
                                        <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                                        <select name="new_status">
                                            <option value="pending" <?php echo $submission['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="complete" <?php echo $submission['status'] === 'complete' ? 'selected' : ''; ?>>Complete</option>
                                            <option value="failed" <?php echo $submission['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn"><i class="fas fa-save"></i> Update</button>
                                    </form>
                                    
                                    <form method="POST" action="manage_submissions.php" onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortField); ?>">
                                        <input type="hidden" name="order" value="<?php echo htmlspecialchars($sortOrder); ?>">
                                        <button type="submit" name="delete_submission" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px;">No submissions found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-submit search when typing
document.querySelector('.search-box input').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});
</script>
</body>
</html>

<?php include 'footer.php'; ?>
