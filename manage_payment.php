<?php
session_start();
include './config.php';
include 'header.php';

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php");
    exit(); 
}

$msg = "";

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['payment_id'])) {
    $new_status = $_POST['new_status'];
    $payment_id = intval($_POST['payment_id']);
    $stmt = $conn->prepare("UPDATE payments SET payment_status = ?, updated_at = NOW() WHERE payment_id = ?");
    $stmt->bind_param("si", $new_status, $payment_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Payment status updated successfully!";
}

// Search/filter
$where = [];
$params = [];
$types = "";

if (!empty($_GET['user'])) {
    $where[] = "users.username LIKE ?";
    $params[] = "%" . $_GET['user'] . "%";
    $types .= "s";
}
if (!empty($_GET['order_id'])) {
    $where[] = "payments.order_id = ?";
    $params[] = $_GET['order_id'];
    $types .= "i";
}
if (!empty($_GET['status'])) {
    $where[] = "payments.payment_status = ?";
    $params[] = $_GET['status'];
    $types .= "s";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch payments
$sql = "SELECT payments.*, users.username, orders.service_id, services.name AS service_name
        FROM payments
        LEFT JOIN users ON payments.user_id = users.id
        LEFT JOIN orders ON payments.order_id = orders.id
        LEFT JOIN services ON orders.service_id = services.id
        $where_sql
        ORDER BY payments.payment_date DESC
        LIMIT 100";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments</title>
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
            max-width: 1400px;
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
            margin-bottom: 25px;
            font-size: 28px;
            font-weight: 600;
            color: #111827;
        }

        /* Message Box */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            text-align: center;
        }

        /* Search Form */
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 25px;
            align-items: center;
        }

        .search-form input, .search-form select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            min-width: 150px;
            background-color: white;
        }

        .search-form button {
            padding: 10px 20px;
            background-color: #4f46e5; /* Indigo */
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        .search-form button:hover { background-color: #4338ca; }

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
            min-width: 1000px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #d1d5db; /* Full Border */
            font-size: 13px;
            vertical-align: middle;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        tr:hover td { background-color: #f9fafb; }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .status-processing { background-color: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .status-completed { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-failed { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status-refunded { background-color: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }

        /* Action Form in Table */
        .action-form { display: flex; align-items: center; gap: 5px; }
        
        .action-form select {
            padding: 6px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 12px;
            background-color: white;
        }

        .update-btn {
            padding: 6px 10px;
            background-color: #10b981; /* Green */
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 500;
        }
        .update-btn:hover { background-color: #059669; }

        /* Links */
        a.view-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        a.view-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .container { padding: 15px; margin: 15px auto; }
            .search-form { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Payments</h2>
    
    <?php if (!empty($msg)): ?>
        <div class="message"><?= htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <form class="search-form" method="get">
        <input type="text" name="user" placeholder="Search by Username" value="<?= htmlspecialchars($_GET['user'] ?? '') ?>">
        <input type="text" name="order_id" placeholder="Search by Order ID" value="<?= htmlspecialchars($_GET['order_id'] ?? '') ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="Pending" <?= (($_GET['status'] ?? '') == 'Pending') ? 'selected' : '' ?>>Pending</option>
            <option value="Processing" <?= (($_GET['status'] ?? '') == 'Processing') ? 'selected' : '' ?>>Processing</option>
            <option value="Completed" <?= (($_GET['status'] ?? '') == 'Completed') ? 'selected' : '' ?>>Completed</option>
            <option value="Failed" <?= (($_GET['status'] ?? '') == 'Failed') ? 'selected' : '' ?>>Failed</option>
            <option value="Refunded" <?= (($_GET['status'] ?? '') == 'Refunded') ? 'selected' : '' ?>>Refunded</option>
        </select>
        <button type="submit">Search</button>
    </form>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Order ID</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>UTR No.</th>
                    <th>Receipt</th>
                    <th>Date</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['payment_id'] ?></td>
                    <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $row['order_id'] ?></td>
                    <td><?= htmlspecialchars($row['service_name']) ?></td>
                    <td style="font-weight: 600; color: #059669;">₹<?= number_format($row['payment_amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td>
                        <?php 
                            $statusClass = 'status-' . strtolower($row['payment_status']);
                        ?>
                        <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['payment_status']) ?></span>
                    </td>
                    <td style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($row['utr_number'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($row['payment_receipt'])): ?>
                            <a href="<?= htmlspecialchars($row['payment_receipt']) ?>" target="_blank" class="view-link">View File</a>
                        <?php else: ?>
                            <span style="color:#9ca3af;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: #6b7280; font-size: 12px;"><?= date('d M Y, h:i A', strtotime($row['payment_date'])) ?></td>
                    <td>
                        <form method="post" class="action-form">
                            <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                            <select name="new_status">
                                <option value="Pending" <?= $row['payment_status']=='Pending'?'selected':'' ?>>Pending</option>
                                <option value="Processing" <?= $row['payment_status']=='Processing'?'selected':'' ?>>Processing</option>
                                <option value="Completed" <?= $row['payment_status']=='Completed'?'selected':'' ?>>Completed</option>
                                <option value="Failed" <?= $row['payment_status']=='Failed'?'selected':'' ?>>Failed</option>
                                <option value="Refunded" <?= $row['payment_status']=='Refunded'?'selected':'' ?>>Refunded</option>
                            </select>
                            <button type="submit" name="update_status" class="update-btn">Save</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>