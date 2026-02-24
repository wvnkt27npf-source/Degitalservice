<?php
// degitalservice/admin_payment_settings.php
session_start();

// Admin access check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

include 'config.php'; // Database connection
$configFile = 'phonepe_config_data.php';

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_phonepe'])) {
        // PhonePe Config Save logic
        $config = [
            'mode' => $_POST['mode'],
            'merchantId' => $_POST['merchantId'],
            'test_cid' => $_POST['test_cid'],
            'test_secret' => $_POST['test_secret'],
            'live_cid' => $_POST['live_cid'],
            'live_secret' => $_POST['live_secret'],
        ];
        $content = "<?php\n\$phonepe_config = " . var_export($config, true) . ";\n?>";
        file_put_contents($configFile, $content);
        $msg = "PhonePe settings updated successfully!";
    }

    if (isset($_POST['save_mobikwik'])) {
        // Mobikwik Database Update logic
        $m_upi = mysqli_real_escape_string($conn, $_POST['merchant_upi']);
        $m_auth = mysqli_real_escape_string($conn, $_POST['Authorization']);
        
        // Pehle check karein record hai ya nahi
        $check = $conn->query("SELECT id FROM mobikwik_token LIMIT 1");
        if ($check->num_rows > 0) {
            $sql = "UPDATE mobikwik_token SET merchant_upi='$m_upi', Authorization='$m_auth'";
        } else {
            $sql = "INSERT INTO mobikwik_token (merchant_upi, Authorization) VALUES ('$m_upi', '$m_auth')";
        }
        
        if ($conn->query($sql)) {
            $msg = "Mobikwik settings updated successfully!";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}

// Data Load karein display ke liye
if (file_exists($configFile)) { include($configFile); } 
else { $phonepe_config = ['mode'=>'test','merchantId'=>'','test_cid'=>'','test_secret'=>'','live_cid'=>'','live_secret'=>'']; }

$mobi_data = $conn->query("SELECT * FROM mobikwik_token LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Gateways</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 800px; margin: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h2 { color: #5f259f; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        label { font-weight: bold; display: block; margin-top: 15px; }
        input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #5f259f; color: white; border: none; padding: 12px; width: 100%; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
        .alert { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; grid-column: span 2; }
    </style>
</head>
<body>
<h1 style="text-align:center; color:#333;">Payment Gateway Manager</h1>
<div class="container">
    <?php if($msg) echo "<div class='alert'>$msg</div>"; ?>

    <div class="card">
        <h2>PhonePe (Official)</h2>
        <form method="POST">
            <label>Environment</label>
            <select name="mode">
                <option value="test" <?= ($phonepe_config['mode']=='test')?'selected':'' ?>>Sandbox</option>
                <option value="live" <?= ($phonepe_config['mode']=='live')?'selected':'' ?>>Live</option>
            </select>
            <label>Merchant ID</label>
            <input type="text" name="merchantId" value="<?= $phonepe_config['merchantId'] ?>">
            <label>Live Client ID</label>
            <input type="text" name="live_cid" value="<?= $phonepe_config['live_cid'] ?>">
            <label>Live Salt Key</label>
            <input type="text" name="live_secret" value="<?= $phonepe_config['live_secret'] ?>">
            <button type="submit" name="save_phonepe">SAVE PHONEPE</button>
        </form>
    </div>

    <div class="card">
        <h2>Mobikwik (UPI QR)</h2>
        <form method="POST">
            <label>Merchant UPI ID (VPA)</label>
            <input type="text" name="merchant_upi" value="<?= $mobi_data['merchant_upi'] ?? '' ?>" placeholder="example@ybl" required>
            <label>Mobikwik Auth Token</label>
            <textarea name="Authorization" rows="5" placeholder="Paste your Authorization token here..."><?= $mobi_data['Authorization'] ?? '' ?></textarea>
            <p style="font-size:12px; color:red;">* Ye token QR verification ke liye zaroori hai.</p>
            <button type="submit" name="save_mobikwik" style="background:#00baf2;">SAVE MOBIKWIK</button>
        </form>
    </div>
</div>
<p style="text-align:center; margin-top:20px;"><a href="admin_dashboard.php">Back to Dashboard</a></p>
</body>
</html>