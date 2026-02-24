<?php
session_start();
include './config.php';

// Authentication Check
$loggedInUserRoles = ['user', 'client', 'customer']; 
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php");
    exit();
}

include 'header.php';

// Fetch user orders
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT 
    orders.id, 
    services.id AS service_id,
    services.name AS service_name,
    orders.price AS price, 
    orders.status, 
    orders.payment_status,
    orders.payment_received,
    orders.file,
    services.download_link AS service_link,
    orders.download_link AS order_link, 
    orders.cancel_reason,
    orders.order_date,
    feedbacks.feedback AS order_feedback,
    feedbacks.feedback_score AS order_feedback_score,
    orders.expiry_date
FROM orders 
JOIN services ON orders.service_id = services.id
LEFT JOIN feedbacks ON orders.id = feedbacks.order_id AND feedbacks.user_id = ? 
WHERE orders.user_id = ? 
ORDER BY orders.id DESC"); 

$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - Digital Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; color: #1e293b; }

        .container {
            max-width: 1200px; width: 95%; margin: 40px auto; padding: 30px;
            background-color: #ffffff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 16px;
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        h2 { font-size: 1.8rem; font-weight: 700; color: #0f172a; }

        .btn-nav {
            padding: 10px 20px; border-radius: 8px; font-weight: 500; text-decoration: none;
            transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem;
        }
        .btn-back { background-color: #f1f5f9; color: #475569; }
        .btn-back:hover { background-color: #e2e8f0; color: #1e293b; }
        .btn-renew { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }

        .table-container { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        th {
            background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase;
            font-size: 0.75rem; padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0;
        }
        td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; vertical-align: middle; }
        tr:hover td { background-color: #f8fafc; }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
            text-transform: uppercase; display: inline-block;
        }
        .status-pending { background: #fff7ed; color: #c2410c; }
        .status-completed { background: #f0fdf4; color: #15803d; }
        .status-working { background: #eff6ff; color: #1d4ed8; }
        .status-cancelled { background: #fef2f2; color: #b91c1c; }

        /* Action Buttons */
        .btn-action {
            padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600;
            text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;
            margin-right: 5px; margin-bottom: 5px;
        }
        .btn-download { background: #10b981; color: white; }
        .btn-download:hover { background: #059669; }

        .btn-upload { background: #3b82f6; color: white; }
        .btn-upload:hover { background: #2563eb; }

        .btn-feedback { background: #8b5cf6; color: white; }
        .btn-feedback:hover { background: #7c3aed; }

        .btn-cancel { background: white; border: 1px solid #ef4444; color: #ef4444; }
        .btn-cancel:hover { background: #fef2f2; }

        /* Popups */
        .popup-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center;
            z-index: 999; backdrop-filter: blur(4px);
        }
        .popup { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .popup textarea, .popup select { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 15px; }
        .popup-btns { display: flex; justify-content: flex-end; gap: 10px; }
        .btn-confirm { background: #0f172a; color: white; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
        .btn-close { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; }
        
        @media (max-width: 768px) { .header-flex { flex-direction: column; gap: 15px; align-items: flex-start; } }
    </style>
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>📦 Your Orders</h2>
        <div style="display:flex; gap:10px;">
            <a href="renew_expiry.php" class="btn-nav btn-renew">🔄 Renewals</a>
            <a href="user_dashboard.php" class="btn-nav btn-back">← Dashboard</a>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Service</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        
                        $dbStatus = !empty($row['status']) ? $row['status'] : 'Pending';
                        $payStatus = $row['payment_status'];
                        $isCompleted = (stripos($dbStatus, 'Completed') !== false);
                        
                        $finalStatus = $dbStatus;

                        if ($dbStatus === 'Pending' && strcasecmp($payStatus, 'failed') === 0) {
                            $finalStatus = 'Failed';
                        }

                        $badgeClass = 'status-pending';
                        if (stripos($finalStatus, 'Processing') !== false || stripos($finalStatus, 'Working') !== false || stripos($finalStatus, 'Submitted') !== false) {
                            $badgeClass = 'status-working';
                        } elseif ($isCompleted) {
                            $badgeClass = 'status-completed';
                        } elseif (stripos($finalStatus, 'Cancelled') !== false || stripos($finalStatus, 'Failed') !== false) {
                            $badgeClass = 'status-cancelled';
                        }

                        // FIXED LOGIC: Completed orders should be downloadable regardless of payment_received flag (for 0 amount orders)
                        $canDownload = ($isCompleted);
                        
                        $downloadUrl = '';
                        if (!empty($row['order_link'])) $downloadUrl = $row['order_link'];
                        elseif (!empty($row['service_link'])) $downloadUrl = $row['service_link'];
                        elseif (!empty($row['file'])) $downloadUrl = "download.php?file=" . urlencode($row['file']);
                    ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td>
                            <strong style="display:block; color:#334155;"><?= htmlspecialchars($row['service_name']) ?></strong>
                            <span style="font-size:0.85rem; color:#64748b;">₹<?= number_format($row['price']) ?></span>
                        </td>
                        <td><?= date("M d, Y", strtotime($row['order_date'])) ?></td>
                        
                        <td><span class="status-badge <?= $badgeClass ?>"><?= htmlspecialchars($finalStatus) ?></span></td>
                        
                        <td>
                            <?php if ($canDownload && !empty($downloadUrl)): ?>
                                <a href="<?= $downloadUrl ?>" class="btn-action btn-download" target="_blank">⬇ Download</a>
                                <?php if (empty($row['order_feedback'])): ?>
                                    <button onclick="showFeedbackPopup(<?= $row['id'] ?>, <?= $row['service_id'] ?>)" class="btn-action btn-feedback">⭐ Rate</button>
                                <?php endif; ?>

                            <?php else: ?>
                                <?php if (stripos($finalStatus, 'Working') !== false): ?>
                                    <a href="upload_documents.php?service_id=<?= $row['service_id'] ?>" class="btn-action btn-upload">📄 Upload Docs</a>
                                
                                <?php elseif ($finalStatus === 'Pending'): ?>
                                    <button onclick="showCancelPopup(<?= $row['id'] ?>)" class="btn-action btn-cancel">✕ Cancel</button>
                                
                                <?php elseif (stripos($finalStatus, 'Failed') !== false || stripos($finalStatus, 'Cancelled') !== false): ?>
                                    <span style="color:#ef4444; font-size:0.85rem;">Cancelled/Failed</span>
                                
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.9rem;">Processing...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <td>
                            <a href="user_order_view.php?id=<?= $row['id'] ?>" style="color:#64748b; font-weight:600; text-decoration:none;">View &rarr;</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:40px; color:#64748b;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="popup-overlay" id="cancel-popup-overlay">
    <div class="popup">
        <h3>Cancel Order</h3>
        <form id="cancel-form">
            <input type="hidden" id="cancel-order-id" name="order_id">
            <label>Reason (Optional):</label>
            <textarea id="cancel-reason" rows="3" placeholder="Why do you want to cancel?"></textarea>
            <div class="popup-btns">
                <button type="button" class="btn-close" onclick="closePopup('cancel-popup-overlay')">Close</button>
                <button type="submit" class="btn-confirm" style="background:#ef4444;">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="popup-overlay" id="feedback-popup-overlay">
    <div class="popup">
        <h3>Rate Your Experience</h3>
        <form id="feedback-form">
            <input type="hidden" id="feedback-order-id" name="order_id">
            <input type="hidden" id="feedback-service-id" name="service_id">
            <label>Rating:</label>
            <select id="feedback_score" required>
                <option value="5">⭐⭐⭐⭐⭐ - Excellent</option>
                <option value="4">⭐⭐⭐⭐ - Good</option>
                <option value="3">⭐⭐⭐ - Average</option>
                <option value="2">⭐⭐ - Poor</option>
                <option value="1">⭐ - Bad</option>
            </select>
            <label>Comment:</label>
            <textarea id="feedback" rows="3" placeholder="Tell us more..."></textarea>
            <div class="popup-btns">
                <button type="button" class="btn-close" onclick="closePopup('feedback-popup-overlay')">Close</button>
                <button type="submit" class="btn-confirm">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showCancelPopup(id) {
        document.getElementById('cancel-order-id').value = id;
        document.getElementById('cancel-popup-overlay').style.display = 'flex';
    }
    function showFeedbackPopup(oid, sid) {
        document.getElementById('feedback-order-id').value = oid;
        document.getElementById('feedback-service-id').value = sid;
        document.getElementById('feedback-popup-overlay').style.display = 'flex';
    }
    function closePopup(id) {
        document.getElementById(id).style.display = 'none';
    }
    document.getElementById('cancel-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('cancel-order-id').value;
        const reason = document.getElementById('cancel-reason').value;
        fetch('cancel_order.php', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${id}&cancel_reason=${encodeURIComponent(reason)}`
        }).then(res => res.text()).then(() => { alert('Order cancelled'); location.reload(); });
    });
    document.getElementById('feedback-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const oid = document.getElementById('feedback-order-id').value;
        const sid = document.getElementById('feedback-service-id').value;
        const score = document.getElementById('feedback_score').value;
        const comment = document.getElementById('feedback').value;
        fetch('submit_feedback.php', {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${oid}&service_id=${sid}&feedback_score=${score}&feedback=${encodeURIComponent(comment)}`
        }).then(res => res.text()).then(() => { alert('Feedback submitted!'); location.reload(); });
    });
</script>

</body>
</html>
<?php include 'footer.php'; ?>