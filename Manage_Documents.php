<?php
session_start();
include './config.php';

// Ensure the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// --- BULK DOWNLOAD LOGIC (Must be before header.php) ---
if (isset($_GET['bulk_download']) && isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    
    // Fetch user's username for the zip filename
    $userStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userStmt->bind_result($zipUsername);
    $userStmt->fetch();
    $userStmt->close();
    
    // Fetch all approved/uploaded documents
    $stmt = $conn->prepare("SELECT file_path, file_name FROM user_documents WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filesToZip = [];
    while ($row = $result->fetch_assoc()) {
        if (file_exists($row['file_path'])) {
            $filesToZip[] = [
                'path' => $row['file_path'],
                'name' => $row['file_name']
            ];
        }
    }
    $stmt->close();

    if (count($filesToZip) > 0) {
        $zip = new ZipArchive();
        $zipFileName = "Documents_" . ($zipUsername ? $zipUsername : $userId) . "_" . date("Ymd") . ".zip";
        $zipFilePath = sys_get_temp_dir() . "/" . $zipFileName;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($filesToZip as $file) {
                // Add file to zip with its original name
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();

            // Serve the file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            header('Pragma: no-cache');
            readfile($zipFilePath);

            // Cleanup
            unlink($zipFilePath);
            exit();
        } else {
            echo "<script>alert('Failed to create ZIP file.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('No files found for this user.'); window.history.back();</script>";
        exit();
    }
}

include './header.php';

$message = '';
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Handle document approval, rejection, and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $docId = $_POST['doc_id'];
        $updateStmt = $conn->prepare("UPDATE user_documents SET status = 'Approved' WHERE id = ?");
        $updateStmt->bind_param("i", $docId);
        if ($updateStmt->execute()) $message = "Document approved successfully.";
        $updateStmt->close();
    } elseif (isset($_POST['reject'])) {
        $docId = $_POST['doc_id'];
        $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id = ?");
        $stmt->bind_param("i", $docId);
        $stmt->execute();
        $stmt->bind_result($filePath);
        $stmt->fetch();
        $stmt->close();
        if (file_exists($filePath)) unlink($filePath);
        $updateStmt = $conn->prepare("UPDATE user_documents SET status = 'Rejected' WHERE id = ?");
        $updateStmt->bind_param("i", $docId);
        if ($updateStmt->execute()) $message = "Document rejected and deleted successfully.";
        $updateStmt->close();
    } elseif (isset($_POST['delete'])) {
        $docId = $_POST['doc_id'];
        $stmt = $conn->prepare("SELECT file_path FROM user_documents WHERE id = ?");
        $stmt->bind_param("i", $docId);
        $stmt->execute();
        $stmt->bind_result($filePath);
        $stmt->fetch();
        $stmt->close();
        if (file_exists($filePath)) unlink($filePath);
        $deleteStmt = $conn->prepare("DELETE FROM user_documents WHERE id = ?");
        $deleteStmt->bind_param("i", $docId);
        if ($deleteStmt->execute()) $message = "Document deleted successfully.";
        $deleteStmt->close();
    }
}

// FETCH DATA BASED ON VIEW MODE
$is_detail_view = false;

