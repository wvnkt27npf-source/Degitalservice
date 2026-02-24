<?php
session_start();
include './config.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
    header('Location: login.php'); 
    exit(); 
}

// --- 2. HANDLE ACTIONS ---

// Account: Block/Unblock
if (isset($_POST['block_user'])) {
    $stmt = $conn->prepare("UPDATE users SET status = 'blocked', block_reason = ? WHERE id = ?");
    $stmt->bind_param("si", $_POST['block_reason'], $_POST['user_id']);
    $stmt->execute();
    echo "<script>alert('Account Blocked!'); window.location.href='manage_users.php';</script>"; 
    exit;
}
if (isset($_GET['unblock_user'])) {
    $conn->query("UPDATE users SET status = 'active', block_reason = NULL WHERE id = " . intval($_GET['unblock_user']));
    echo "<script>alert('Account Active!'); window.location.href='manage_users.php';</script>"; 
    exit;
}

// License & HWID Actions
if (isset($_GET['suspend_license'])) {
    $conn->query("UPDATE users SET license_status = 'suspended' WHERE id = " . intval($_GET['suspend_license']));
    echo "<script>alert('License Suspended!'); window.location.href='manage_users.php';</script>"; exit;
}
if (isset($_GET['activate_license'])) {
    $conn->query("UPDATE users SET license_status = 'active' WHERE id = " . intval($_GET['activate_license']));
    echo "<script>alert('License Activated!'); window.location.href='manage_users.php';</script>"; exit;
}
if (isset($_GET['reset_hwid'])) {
    $conn->query("UPDATE users SET hwid = NULL WHERE id = " . intval($_GET['reset_hwid']));
    echo "<script>alert('HWID Reset!'); window.location='manage_users.php';</script>"; exit;
}

// Password Change
if (isset($_POST['change_password'])) {
    $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $pass, $_POST['user_id']);
    $stmt->execute();
    echo "<script>alert('Password Updated!'); window.location.href='manage_users.php';</script>"; exit;
}

// Login As User
if (isset($_GET['impersonate_user'])) {
    $user = $conn->query("SELECT * FROM users WHERE id = " . intval($_GET['impersonate_user']))->fetch_assoc();
    
    if ($user && $user['status'] !== 'blocked') {
        $_SESSION['user_id'] = $user['id']; 
        $_SESSION['username'] = $user['username']; 
        
        // --- FIX START ---
        // Pehle yahan 'user' likha tha, ab hum database wala actual role use karenge
        $_SESSION['role'] = $user['role']; 
        // --- FIX END ---

        header('Location: category.php'); exit;
    } else { 
        echo "<script>alert('Account is blocked!'); window.location.href='manage_users.php';</script>"; exit; 
    }
}

// --- 3. FILTERS & QUERY LOGIC ---

$whereClauses = [];
$orderBy = "u.id DESC"; // Default sort

// Filter by Role
if (!empty($_GET['role'])) {
    $role = $conn->real_escape_string($_GET['role']);
    $whereClauses[] = "u.role = '$role'";
}

// Search (Name, Email, Phone)
if (!empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $whereClauses[] = "(u.username LIKE '%$search%' OR u.email LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

// Sorting Logic
if (!empty($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'joined_desc': $orderBy = "first_order_date DESC"; break;
        case 'joined_asc': $orderBy = "first_order_date ASC"; break;
        case 'purchases_desc': $orderBy = "lifetime_purchases DESC"; break;
        case 'purchases_asc': $orderBy = "lifetime_purchases ASC"; break;
    }
}

$whereSQL = count($whereClauses) > 0 ? "WHERE " . implode(' AND ', $whereClauses) : "";

// --- 4. FETCH DATA ---
$live_count = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE)")->fetch_assoc()['cnt'];

// Modified Query: Uses 'order_date' to calculate Joined Date since 'created_at' is missing in users table
$query = "
    SELECT u.*, 
           COALESCE(SUM(CASE WHEN o.status = 'Completed' THEN o.price ELSE 0 END), 0) AS lifetime_purchases,
           COUNT(o.id) AS total_orders,
           MIN(o.order_date) as first_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    $whereSQL
    GROUP BY u.id
    ORDER BY $orderBy
