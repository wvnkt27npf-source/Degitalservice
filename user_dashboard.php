<?php
session_start();
include './config.php';
include './header.php';
include './user_sidebar.php';

// Check User Access
$allowed_roles = ['user', 'client', 'customer'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// --- 1. FETCH USER STATISTICS ---

// Total Orders Placed
$total_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id")->fetch_row()[0];

// Active Orders (Pending or Processing)
$active_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND status IN ('Pending', 'In Progress', 'Processing')")->fetch_row()[0];

// Completed Orders
$completed_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id AND status = 'Completed'")->fetch_row()[0];

// Total Spent
$spent_query = $conn->query("SELECT SUM(price) FROM orders WHERE user_id = $user_id AND status != 'Cancelled'");
$total_spent = $spent_query->fetch_row()[0] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard | Digital Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        
        .dashboard-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Welcome Section */
        .welcome-banner {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .welcome-text h1 { margin: 0; font-size: 1.8rem; }
        .welcome-text p { margin: 5px 0 0; opacity: 0.8; }
        .action-btn {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }
        .action-btn:hover { background: #1d4ed8; transform: translateY(-2px); }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .stat-label { color: #64748b; font-size: 0.9rem; font-weight: 600; margin-bottom: 5px; }
        .stat-number { font-size: 2rem; font-weight: 800; color: #0f172a; }
        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 2.5rem;
            opacity: 0.1;
        }

        /* Recent Activity Section */
        .recent-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
        }
        .section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0; }
        .view-all { color: #2563eb; text-decoration: none; font-weight: 600; font-size: 0.9rem; }

        /* Table */
        .custom-table { width: 100%; border-collapse: collapse; }
        .custom-table th { text-align: left; color: #64748b; font-size: 0.85rem; padding: 12px; background: #f8fafc; border-radius: 6px; }
        .custom-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; }
        .custom-table tr:last-child td { border-bottom: none; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .bg-pending { background: #fff7ed; color: #c2410c; }
        .bg-completed { background: #f0fdf4; color: #15803d; }
        .bg-cancelled { background: #fef2f2; color: #b91c1c; }
        .bg-process { background: #eff6ff; color: #1d4ed8; }

    /* Mobile Fixes */
    @media (max-width: 768px) {
        .dashboard-wrapper { margin: 20px auto; }
        .welcome-banner { flex-direction: column; text-align: center; gap: 15px; padding: 20px; }
        .stats-grid { grid-template-columns: 1fr 1fr; gap: 15px; }
        .stat-card { padding: 15px; }
        .stat-number { font-size: 1.5rem; }
        .recent-section { overflow-x: auto; padding: 15px; }
        .custom-table { min-width: 650px; } /* Isse table kategi nahi */
    }

    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
        .welcome-text h1 { font-size: 1.4rem; }
    }

    </style>
</head>
<body>

<div class="dashboard-wrapper">
    
    <div class="welcome-banner">
        <div class="welcome-text">
            <h1>Welcome back, <?= htmlspecialchars($username) ?>! 👋</h1>
            <p>Track your orders, manage services, and stay updated.</p>
        </div>
        <a href="category.php" class="action-btn">+ New Service Order</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="border-bottom: 4px solid #2563eb;">
            <div class="stat-label">Active Orders</div>
            <div class="stat-number"><?= $active_orders ?></div>
            <div class="stat-icon">⏳</div>
        </div>
        <div class="stat-card" style="border-bottom: 4px solid #10b981;">
            <div class="stat-label">Completed</div>
            <div class="stat-number"><?= $completed_orders ?></div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-card" style="border-bottom: 4px solid #f59e0b;">
            <div class="stat-label">Total Spent</div>
            <div class="stat-number">₹<?= number_format($total_spent) ?></div>
            <div class="stat-icon">💳</div>
        </div>
        <div class="stat-card" style="border-bottom: 4px solid #64748b;">
            <div class="stat-label">Total Orders</div>
            <div class="stat-number"><?= $total_orders ?></div>
            <div class="stat-icon">📦</div>
        </div>
    </div>

    <div class="recent-section">
        <div class="section-header">
            <h2 class="section-title">Recent Activity</h2>
            <a href="user_orders.php" class="view-all">View All Orders &rarr;</a>
        </div>

        <table class="custom-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Service Name</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            
            <tbody>
                <?php
                // Last 5 orders fetch karein
                $recent_sql = "SELECT o.id, s.name as service_name, o.order_date, o.price, o.status, s.download_link 
                               FROM orders o 
                               JOIN services s ON o.service_id = s.id 
                               WHERE o.user_id = $user_id 
                               ORDER BY o.id DESC LIMIT 5";
                $recent_res = $conn->query($recent_sql);

                if ($recent_res->num_rows > 0):
                    while ($row = $recent_res->fetch_assoc()):
                        
                        $statusRaw = trim($row['status']); // Extra spaces hatayein
                        $badgeClass = 'bg-process'; // Default Blue (Processing, We Are Working, etc.)

                        // --- Status Color Mapping Logic ---
                        
                        // 1. Pending (Orange)
                        if (strcasecmp($statusRaw, 'Pending') == 0) {
                            $badgeClass = 'bg-pending';
                        } 
                        // 2. Completed & Document Submitted (Green)
                        elseif (strcasecmp($statusRaw, 'Completed') == 0 || $statusRaw == 'Document Submitted to Government') {
                            $badgeClass = 'bg-completed';
                        } 
                        // 3. Cancelled & Failed (Red) - Sabhi type ke Cancelled handle karega
                        elseif (stripos($statusRaw, 'Cancelled') !== false || strcasecmp($statusRaw, 'Failed') == 0) {
                            $badgeClass = 'bg-cancelled';
                        }
                        
                        // (Processing aur We Are Working default 'bg-process' (Blue) rahenge)
                ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['service_name']) ?></strong>
                    </td>
                    <td style="color: #64748b; font-size: 0.9rem;">
                        <?= date('d M Y', strtotime($row['order_date'])) ?>
                    </td>
                    <td style="font-weight: 600;">₹<?= number_format($row['price']) ?></td>
                    <td>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= htmlspecialchars($statusRaw) ?>
                        </span>
                    </td>
                    <td>
                        <a href="user_order_view.php?id=<?= $row['id'] ?>" style="color:#2563eb; font-weight:600; text-decoration:none; font-size: 0.9rem;">View Details</a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding: 30px; color: #64748b;">
                        <p style="margin-bottom: 10px;">You haven't placed any orders yet.</p>
                        <a href="category.php" class="action-btn" style="font-size: 0.9rem; padding: 8px 16px;">Explore Services</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
            
        </table>
    </div>

</div>

</body>
</html>
<?php include './footer.php'; ?>