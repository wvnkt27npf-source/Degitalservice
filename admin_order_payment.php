<?php
session_start();
include './config.php';
include './header.php';

// Ensure the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch Payment Data with User and Order Details
// We use LEFT JOIN so even if an order/user is deleted, payment record is still visible
$query = "
    SELECT 
        payments.id AS payment_id,
        payments.payment_amount,
        payments.payment_method,
        payments.payment_status,
        payments.utr_number,
        payments.created_at AS payment_date,
        users.username,
        users.email,
        users.phone,
        orders.id AS order_id,
        services.name AS service_name
    FROM 
        payments
    LEFT JOIN 
        users ON payments.user_id = users.id
    LEFT JOIN 
        orders ON payments.order_id = orders.id
    LEFT JOIN 
        services ON orders.service_id = services.id
    ORDER BY 
        payments.created_at DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Payment Transactions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

    <style>
        /* Reusing your admin styles for consistency */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        
        .table-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            overflow-x: auto;
        }

        h2 {
            margin-bottom: 20px;
            color: #111827;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            color: #1f2937;
            vertical-align: middle;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        /* Status Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-completed, .status-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending, .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-failed, .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .utr-code {
            font-family: monospace;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            color: #4b5563;
        }

        .amount {
            font-weight: 600;
            color: #111827;
        }
        
        .user-info small {
            display: block;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="table-container">
            <h2>Transaction History</h2>
            
            <table id="paymentsTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Details</th>
                        <th>Order Info</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>UTR / Transaction ID</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['payment_id']); ?></td>
                                
                                <td class="user-info">
                                    <strong><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></strong>
                                    <small><?php echo htmlspecialchars($row['email']); ?></small>
                                    <small><?php echo htmlspecialchars($row['phone']); ?></small>
                                </td>

                                <td>
                                    <?php if($row['order_id']): ?>
                                        <a href="admin_orders.php?user_id=<?php echo $row['payment_id']; // Optionally link to order ?>" style="text-decoration:none; color:#4f46e5;">
                                            <strong>Order #<?php echo $row['order_id']; ?></strong>
                                        </a>
                                        <br>
                                        <small><?php echo htmlspecialchars($row['service_name'] ?? 'Service Deleted'); ?></small>
                                    <?php else: ?>
                                        <span style="color:#9ca3af;">No Order Linked</span>
                                    <?php endif; ?>
                                </td>

                                <td class="amount">₹<?php echo number_format($row['payment_amount'], 2); ?></td>
                                
                                <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                                
                                <td>
                                    <?php if (!empty($row['utr_number'])): ?>
                                        <span class="utr-code"><?php echo htmlspecialchars($row['utr_number']); ?></span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                        $status = strtolower($row['payment_status']);
                                        $badgeClass = '';
                                        
                                        if (in_array($status, ['completed', 'success', 'paid'])) {
                                            $badgeClass = 'status-completed';
                                        } elseif (in_array($status, ['pending', 'processing'])) {
                                            $badgeClass = 'status-pending';
                                        } else {
                                            $badgeClass = 'status-failed';
                                        }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php echo date('d M Y, h:i A', strtotime($row['payment_date'])); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('#paymentsTable').DataTable({
            "order": [[ 0, "desc" ]], // Default sort by ID descending
            "pageLength": 25,
            "language": {
                "emptyTable": "No payment records found"
            }
        });
    });
</script>

</body>
</html>
<?php include 'footer.php'; ?>