";
$usersResult = $conn->query($query);

include './header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .table-container { max-width: 1500px; width: 98%; margin: 30px auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .live-badge { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 6px; }
        .blink-dot { height: 8px; width: 8px; background-color: #22c55e; border-radius: 50%; animation: blink 1.5s infinite; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
        
        .logs-btn { background: #4f46e5; color: white; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-size: 13px; }

        /* Filter Bar Styles */
        .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; background: #f9fafb; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 15px; }
        .filter-select, .filter-input { padding: 8px; border: 1px solid #d1d5db; border-radius: 5px; font-size: 13px; }
        .filter-btn { background: #3b82f6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; }
        .filter-btn:hover { background: #2563eb; }
        .reset-btn { background: #6b7280; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 13px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; vertical-align: middle; }
        th { background-color: #f3f4f6; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }

        .user-info-cell div { margin-bottom: 2px; }
        .user-role-tag { font-size: 10px; padding: 2px 6px; border-radius: 4px; background: #e0f2fe; color: #0369a1; font-weight: 600; text-transform: uppercase; display: inline-block; margin-left: 5px; }

        .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .online { background-color: #10b981; box-shadow: 0 0 4px #10b981; }
        .offline { background-color: #9ca3af; }

        .key-box { font-family: monospace; background: #eff6ff; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; border: 1px solid #dbeafe; font-weight: bold; font-size: 11px; }
        .gen-btn { background: #059669; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 11px; }

        .hwid-locked { color: #b91c1c; font-weight: 700; font-size: 10px; }
        .hwid-open { color: #15803d; font-weight: 600; font-size: 10px; }
        .reset-link { color: #2563eb; text-decoration: underline; cursor: pointer; font-size: 10px; margin-left: 5px; }

        /* Badges */
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-blocked { background: #fee2e2; color: #991b1b; }
        .badge-suspended { background: #ffedd5; color: #9a3412; }

        /* Action Buttons */
        .action-group { display: flex; gap: 5px; }
        .btn-mini { padding: 6px 10px; border-radius: 4px; color: white; font-size: 11px; border: none; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 500; }
        .btn-block { background: #ef4444; } 
        .btn-unblock { background: #10b981; } 
        .btn-suspend { background: #f97316; } 
        .btn-activate { background: #10b981; }
        .btn-pass { background: #f59e0b; } 
        .btn-login { background: #3b82f6; }
        .btn-principle { background: #8b5cf6; }

        .modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 25px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); z-index: 1000; width: 350px; }
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999; backdrop-filter: blur(2px); }
        .close-modal { background: #f3f4f6; color: #374151; width: 100%; padding: 10px; margin-top: 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        
        .info-btn { background-color: #e0e7ff; color: #4f46e5; width: 20px; height: 20px; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; font-size: 11px; cursor: pointer; border: 1px solid #c7d2fe; margin-left: 5px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; border-bottom: 1px solid #f3f4f6; padding-bottom: 5px; font-size: 13px; }
        input, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #d1d5db; border-radius: 4px; }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeModals()"></div>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
    <div class="table-container">
    <div class="header-area">
        <div style="display:flex; align-items:center; gap:15px;">
            <h2 style="margin:0;">Manage Users</h2>
            <span class="live-badge"><span class="blink-dot"></span> <?= $live_count; ?> Live</span>
        </div>
        <a href="admin_logs.php" class="logs-btn">Logs</a>
    </div>

    <form method="GET" class="filter-bar">
        <select name="role" class="filter-select">
            <option value="">All Roles</option>
            <option value="user" <?= isset($_GET['role']) && $_GET['role']=='user' ? 'selected' : '' ?>>User</option>
            <option value="client" <?= isset($_GET['role']) && $_GET['role']=='client' ? 'selected' : '' ?>>Client</option>
            <option value="customer" <?= isset($_GET['role']) && $_GET['role']=='customer' ? 'selected' : '' ?>>Customer</option>
        </select>

        <select name="sort" class="filter-select">
            <option value="">Default Sort</option>
            <option value="joined_desc" <?= isset($_GET['sort']) && $_GET['sort']=='joined_desc' ? 'selected' : '' ?>>Newest Joined</option>
            <option value="joined_asc" <?= isset($_GET['sort']) && $_GET['sort']=='joined_asc' ? 'selected' : '' ?>>Oldest Joined</option>
            <option value="purchases_desc" <?= isset($_GET['sort']) && $_GET['sort']=='purchases_desc' ? 'selected' : '' ?>>Highest Purchase</option>
        </select>

        <input type="text" name="search" class="filter-input" placeholder="Search name, email..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        
        <button type="submit" class="filter-btn">Filter</button>
        <a href="manage_users.php" class="reset-btn">Reset</a>
    </form>

    <table id="usersTable">
        <thead>
            <tr>
                <th>ID</th>
                <th style="width: 250px;">User Details</th>
                <th>Joined Date</th>
                <th>Purchases</th>
                <th>Status</th>
                <th>License & HWID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $usersResult->fetch_assoc()): ?>
                <?php
                    $is_online = (!empty($user['last_seen']) && (time() - strtotime($user['last_seen']) < 120));
                    $acc_status = $user['status'];
                    $lic_status = $user['license_status'] ?? 'active';
                    
                    // Fixed Joined Date: Uses First Order Date or 'N/A'
                    $joined_date = !empty($user['first_order_date']) ? date("d M Y", strtotime($user['first_order_date'])) : '<span style="color:#9ca3af">No Orders</span>';
                ?>
                <tr style="<?= ($acc_status == 'blocked') ? 'background:#fef2f2;' : '' ?>">
                    <td><?= $user['id']; ?></td>
                    
                    <td class="user-info-cell">
                        <div style="display:flex; align-items:center; margin-bottom:4px;">
                            <span class="status-dot <?= $is_online?'online':'offline' ?>"></span>
                            <strong style="color:#111827;"><?= htmlspecialchars($user['username']); ?></strong>
                            <span class="user-role-tag"><?= strtoupper($user['role']); ?></span>
                            <span class="info-btn" onclick='openInfoModal(<?php echo json_encode([
                                "name" => $user['username'],
                                "role" => $user['role'],
                                "email" => $user['email'],
                                "phone" => $user['phone'],
                                "state" => $user['state'],
                                "joined" => $joined_date,
                                "total_orders" => $user['total_orders'],
                                "lifetime" => number_format($user['lifetime_purchases'], 2)
                            ]); ?>)'>i</span>
                        </div>
                    </td>

                    <td style="font-weight:500; color:#374151;"><?= $joined_date; ?></td>

                    <td>
                        <div style="font-weight:bold; color:#059669;">₹<?= number_format($user['lifetime_purchases']); ?></div>
                        <div style="font-size:11px; color:#6b7280;"><?= $user['total_orders']; ?> Orders</div>
                    </td>

                    <td>
                        <div style="margin-bottom:4px;">
                            <?php if ($acc_status == 'active'): ?>
                                <span class="badge badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge badge-blocked">Blocked</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($lic_status == 'active'): ?>
                                <span class="badge badge-active" style="font-size:10px;">Lic: Active</span>
                            <?php else: ?>
                                <span class="badge badge-suspended" style="font-size:10px;">Lic: Suspended</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td id="key-cell-<?= $user['id']; ?>">
                        <?php if (!empty($user['license_key'])): ?>
                            <span class="key-box"><?= htmlspecialchars($user['license_key']); ?></span>
                            <div style="margin-top:4px;">
                                <?php if (!empty($user['hwid'])): ?>
                                    <span class="hwid-locked">🔒 LOCKED</span>
                                    <a class="reset-link" href="manage_users.php?reset_hwid=<?= $user['id']; ?>" onclick="return confirm('Reset HWID?')">Reset</a>
                                <?php else: ?>
                                    <span class="hwid-open">🔓 OPEN</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <button class="gen-btn" onclick="generateKey(<?= $user['id']; ?>)">+ Key</button>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="action-group">
                            <?php if ($acc_status !== 'blocked'): ?>
                                <button class="btn-mini btn-block" title="Block Account" onclick="openBlockModal(<?= $user['id']; ?>)">Block</button>
                            <?php else: ?>
                                <a href="manage_users.php?unblock_user=<?= $user['id']; ?>" class="btn-mini btn-unblock">Unblock</a>
                            <?php endif; ?>

                            <?php if ($lic_status !== 'suspended'): ?>
                                <a href="manage_users.php?suspend_license=<?= $user['id']; ?>" class="btn-mini btn-suspend" title="Suspend License">Sus</a>
                            <?php else: ?>
                                <a href="manage_users.php?activate_license=<?= $user['id']; ?>" class="btn-mini btn-activate">Act</a>
                            <?php endif; ?>

                            <a href="admin_principles.php?user_id=<?= $user['id']; ?>" class="btn-mini btn-principle">Prin</a>
                            <button class="btn-mini btn-pass" onclick="openPassModal(<?= $user['id']; ?>)">Pass</button>
                            <a href="manage_users.php?impersonate_user=<?= $user['id']; ?>" class="btn-mini btn-login" title="Login As User">Login</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="infoModal" class="modal">
    <h3 style="color:#4f46e5; margin-top:0; padding-bottom:10px; border-bottom:1px solid #eee;">User Details</h3>
    <div id="infoContent"></div>
    <button class="close-modal" onclick="closeModals()">Close</button>
</div>

<div id="blockModal" class="modal">
    <h3 style="color:#dc2626; margin-top:0;">🚫 Block Account</h3>
    <form method="POST">
        <input type="hidden" name="user_id" id="block_user_id">
        <textarea name="block_reason" placeholder="Reason for blocking..." required rows="3"></textarea>
        <button type="submit" name="block_user" class="btn-mini btn-block" style="width:100%; padding:10px; font-size:14px;">Confirm Block</button>
    </form>
    <button class="close-modal" onclick="closeModals()">Cancel</button>
</div>

<div id="passModal" class="modal">
    <h3 style="color:#2563eb; margin-top:0;">🔑 Change Password</h3>
    <form method="POST">
        <input type="hidden" name="user_id" id="pass_user_id">
        <input type="text" name="new_password" placeholder="New Password" required>
        <button type="submit" name="change_password" class="btn-mini btn-login" style="width:100%; padding:10px; font-size:14px;">Update</button>
    </form>
    <button class="close-modal" onclick="closeModals()">Cancel</button>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    // Initialize DataTable but disable default search since we have custom filters
    $(document).ready(function () {
        $('#usersTable').DataTable({
            "paging": true,
            "searching": false, 
            "info": false,
            "ordering": false // We handle sorting via PHP
        });
    });

    function openInfoModal(data) {
        let html = `
            <div class="info-row"><span>Username:</span> <strong>${data.name}</strong></div>
            <div class="info-row"><span>Role:</span> <strong>${data.role.toUpperCase()}</strong></div>
            <div class="info-row"><span>Email:</span> <strong>${data.email}</strong></div>
            <div class="info-row"><span>Phone:</span> <strong>${data.phone}</strong></div>
            <div class="info-row"><span>State:</span> <strong>${data.state}</strong></div>
            <hr style="border:0; border-top:1px dashed #ddd; margin:10px 0;">
            <div class="info-row"><span>Joined (First Order):</span> <strong>${data.joined}</strong></div>
            <div class="info-row"><span>Total Orders:</span> <strong>${data.total_orders}</strong></div>
            <div class="info-row"><span>Lifetime Purchase:</span> <strong style="color:#059669;">₹${data.lifetime}</strong></div>
        `;
        document.getElementById('infoContent').innerHTML = html;
        document.getElementById('infoModal').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }

    function openBlockModal(id) {
        document.getElementById('block_user_id').value = id;
        document.getElementById('blockModal').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }
    function openPassModal(id) {
        document.getElementById('pass_user_id').value = id;
        document.getElementById('passModal').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }
    function closeModals() {
        document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        document.getElementById('overlay').style.display = 'none';
    }

    function generateKey(userId) {
        if(!confirm("Generate Key?")) return;
        $.post('generate_key.php', {user_id: userId}, function(res) {
            try {
                let data = JSON.parse(res);
                if(data.status === 'success') {
                    $('#key-cell-' + userId).html('<span class="key-box">' + data.key + '</span>');
                    alert("Key Generated!");
                } else { alert(data.message); }
            } catch(e) { alert("Server Error"); }
        });
    }
</script>

</body>
</html>
<?php include 'footer.php'; ?>