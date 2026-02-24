<?php
session_start();
include './config.php';
include './header.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

// --- DELETE SOFTWARE ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM software_list WHERE id = $id");
    echo "<script>alert('Software Deleted!'); window.location='admin_software.php';</script>";
}

// --- ADD SOFTWARE ---
if (isset($_POST['add_software'])) {
    $name = $_POST['name'];
    $ver = $_POST['version'];
    $link = $_POST['external_link'];

    // Agar File Upload ki gayi hai
    if (!empty($_FILES['soft_file']['name'])) {
        $target_dir = "uploads/softwares/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["soft_file"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["soft_file"]["tmp_name"], $target_file)) {
            $link = "https://degitalservice.com/" . $target_file;
        }
    }

    if (!empty($link)) {
        $stmt = $conn->prepare("INSERT INTO software_list (name, version, download_link) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $ver, $link);
        $stmt->execute();
        echo "<script>alert('Software Added!'); window.location='admin_software.php';</script>";
    } else {
        echo "<script>alert('Please upload a file or enter a link!');</script>";
    }
}
?>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div style="max-width:900px; margin:30px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
    
    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <a href="admin_tool_updates.php" style="text-decoration:none; background:#6b7280; color:white; padding:8px 15px; border-radius:5px; font-weight:500;">⬅ Tool Update</a>
        
    </div>

    <h2 style="border-bottom:2px solid #eee; padding-bottom:10px;">📂 Manage Tool Softwares</h2>

    <form method="POST" enctype="multipart/form-data" style="background:#f9fafb; padding:20px; border-radius:8px; margin-bottom:30px; border:1px dashed #ccc;">
        <div style="display:flex; gap:15px; margin-bottom:15px;">
            <div style="flex:2;">
                <label style="font-weight:bold;">Software Name:</label>
                <input type="text" name="name" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="flex:1;">
                <label style="font-weight:bold;">Version:</label>
                <input type="text" name="version" placeholder="e.g. 2.0" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
        </div>

        <div style="display:flex; gap:15px;">
            <div style="flex:1;">
                <label style="font-weight:bold;">Option A: Upload File</label>
                <input type="file" name="soft_file" style="width:100%; padding:8px; background:white; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="flex:1; text-align:center; padding-top:25px; font-weight:bold; color:#aaa;">OR</div>
            <div style="flex:1;">
                <label style="font-weight:bold;">Option B: External Link</label>
                <input type="text" name="external_link" placeholder="https://google.com/file..." style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            </div>
        </div>

        <button type="submit" name="add_software" style="width:100%; background:#2563eb; color:white; padding:10px; margin-top:15px; border:none; border-radius:4px; cursor:pointer; font-size:16px;">+ Add to Tool</button>
    </form>

    <table width="100%" border="1" style="border-collapse:collapse; border-color:#eee;">
        <tr style="background:#f3f4f6; text-align:left;">
            <th style="padding:10px;">Name</th>
            <th style="padding:10px;">Ver</th>
            <th style="padding:10px;">Link</th>
            <th style="padding:10px;">Action</th>
        </tr>
        <?php
        $res = $conn->query("SELECT * FROM software_list ORDER BY id DESC");
        while($row = $res->fetch_assoc()) {
            echo "<tr>
                <td style='padding:10px; font-weight:bold;'>{$row['name']}</td>
                <td style='padding:10px;'>v{$row['version']}</td>
                <td style='padding:10px;'><a href='{$row['download_link']}' target='_blank' style='color:#2563eb;'>Test Link</a></td>
                <td style='padding:10px;'><a href='admin_software.php?delete={$row['id']}' style='color:red; text-decoration:none;' onclick='return confirm(\"Delete?\")'>🗑 Delete</a></td>
            </tr>";
        }
        ?>
    </table>
</div>
<?php include 'footer.php'; ?>