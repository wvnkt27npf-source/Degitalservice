<?php
session_start();
include './config.php';
include './header.php';

// Check Admin Access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

// --- SAVE SETTINGS ---
if (isset($_POST['save_seo'])) {
    foreach ($_POST['settings'] as $key => $value) {
        // Use prepared statements for security
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->bind_param("ss", $key, $value);
        $stmt->execute();
    }
    echo "<script>alert('SEO & Scripts Updated Successfully!'); window.location='admin_seo.php';</script>";
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
<div style="max-width:900px; margin:40px auto; padding:30px; background:white; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1);">
    
    <h2 style="border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:25px; color:#111827;">🚀 SEO & Google Tools Manager</h2>
    
    <form method="POST" style="display:flex; flex-direction:column; gap:20px;">
        
        <div style="background:#f9fafb; padding:20px; border-radius:8px; border:1px solid #e5e7eb;">
            <h3 style="margin-top:0; color:#2563eb;">🌐 Global Website SEO</h3>
            <p style="font-size:13px; color:#6b7280; margin-bottom:15px;">Ye settings home page aur default pages ke liye use hongi.</p>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600; display:block; margin-bottom:5px;">Site Title (Default):</label>
                <input type="text" name="settings[site_title]" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>" placeholder="Ex: Digital Services | Web Solutions" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600; display:block; margin-bottom:5px;">Meta Description:</label>
                <textarea name="settings[site_desc]" rows="2" placeholder="Ex: Best web development agency in India..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"><?= htmlspecialchars($settings['site_desc'] ?? '') ?></textarea>
            </div>

            <div>
                <label style="font-weight:600; display:block; margin-bottom:5px;">Meta Keywords:</label>
                <textarea name="settings[site_keywords]" rows="2" placeholder="Ex: web design, seo, digital marketing" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;"><?= htmlspecialchars($settings['site_keywords'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="background:#fff7ed; padding:20px; border-radius:8px; border:1px solid #fed7aa;">
            <h3 style="margin-top:0; color:#c2410c;">⚡ Third-Party Scripts (Google/FB)</h3>
            <p style="font-size:13px; color:#6b7280; margin-bottom:15px;">Analytics, GTM, Pixel, Verification codes yahan paste karein. (DO NOT paste PHP code here).</p>

            <div style="margin-bottom:15px;">
                <label style="font-weight:600; display:block; margin-bottom:5px;">Header Scripts (Inside &lt;head&gt;):</label>
                <textarea name="settings[header_scripts]" rows="6" placeholder="Paste Google Analytics, Google Tag Manager, or Verification meta tags here..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-family:monospace;"><?= htmlspecialchars($settings['header_scripts'] ?? '') ?></textarea>
            </div>

            <div>
                <label style="font-weight:600; display:block; margin-bottom:5px;">Footer Scripts (Before &lt;/body&gt;):</label>
                <textarea name="settings[footer_scripts]" rows="6" placeholder="Paste Chatbots, WhatsApp Widgets, or JS files here..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:5px; font-family:monospace;"><?= htmlspecialchars($settings['footer_scripts'] ?? '') ?></textarea>
            </div>
        </div>

        <button type="submit" name="save_seo" style="background:#2563eb; color:white; border:none; padding:15px; border-radius:5px; font-size:16px; font-weight:bold; cursor:pointer; margin-top:10px;">💾 Save All Settings</button>
    </form>
</div>

<?php include 'footer.php'; ?>