if ($selected_user_id > 0) {
    $is_detail_view = true;
    $stmt = $conn->prepare("SELECT ud.id, ud.user_id, ud.file_name, ud.file_path, ud.status, u.username 
                            FROM user_documents ud 
                            JOIN users u ON ud.user_id = u.id 
                            WHERE ud.user_id = ?");
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $docResult = $stmt->get_result();
    $documents = [];
    while ($row = $docResult->fetch_assoc()) {
        $documents[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT u.id, u.username, u.email, COUNT(ud.id) as total_docs 
                            FROM users u 
                            JOIN user_documents ud ON u.id = ud.user_id 
                            GROUP BY u.id 
                            ORDER BY total_docs DESC");
    $stmt->execute();
    $userResult = $stmt->get_result();
    $users_list = [];
    while ($row = $userResult->fetch_assoc()) {
        $users_list[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Documents</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    
    <style>
        /* General Reset & Fonts */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; line-height: 1.6; }
        .container { max-width: 1200px; width: 95%; margin: 30px auto; padding: 25px; background-color: #ffffff; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); border-radius: 12px; border: 1px solid #e5e7eb; }
        h2 { margin: 0; font-size: 24px; font-weight: 600; color: #111827; }
        
        .btn { padding: 8px 16px; cursor: pointer; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; transition: all 0.2s ease; text-decoration: none; display: inline-block; color: white; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        
        .approve-btn { background-color: #10b981; } .approve-btn:hover { background-color: #059669; }
        .reject-btn { background-color: #ef4444; } .reject-btn:hover { background-color: #dc2626; }
        .delete-btn { background-color: #b91c1c; } .delete-btn:hover { background-color: #991b1b; }
        .download-btn { background-color: #3b82f6; } .download-btn:hover { background-color: #2563eb; }
        .view-btn { background-color: #6366f1; } .view-btn:hover { background-color: #4f46e5; }
        .required-docs-btn { background-color: #f59e0b; } .required-docs-btn:hover { background-color: #d97706; }
        .back-btn { background-color: #6b7280; margin-right: 15px; } .back-btn:hover { background-color: #4b5563; }
        .show-info-btn { background-color: #0ea5e9; } .show-info-btn:hover { background-color: #0284c7; }
        
        /* New Bulk Download Button */
        .bulk-download-btn { background-color: #2563eb; padding: 10px 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .bulk-download-btn:hover { background-color: #1d4ed8; }

        .message { margin: 20px 0; padding: 15px; background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 8px; }
        .table-container { width: 100%; overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background-color: white; min-width: 800px; }
        th, td { padding: 16px; text-align: center; border: 1px solid #d1d5db; font-size: 14px; }
        th { background-color: #f9fafb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; }
        tr:hover td { background-color: #f9fafb; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
        .status-approved { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-pending { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    </style>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    
    <?php if ($message): ?>
        <div class="message"><?= $message; ?></div>
    <?php endif; ?>

    <?php if ($is_detail_view): ?>
        <div class="header-actions">
            <div style="display:flex; align-items:center;">
                <a href="Manage_Documents.php" class="btn back-btn">&larr; Back</a>
                <h2>User Documents</h2>
            </div>
            <a href="Manage_Documents.php?bulk_download=true&user_id=<?= $selected_user_id; ?>" class="btn bulk-download-btn">
                ⬇ Download All (ZIP)
            </a>
        </div>

        <div class="table-container">
            <table id="docsTable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Document Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($documents)): ?>
                        <tr><td colspan="4">No documents uploaded by this user.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $document): ?>
                            <tr>
                                <td><?= htmlspecialchars($document['username']); ?></td>
                                <td><?= htmlspecialchars($document['file_name']); ?></td>
                                <td>
                                    <?php
                                        $statusClass = 'status-pending';
                                        if($document['status'] == 'Approved') $statusClass = 'status-approved';
                                        if($document['status'] == 'Rejected') $statusClass = 'status-rejected';
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($document['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($document['status'] !== 'Rejected'): ?>
                                        <a href="view_document.php?doc_id=<?= $document['id']; ?>&user_id=<?= $selected_user_id; ?>" class="btn view-btn">View</a>
                                        <a href="<?= htmlspecialchars($document['file_path']); ?>" class="btn download-btn" download>Download</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($document['status'] !== 'Approved'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="doc_id" value="<?= $document['id']; ?>">
                                            <button type="submit" name="approve" class="btn approve-btn">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="doc_id" value="<?= $document['id']; ?>">
                                            <button type="submit" name="reject" class="btn reject-btn" onclick="return confirm('Are you sure you want to reject this document?')">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="doc_id" value="<?= $document['id']; ?>">
                                        <button type="submit" name="delete" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this document?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <div class="header-actions">
            <h2>Manage Documents (User Wise)</h2>
            <a href="service_documents.php" class="btn required-docs-btn">⚙ Required Docs Config</a>
        </div>

        <div class="table-container">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Total Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users_list)): ?>
                        <tr><td colspan="5">No users have uploaded documents yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users_list as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']); ?></td>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td style="font-weight:bold;"><?= htmlspecialchars($user['total_docs']); ?></td>
                                <td>
                                    <a href="Manage_Documents.php?user_id=<?= $user['id']; ?>" class="btn show-info-btn">Show All Info</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<script>
$(document).ready(function() {
    $('table').DataTable({ "paging": true, "searching": true, "ordering": true, "columnDefs": [ { "orderable": false, "targets": -1 } ] });
});
</script>

</body>
</html>