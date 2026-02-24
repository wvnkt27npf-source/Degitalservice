<?php
session_start();
include './config.php';
include 'header.php'; // Admin ka header include karein

// Sirf Admin hi yeh page dekh sakta hai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// === YEH SQL QUERY UPDATE HO GAYI HAI ===
// Hum ab 'users' table ko JOIN kar rahe hain taaki 'live_user_phone' mil sake
$requests_stmt = $conn->prepare(
    "SELECT 
        req.*, 
        u.phone AS live_user_phone 
     FROM role_change_requests req
     LEFT JOIN users u ON req.user_id = u.id
     ORDER BY FIELD(req.status, 'Pending') DESC, req.created_at DESC"
);
// ======================================

$requests_stmt->execute();
$requests_result = $requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Role Change Requests</title>
    <link rel="stylesheet" href="admin_orders.css"> 
    <style>
        .container {
            max-width: 1200px; /* Page ko thoda chauda karein */
        }
        .orders-table th, .orders-table td {
            min-width: 120px;
            padding: 12px;
        }
        .reason-message {
            max-width: 250px;
            min-width: 200px;
            white-space: pre-wrap; /* Message ko line breaks ke saath dikhayein */
            word-wrap: break-word;
        }
        /* Status ke liye colors */
        .status-Pending { color: #e67e22; font-weight: bold; }
        .status-Approved { color: #2ecc71; font-weight: bold; }
        .status-Rejected { color: #e74c3c; font-weight: bold; }
        
        /* Action form jo table ke andar hai */
        .action-form {
            display: flex;
            flex-direction: column; /* Form elements ko upar-neeche rakhein */
            gap: 10px;
            align-items: flex-start;
            min-width: 250px;
        }
        .action-form textarea {
            width: 100%;
            height: 50px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 0.9rem;
        }
        .action-form .buttons-row {
            display: flex;
            gap: 10px;
        }
        .action-form button {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        .action-form button.approve {
            background: #28a745;
        }
        .action-form button.approve:hover {
            background: #218838;
        }
        .action-form button.reject {
            background: #e74c3c;
        }
        .action-form button.reject:hover {
            background: #c0392b;
        }
        
        /* Success message */
        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
    <div class="container">
        <h2>Role Change Requests Management</h2>
        
        <?php if ($message): ?>
            <div class="message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <table class="orders-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Phone (Live)</th> <th>Current Role</th>
                    <th>Requested Role</th>
                    <th>Reason (Message)</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Action / Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests_result->num_rows > 0): ?>
                    <?php while ($row = $requests_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            
                            <td><?= htmlspecialchars($row['live_user_phone'] ?? 'N/A') ?></td>
                            <td style="text-transform: capitalize;"><?= htmlspecialchars($row['current_role']) ?></td>
                            <td style="text-transform: capitalize; font-weight: bold;"><?= htmlspecialchars($row['requested_role']) ?></td>
                            <td class="reason-message"><?= nl2br(htmlspecialchars($row['reason_message'])) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                            <td class="status-<?= htmlspecialchars($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'Pending'): ?>
                                    <form class="action-form" action="admin_update_role_status.php" method="POST">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <input type="hidden" name="requested_role" value="<?= $row['requested_role'] ?>">
                                        
                                        <textarea name="admin_notes" placeholder="Jawab/Note likhein (Optional)"></textarea>
                                        
                                        <div class="buttons-row">
                                            <button type="submit" name="action" value="Approve" class="approve" title="Approve Request">Approve</button>
                                            <button type="submit" name="action" value="Reject" class="reject" title="Reject Request">Reject</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <?php if(!empty($row['admin_notes'])): ?>
                                        <p><small><strong>Aapka Jawab:</strong> <?= htmlspecialchars($row['admin_notes']) ?></small></p>
                                    <?php else: ?>
                                        - (Action Taken) -
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Koi nayi role change request nahi hai.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php include 'footer.php'; // Admin ka footer include karein ?>