<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

include './config.php';
include 'header.php';

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // Prepare and execute delete query
    $stmt = $conn->prepare("DELETE FROM custom_requests WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Request deleted successfully!'); window.location.href = 'CustomRequestadmin.php';</script>";
    } else {
        echo "<script>alert('Failed to delete request!');</script>";
    }
}

// Fetch all custom requests
$requests = $conn->query("SELECT cr.id, cr.user_id, cr.request_description, cr.status, cr.admin_message, cr.response_link, cr.response_file, u.username 
                          FROM custom_requests cr 
                          JOIN users u ON cr.user_id = u.id 
                          ORDER BY cr.created_at DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Custom Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Reset & Fonts */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            color: #111827;
        }

        /* Table Styles */
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            min-width: 800px; /* Ensures table doesn't squash on small screens */
        }

        th, td {
            padding: 16px;
            text-align: left;
            border: 1px solid #d1d5db; /* Full Border */
            font-size: 14px;
            vertical-align: top;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        tr:hover td {
            background-color: #f9fafb;
        }

        /* Links inside table */
        table td a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        table td a:hover {
            text-decoration: underline;
        }

        /* Action Buttons */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }

        .update-btn {
            background-color: #0ea5e9; /* Sky Blue */
        }
        .update-btn:hover {
            background-color: #0284c7;
        }

        .delete-btn {
            background-color: #ef4444; /* Red */
        }
        .delete-btn:hover {
            background-color: #dc2626;
        }

        /* Status Badges (Optional styling for status text) */
        .status-text {
            font-weight: 600;
            color: #4b5563;
        }

        @media (max-width: 768px) {
            .container { padding: 15px; margin: 15px auto; }
            th, td { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <h2>Manage Custom Requests</h2>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 120px;">Username</th>
                    <th>Description</th>
                    <th style="width: 100px;">Status</th>
                    <th>Admin Response</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $requests->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= nl2br(htmlspecialchars($row['request_description'])) ?></td>
                    <td><span class="status-text"><?= htmlspecialchars($row['status']) ?></span></td>
                    <td>
                        <?php if ($row['admin_message']): ?>
                            <div style="margin-bottom:5px;"><strong>Msg:</strong> <?= htmlspecialchars($row['admin_message']) ?></div>
                        <?php endif; ?>
                        
                        <?php if ($row['response_link']): ?>
                            <div style="margin-bottom:5px;"><strong>Link:</strong> <a href="<?= htmlspecialchars($row['response_link']) ?>" target="_blank">Open Link</a></div>
                        <?php endif; ?>
                        
                        <?php if ($row['response_file']): ?>
                            <div><strong>File:</strong> <a href="download.php?file=<?= urlencode($row['response_file']) ?>" target="_blank">Download</a></div>
                        <?php endif; ?>
                        
                        <?php if (!$row['admin_message'] && !$row['response_link'] && !$row['response_file']): ?>
                            <span style="color: #9ca3af;">- No response yet -</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="update_request.php?id=<?= $row['id'] ?>" class="btn update-btn">Update</a>
                        <a href="?delete_id=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this request?')">Delete</a>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>