<?php
session_start();
include './config.php';
include './header.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header("Location: index.php"); 
    exit; 
}

// --- 1. FETCH STATISTICS ---

// Total Users
$user_count = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Total Orders
$order_count = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

// Total Revenue (Assuming status NOT LIKE 'cancelled')
$rev_query = $conn->query("SELECT SUM(price) FROM orders WHERE status NOT LIKE '%cancelled%'");
$total_revenue = $rev_query->fetch_row()[0] ?? 0;

// Pending Orders
$pending_count = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetch_row()[0];

// --- 2. FETCH CHART DATA ---

// Graph 1: Orders per Month (Last 6 Months)
$months = [];
$order_data = [];
$month_query = "SELECT DATE_FORMAT(order_date, '%M') as month_name, COUNT(*) as count 
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) 
                GROUP BY DATE_FORMAT(order_date, '%Y-%m') 
                ORDER BY order_date ASC";
$m_res = $conn->query($month_query);
while($row = $m_res->fetch_assoc()){
    $months[] = $row['month_name'];
    $order_data[] = $row['count'];
}

// Graph 2: Order Status Distribution
$statuses = [];
$status_counts = [];
$s_res = $conn->query("SELECT status, COUNT(*) as c FROM orders GROUP BY status");
while($row = $s_res->fetch_assoc()){
    $statuses[] = ucwords(str_replace('-', ' ', $row['status']));
    $status_counts[] = $row['c'];
}

// Convert PHP Arrays to JSON for JavaScript
$json_months = json_encode($months);
$json_orders = json_encode($order_data);
$json_statuses = json_encode($statuses);
$json_status_counts = json_encode($status_counts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; }
        .dashboard-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 5px solid #2563eb;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-label { color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #1e293b; margin-top: 5px; }
        .stat-icon { float: right; font-size: 2rem; opacity: 0.2; }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .chart-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .chart-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 15px; }

        /* Recent Orders Table */
        .table-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; color: #64748b; font-size: 0.85rem; padding: 10px; border-bottom: 2px solid #f1f5f9; }
        td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; }
        .badge {
            padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600;
        }
        .badge-pending { background: #fff7ed; color: #c2410c; }
        .badge-success { background: #f0fdf4; color: #15803d; }
        .badge-danger { background: #fef2f2; color: #b91c1c; }

        @media (max-width: 768px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <main class="admin-main">
<div class="dashboard-container">
    <h2 style="margin-bottom: 20px; color: #1e293b;">📊 Dashboard Overview</h2>

    <div class="stats-grid">
        <div class="stat-card" style="border-color: #3b82f6;">
            <div class="stat-icon">👥</div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($user_count) ?></div>
        </div>
        <div class="stat-card" style="border-color: #10b981;">
            <div class="stat-icon">💰</div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">₹<?= number_format($total_revenue) ?></div>
        </div>
        <div class="stat-card" style="border-color: #f59e0b;">
            <div class="stat-icon">📦</div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= number_format($order_count) ?></div>
        </div>
        <div class="stat-card" style="border-color: #ef4444;">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value"><?= number_format($pending_count) ?></div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-box">
            <div class="chart-title">📈 Monthly Orders Trend</div>
            <canvas id="ordersChart"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title">⚡ Order Status</div>
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <div class="table-box">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="chart-title">🛒 Recent Orders</div>
            <a href="admin_orders.php" style="font-size:0.9rem; text-decoration:none; color:#2563eb;">View All &rarr;</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recent = $conn->query("SELECT o.id, u.username, s.name as service, o.price, o.order_date, o.status 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.id 
                                        JOIN services s ON o.service_id = s.id 
                                        ORDER BY o.id DESC LIMIT 5");
                if($recent->num_rows > 0):
                    while($row = $recent->fetch_assoc()):
                        $status = strtolower($row['status']);
                        $badgeClass = 'badge-pending';
                        if(strpos($status, 'completed') !== false) $badgeClass = 'badge-success';
                        elseif(strpos($status, 'cancelled') !== false) $badgeClass = 'badge-danger';
                ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                    <td><?= htmlspecialchars($row['service']) ?></td>
                    <td>₹<?= number_format($row['price']) ?></td>
                    <td><?= date('d M', strtotime($row['order_date'])) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // 1. Bar Chart Config
    const ctx1 = document.getElementById('ordersChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?= $json_months ?>,
            datasets: [{
                label: 'Orders',
                data: <?= $json_orders ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. Doughnut Chart Config
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?= $json_statuses ?>,
            datasets: [{
                data: <?= $json_status_counts ?>,
                backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
</script>

</body>
</html>
<?php include 'footer.php'; ?>