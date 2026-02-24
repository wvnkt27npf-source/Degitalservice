<?php
// degitalservice/user_order_view.php
session_start();
include './config.php'; // Database connection $conn

// Auth check
$loggedInUserRoles = ['user', 'client', 'customer']; 
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php");
    exit();
}

include 'header.php';

if (!isset($_GET['id'])) {
    echo "<div style='padding:50px; text-align:center;'>Invalid Request. <a href='user_orders.php'>Go Back</a></div>";
    include 'footer.php';
    exit;
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch order with feedback and service details
$stmt = $conn->prepare("SELECT orders.*, services.name AS service_name, services.id AS service_id, services.description AS service_desc, 
    services.download_link AS service_link, feedbacks.id AS feedback_id 
    FROM orders 
    JOIN services ON orders.service_id = services.id 
    LEFT JOIN feedbacks ON orders.id = feedbacks.order_id 
    WHERE orders.id = ? AND orders.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<div style='padding:50px; text-align:center;'>Order not found. <a href='user_orders.php'>Go Back</a></div>";
    include 'footer.php';
    exit;
}

// Status & Logic
$finalStatus = $order['status'] ?: 'Pending';
if ($finalStatus === 'Pending' && strcasecmp($order['payment_status'], 'failed') === 0) $finalStatus = 'Failed';

$isCancelled = (stripos($finalStatus, 'Cancelled') !== false || stripos($finalStatus, 'Failed') !== false);
$isCompleted = (stripos($finalStatus, 'Completed') !== false);

// Payment Method name fetch karein (Agar payments table mein record hai)
$pMethod = ($order['price'] == 0) ? 'Free Service' : 'Online Payment';
$payQuery = $conn->prepare("SELECT payment_method FROM payments WHERE order_id = ? LIMIT 1");
$payQuery->bind_param("i", $order_id);
$payQuery->execute();
$payRes = $payQuery->get_result()->fetch_assoc();
if ($payRes) { $pMethod = $payRes['payment_method']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details #<?= $order['id'] ?></title>
    <style>
        body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .view-wrapper { max-width: 900px; margin: 0 auto; padding: 20px; }
        .sticky-nav { position: sticky; top: 0; background: #f1f5f9; padding: 15px 0; z-index: 100; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; }
        .back-btn { background: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; color: #1e293b; font-weight: 700; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .main-card { background: white; border-radius: 20px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; }
        .bg-pending { background: #fff7ed; color: #c2410c; }
        .bg-success { background: #f0fdf4; color: #15803d; }
        .bg-danger { background: #fef2f2; color: #b91c1c; }
        .bg-info { background: #eff6ff; color: #1d4ed8; }
        .grid-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px; }
        .data-row { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .btn-group { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
        .btn { padding: 14px; border-radius: 12px; font-weight: 700; text-decoration: none; text-align: center; cursor: pointer; border: none; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-success { background: #10b981; color: white; }
        @media (max-width: 768px) { .grid-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="view-wrapper">
    <div class="sticky-nav"><a href="user_orders.php" class="back-btn">← Back to My Orders</a></div>

    <div class="main-card">
        <div style="display:flex; justify-content:space-between; align-items:start; border-bottom:1px solid #f1f5f9; padding-bottom:20px;">
            <div>
                <h1 style="margin:0; font-size:1.6rem; color:#0f172a;">Order #<?= $order['id'] ?></h1>
                <p style="color:#64748b; margin-top:5px;">Placed: <?= date("d M Y, h:i A", strtotime($order['order_date'])) ?></p>
            </div>
            <?php 
                $stClass = 'bg-pending';
                if ($isCompleted) $stClass = 'bg-success';
                elseif ($isCancelled) $stClass = 'bg-danger';
                elseif ($finalStatus != 'Pending') $stClass = 'bg-info';
            ?>
            <span class="badge <?= $stClass ?>"><?= $finalStatus ?></span>
        </div>

        <div class="grid-layout">
            <div>
                <h3>📜 Service Details</h3>
                <div class="data-row"><label>Service:</label><span><?= htmlspecialchars($order['service_name']) ?></span></div>
                <div class="data-row"><label>Expiry:</label><span><?= $order['expiry_date'] ? date("d M Y", strtotime($order['expiry_date'])) : 'Lifetime' ?></span></div>
                <div style="background:#f8fafc; padding:15px; border-radius:12px; margin-top:10px;">
                    <label style="font-size:0.8rem; color:#64748b; font-weight:700;">DESCRIPTION:</label>
                    <p style="font-size:0.9rem; line-height:1.6; color:#475569; margin:5px 0 0;"><?= nl2br(htmlspecialchars($order['service_desc'])) ?></p>
                </div>
            </div>

            <div>
                <h3>💳 Payment Summary</h3>
                <div class="data-row"><label>Amount:</label><span>₹<?= number_format($order['price'], 2) ?></span></div>
                <div class="data-row"><label>Method:</label><span><?= $pMethod ?></span></div>
                <div class="data-row">
                    <label>Status:</label>
                    <span style="color:<?= ($order['payment_received'] || $order['price'] == 0) ? '#10b981' : ($isCancelled ? '#ef4444' : '#f59e0b') ?>;">
                        <?= ($order['payment_received'] || $order['price'] == 0) ? 'SUCCESS' : ($isCancelled ? 'CANCELLED' : 'PENDING') ?>
                    </span>
                </div>

                <div class="btn-group">
                    <?php if ($finalStatus === 'Pending' && !$order['payment_received'] && $order['price'] > 0): ?>
                        <a href="payment.php?order_id=<?= $order['id'] ?>&price=<?= $order['price'] ?>" class="btn btn-primary">Pay Now ₹<?= $order['price'] ?></a>
                    <?php endif; ?>

                    <?php if ($isCompleted): ?>
                        <?php 
                        // FIX: Changed 'order_link' to 'download_link' based on SQL
                        $dLink = $order['download_link'] ?: ($order['service_link'] ?: ($order['file'] ? "download.php?file=".urlencode($order['file']) : ''));
                        if ($dLink): ?>
                            <a href="<?= $dLink ?>" target="_blank" class="btn btn-success">📥 Download Document</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php include 'footer.php'; ?>