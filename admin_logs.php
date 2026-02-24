<?php
session_start();
include './config.php';
include './header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit(); }

$result = $conn->query("SELECT * FROM login_logs ORDER BY id DESC LIMIT 100");
?>

<div style="max-width:1000px; margin:30px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Login Logs (Last 100)</h2>
        <a href="manage_users.php" style="text-decoration:none; background:#6b7280; color:white; padding:8px 12px; border-radius:5px;">&larr; Back to Users</a>
    </div>
    
    <table width="100%" border="1" style="border-collapse:collapse; border-color:#eee;">
        <tr style="background:#f9fafb; text-align:left;">
            <th style="padding:10px;">Time</th>
            <th style="padding:10px;">User</th>
            <th style="padding:10px;">IP Address</th>
            <th style="padding:10px;">HWID (PC ID)</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td style="padding:8px; color:#555;"><?= date('d M Y, H:i:s', strtotime($row['login_time'])); ?></td>
            <td style="padding:8px; font-weight:bold;"><?= htmlspecialchars($row['username']); ?></td>
            <td style="padding:8px; color:#2563eb;"><?= htmlspecialchars($row['ip_address']); ?></td>
            <td style="padding:8px; font-family:monospace; font-size:12px;"><?= htmlspecialchars($row['hwid']); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<?php include 'footer.php'; ?>