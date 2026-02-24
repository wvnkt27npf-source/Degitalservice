<?php
include 'config.php';

// --- ACTIONS (Handle Delete & Update) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("DELETE FROM subscriptions WHERE id='$id'");
    header("Location: admin.php"); // Refresh page
}

if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $plan = $_POST['plan'];
    
    // Plan change hone par amount bhi update karein
    $amount = 0;
    if (strpos($plan, '3500') !== false) $amount = 3500;
    elseif (strpos($plan, '4100') !== false) $amount = 4100;
    elseif (strpos($plan, '10,000') !== false) $amount = 10000;

    $stmt = $conn->prepare("UPDATE subscriptions SET status=?, plan=?, amount=? WHERE id=?");
    $stmt->bind_param("ssii", $status, $plan, $amount, $id);
    $stmt->execute();
    echo "<script>alert('User Updated Successfully!'); window.location='admin.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Digital Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .status-pending { color: #d35400; font-weight: bold; }
        .status-submitted { color: #2980b9; font-weight: bold; }
        .status-approved { color: #27ae60; font-weight: bold; }
        .status-rejected { color: #c0392b; font-weight: bold; }
        .navbar { background: #1e1e1e !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Digital Service Admin</span>
        <button class="btn btn-outline-light btn-sm" onclick="location.reload()">Refresh Data</button>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card p-4">
                <h4 class="mb-4">User Management</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>User Details</th>
                                <th>Plan & Amount</th>
                                <th>Payment Info (UTR)</th>
                                <th>Status</th>
                                <th>Actions (Edit / Delete)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Sabhi users show karein (Latest first)
                            $result = $conn->query("SELECT * FROM subscriptions ORDER BY id DESC");
                            while ($row = $result->fetch_assoc()) {
                                $statusClass = 'status-' . $row['status'];
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo $row['email']; ?></small><br>
                                    <small>📞 <?php echo $row['mobile']; ?></small>
                                </td>
                                <td>
                                    <?php echo $row['plan']; ?><br>
                                    <span class="badge bg-secondary">₹<?php echo $row['amount']; ?></span>
                                </td>
                                <td>
                                    <?php if($row['utr_number']) { ?>
                                        <div class="alert alert-info py-1 px-2 m-0" style="font-size: 0.9rem;">
                                            UTR: <strong><?php echo $row['utr_number']; ?></strong>
                                        </div>
                                    <?php } else { echo "<span class='text-muted'>Not Paid</span>"; } ?>
                                </td>
                                <td class="<?php echo $statusClass; ?>">
                                    <?php echo strtoupper($row['status']); ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        
                                        <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                            <option value="pending" <?php if($row['status']=='pending') echo 'selected'; ?>>Pending</option>
                                            <option value="submitted" <?php if($row['status']=='submitted') echo 'selected'; ?>>Submitted</option>
                                            <option value="approved" <?php if($row['status']=='approved') echo 'selected'; ?>>Approve</option>
                                            <option value="rejected" <?php if($row['status']=='rejected') echo 'selected'; ?>>Reject</option>
                                        </select>
                                        
                                        <select name="plan" class="form-select form-select-sm" style="width: 150px;">
                                            <option value="Personal (3500/yr)" <?php if(strpos($row['plan'], '3500')!==false) echo 'selected'; ?>>Personal</option>
                                            <option value="Family (4100/yr)" <?php if(strpos($row['plan'], '4100')!==false) echo 'selected'; ?>>Family</option>
                                            <option value="Lifetime (10,000)" <?php if(strpos($row['plan'], '10,000')!==false) echo 'selected'; ?>>Lifetime</option>
                                        </select>

                                        <button type="submit" name="update_user" class="btn btn-primary btn-sm">Save</button>
                                        
                                        <a href="admin.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this user?');">X</a>
                                    </form>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>