<?php
session_start();
include './config.php';
include './header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

// --- SAVE SETTINGS ---
if (isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    echo "<script>alert('Settings Saved! Live on all tools.'); window.location='admin_tool_settings.php';</script>";
}

// --- FETCH SETTINGS ---
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div style="max-width:800px; margin:40px auto; padding:30px; background:white; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <a href="admin_tool_updates.php" style="text-decoration:none; background:#6b7280; color:white; padding:8px 15px; border-radius:5px; font-weight:500;">⬅ Tool Update</a>
        
    </div>

    <h2 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:20px;">⚙️ Tool Live Settings</h2>
    
    <form method="POST" style="display:flex; flex-direction:column; gap:15px;">
        
        <div>
            <label style="font-weight:bold; display:block; margin-bottom:5px;">Tool Name (Title Bar):</label>
            <input type="text" name="settings[tool_name]" value="<?= $settings['tool_name'] ?? '' ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
        </div>

        <div>
            <label style="font-weight:bold; display:block; margin-bottom:5px;">Live Announcement / Message:</label>
            <textarea name="settings[custom_message]" rows="3" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"><?= $settings['custom_message'] ?? '' ?></textarea>
            <small style="color:gray;">Ye text tool ke dashboard par turant change ho jayega.</small>
        </div>

        <div style="display:flex; gap:20px;">
            <div style="flex:1;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Latest Version:</label>
                <input type="text" name="settings[tool_version]" value="<?= $settings['tool_version'] ?? '1.0' ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
            </div>
            <div style="flex:2;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Download Link:</label>
                <input type="text" name="settings[download_link]" value="<?= $settings['download_link'] ?? '' ?>" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
            </div>
        </div>

        <button type="submit" name="save_settings" style="background:#2563eb; color:white; border:none; padding:12px; border-radius:5px; font-size:16px; cursor:pointer; margin-top:10px;">Save Changes</button>
    </form>
</div>
<?php include 'footer.php'; ?>