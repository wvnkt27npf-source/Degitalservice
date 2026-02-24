<?php
session_start();
include './config.php';
include './header.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

// --- UPLOAD LOGIC ---
if (isset($_POST['submit_update'])) {
    $version = $_POST['version_code'];
    $target_dir = "uploads/tools/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = time() . "_" . basename($_FILES["tool_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if($fileType != "exe" && $fileType != "zip") {
        echo "<script>alert('Only EXE or ZIP allowed!');</script>";
    } else {
        if (move_uploaded_file($_FILES["tool_file"]["tmp_name"], $target_file)) {
            $full_url = "https://degitalservice.com/" . $target_file;
            
            $stmt = $conn->prepare("INSERT INTO tool_updates (version_code, download_link) VALUES (?, ?)");
            $stmt->bind_param("ss", $version, $full_url);
            
            if($stmt->execute()){
                echo "<script>alert('Update Live Successfully!'); window.location='admin_tool_updates.php';</script>";
            }
        } else {
            echo "<script>alert('Upload Failed');</script>";
        }
    }
}

// --- DELETE LOGIC ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tool_updates WHERE id = $id");
    echo "<script>alert('Deleted!'); window.location='admin_tool_updates.php';</script>";
}
?>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="table-container" style="padding:30px; max-width:800px; margin:30px auto; background:white; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
    
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <a href="admin_tool_settings.php" style="text-decoration:none; background:#6b7280; color:white; padding:8px 15px; border-radius:5px; font-weight:500;">⬅ Tool Settings</a>
        <a href="admin_software.php" style="text-decoration:none; background:#8b5cf6; color:white; padding:8px 15px; border-radius:5px; font-weight:500;">📂 Manage Softwares ➡</a>
    </div>

    <h2 style="text-align:center; border-bottom:2px solid #f3f4f6; padding-bottom:15px; margin-bottom:25px;">🚀 Publish Tool Update</h2>
    
    <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:15px;">
        
        <div>
            <label style="font-weight:bold; color:#374151;">New Version Code (e.g. 1.1):</label>
            <input type="text" name="version_code" required style="width:100%; padding:10px; margin-top:5px; border:1px solid #d1d5db; border-radius:6px;">
        </div>
        
        <div>
            <label style="font-weight:bold; color:#374151;">Select Tool File (.exe / .zip):</label>
            <input type="file" name="tool_file" required style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px; background:#f9fafb;">
        </div>
        
        <button type="submit" name="submit_update" style="background:#10b981; color:white; padding:12px; border:none; border-radius:6px; cursor:pointer; font-size:16px; font-weight:600; margin-top:10px;">Upload & Publish Update</button>
    </form>

    <hr style="margin:30px 0; border:0; border-top:1px solid #eee;">
    
    <h3>Recent Updates History</h3>
    <table width="100%" border="0" cellpadding="12" style="border-collapse:collapse;">
        <thead>
            <tr style="background:#f3f4f6; text-align:left; color:#374151;">
                <th>Version</th>
                <th>Download Link</th>
                <th>Release Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $conn->query("SELECT * FROM tool_updates ORDER BY id DESC LIMIT 10");
            if ($res->num_rows > 0) {
                while($row = $res->fetch_assoc()) {
                    echo "<tr style='border-bottom:1px solid #eee;'>
                        <td><span style='background:#fffbeb; color:#b45309; padding:2px 8px; border-radius:10px; font-weight:bold; font-size:12px;'>v{$row['version_code']}</span></td>
                        <td><a href='{$row['download_link']}' style='color:#2563eb; text-decoration:none;'>Download File</a></td>
                        <td style='color:#6b7280; font-size:13px;'>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                        <td><a href='admin_tool_updates.php?delete={$row['id']}' style='color:#ef4444; text-decoration:none; font-weight:500;' onclick='return confirm(\"Delete this version?\")'>Delete</a></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center; color:#9ca3af; padding:20px;'>No updates found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<?php include 'footer.php'; ?>