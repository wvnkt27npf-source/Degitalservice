<?php
session_start();
include './config.php';
include './header.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if a specific user is selected
$selected_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$is_detail_view = false;

if ($selected_user_id > 0) {
    // --- DETAIL VIEW: Fetch orders for specific user ---
    $is_detail_view = true;
    
    // Using 'order_date'
    $stmt = $conn->prepare("
        SELECT 
            orders.id, 
            users.username AS customer_name, 
            orders.service_id, 
            services.name AS service_name,
            services.price, 
            orders.status, 
            services.download_link, 
            orders.cancel_reason, 
            orders.cancelled_by,
            orders.expiry_date,
            orders.order_date
        FROM 
            orders 
        JOIN 
            users ON orders.user_id = users.id
        JOIN 
            services ON orders.service_id = services.id
        WHERE 
            orders.user_id = ?
        ORDER BY orders.id DESC
    ");
    $stmt->bind_param("i", $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // --- SUMMARY VIEW: Fetch users sorted by Last Order ---
    $stmt = $conn->prepare("
        SELECT 
            users.id, 
            users.username AS customer_name, 
            users.email,
            COUNT(orders.id) AS total_orders,
            MAX(orders.id) as last_order_id
        FROM 
            users
        JOIN 
            orders ON users.id = orders.user_id
        GROUP BY 
            users.id
        ORDER BY 
            last_order_id DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    
    <style>
        /* General Reset & Fonts */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
        }

        .table-container {
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

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }

        .back-btn {
            background-color: #6b7280; 
            margin-bottom: 15px;
            padding: 8px 16px;
            font-size: 14px;
        }
        .back-btn:hover { background-color: #4b5563; }

        .view-btn { background-color: #0ea5e9; } /* Sky Blue */
        .view-btn:hover { background-color: #0284c7; }

        .edit-btn { background-color: #f59e0b; } /* Amber */
        .edit-btn:hover { background-color: #d97706; }

        .delete-btn { background-color: #ef4444; } /* Red */
        .delete-btn:hover { background-color: #dc2626; }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            min-width: 1000px; /* Prevent squashing */
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
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
            white-space: normal;
            max-width: 200px;
            line-height: 1.4;
        }

        .status.pending { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .status.completed { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status.cancelled-by-user { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .status.cancelled-by-us { background-color: #fecaca; color: #7f1d1d; border: 1px solid #f87171; }
        .status.cancelled-by-government { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .status.we-are-working { background-color: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .status.document-submitted-to-government { background-color: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }

        /* Responsive */
        @media (max-width: 768px) {
            .table-container { padding: 15px; overflow-x: auto; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
    <div class="table-container">
    
    <?php if ($is_detail_view): ?>
        <a href="admin_orders.php" class="btn back-btn">&larr; Back to User</a>
        <h2>Order Details for User</h2>
        
        <table id="ordersTable">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                    <th>Order ID</th>
                    <th>Order Date</th> 
                    <th>Customer Name</th>
                    <th>Service Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><input type="checkbox" class="row-select" value="<?php echo $row['id']; ?>"></td>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            
                            <td style="color: #4b5563;">
                                <?php 
                                if (!empty($row['order_date'])) {
                                    echo date('d M Y', strtotime($row['order_date'])); 
                                } else {
                                    echo '<span style="color:#9ca3af;">N/A</span>';
                                }
                                ?>
                            </td>

                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                            <td style="font-weight: 600; color: #059669;">₹<?php echo number_format($row['price'], 0); ?></td>
                            
                            <td>
                                <?php 
                                    $statusRaw = strtolower(trim($row['status']));
                                    $displayStatus = ucwords(str_replace('-', ' ', $statusRaw));
                                    
                                    // Normalize classes for CSS
                                    $cssClass = str_replace(' ', '-', $statusRaw);
                                    if (!empty($row['download_link'])) {
                                        $cssClass = 'completed';
                                        $displayStatus = 'Completed';
                                    }

                                    // Check cancellation reasons
                                    if ($statusRaw == 'cancelled by user' && !empty($row['cancel_reason'])) {
                                        $displayStatus = "Cancelled By User: " . htmlspecialchars($row['cancel_reason']);
                                    }
                                ?>
                                <span class="status <?php echo $cssClass; ?>">
                                    <?php echo $displayStatus; ?>
                                </span>
                            </td>

                            <td>
                                <?php 
                                if (!empty($row['expiry_date'])) {
                                    echo date('d M Y', strtotime($row['expiry_date'])); 
                                } else {
                                    echo '<span style="color:#9ca3af;">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:5px;">
                                    <a href="edit_order.php?id=<?php echo $row['id']; ?>" class="btn edit-btn">Edit</a>
                                    <a href="delete_order.php?id=<?php echo $row['id']; ?>&user_id=<?php echo $selected_user_id; ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <h2>User Wise Orders Summary</h2>
        <table id="usersTable">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Total Orders</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td style="font-weight: 500; color: #111827;"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td style="font-weight:bold; text-align:center; color:#4f46e5;"><?php echo $row['total_orders']; ?></td>
                            <td>
                                <a href="admin_orders.php?user_id=<?php echo $row['id']; ?>" class="btn view-btn">Show All Info</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('table').DataTable({
        "paging": true,
        "searching": true,
        "ordering": true,
        "order": [[0, "desc"]], 
        "columnDefs": [
            { "orderable": false, "targets": -1 } 
        ]
    });

    // Select All Logic
    $('#select-all').on('click', function() {
        $('.row-select').prop('checked', this.checked);
    });
});
</script>

</body>
</html>
<?php include 'footer.php'; ?>