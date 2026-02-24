<?php
session_start();
include './config.php';

// Ensure admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// --- HANDLE CSV EXPORT ---
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=principles_list.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Category', 'Firm Name', 'Auth Person', 'Email', 'Mobile', 'License No')); 
    
    $query = "SELECT pc.name as category_name, p.title, p.authorized_person, p.email_id, p.mobile_no, p.license_no
              FROM principles p 
              LEFT JOIN principle_categories pc ON p.category_id = pc.id 
              ORDER BY pc.name, p.title";
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

include './header.php';

$message = "";
$msg_type = "";
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_user = null;

// --- HANDLE EDIT FETCHING ---
$edit_mode = false;
$edit_data = [
    'id' => '', 'category_id' => '', 'title' => '', 
    'authorized_person' => '', 'email_id' => '', 
    'mobile_no' => '', 'license_no' => ''
];

if (isset($_GET['edit_principle'])) {
    $edit_id = intval($_GET['edit_principle']);
    $q = $conn->query("SELECT * FROM principles WHERE id = $edit_id");
    if ($q->num_rows > 0) {
        $edit_mode = true;
        $edit_data = $q->fetch_assoc();
    }
}

// --- 1. HANDLE CATEGORY ACTIONS ---
if (isset($_POST['add_category'])) {
    $name = trim($_POST['cat_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO principle_categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $message = "Category added successfully."; $msg_type = "success";
        } else {
            $message = "Error adding category."; $msg_type = "error";
        }
    }
}

if (isset($_GET['delete_category'])) {
    $cId = intval($_GET['delete_category']);
    $conn->query("DELETE FROM principle_categories WHERE id = $cId");
    header("Location: admin_principles.php" . ($user_id ? "?user_id=$user_id" : ""));
    exit();
}

// --- 2. HANDLE PRINCIPLE ACTIONS (ADD & UPDATE) ---
if (isset($_POST['save_principle'])) {
    $p_id = intval($_POST['principle_id']); // 0 for new, ID for update
    $cat_id = intval($_POST['principle_category_id']);
    $title = trim($_POST['principle_title']); // Firm Name
    $auth = trim($_POST['auth_person']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $license = trim($_POST['license']);
    
    if (!empty($title) && $cat_id > 0) {
        if ($p_id > 0) {
            // UPDATE EXISTING
            $stmt = $conn->prepare("UPDATE principles SET category_id=?, title=?, authorized_person=?, email_id=?, mobile_no=?, license_no=? WHERE id=?");
            $stmt->bind_param("isssssi", $cat_id, $title, $auth, $email, $mobile, $license, $p_id);
            if ($stmt->execute()) {
                $message = "Firm details updated successfully."; $msg_type = "success";
                // Clear edit mode
                $edit_mode = false;
                $edit_data = array_fill_keys(array_keys($edit_data), ''); 
            } else {
                $message = "Error updating details."; $msg_type = "error";
            }
        } else {
            // ADD NEW
            // Check duplicate
            $check = $conn->prepare("SELECT id FROM principles WHERE title = ? AND category_id = ?");
            $check->bind_param("si", $title, $cat_id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $message = "Error: Firm Name already exists in this category!"; $msg_type = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO principles (category_id, title, authorized_person, email_id, mobile_no, license_no) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $cat_id, $title, $auth, $email, $mobile, $license);
                $stmt->execute();
                $message = "Firm/Principle added."; $msg_type = "success";
            }
        }
    } else {
        $message = "Category and Firm Name are required."; $msg_type = "error";
    }
}

if (isset($_GET['delete_principle'])) {
    $pId = intval($_GET['delete_principle']);
    $conn->query("DELETE FROM principles WHERE id = $pId");
    header("Location: admin_principles.php" . ($user_id ? "?user_id=$user_id" : ""));
    exit();
}

// --- 3. HANDLE CSV IMPORT ---
if (isset($_POST['import_csv'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $imp_cat_id = intval($_POST['import_category_id']);
        if ($imp_cat_id > 0) {
            $file = fopen($_FILES['csv_file']['tmp_name'], "r");
            $added = 0; $skipped = 0;
            while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
                $title = trim($data[0]);
                $auth = isset($data[1]) ? trim($data[1]) : '';
                $email = isset($data[2]) ? trim($data[2]) : '';
                $mobile = isset($data[3]) ? trim($data[3]) : '';
                $license = isset($data[4]) ? trim($data[4]) : '';

                if (!empty($title) && strtolower($title) !== 'firm name' && strtolower($title) !== 'title') {
                    $check = $conn->query("SELECT id FROM principles WHERE title = '$title' AND category_id = $imp_cat_id");
                    if ($check->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO principles (category_id, title, authorized_person, email_id, mobile_no, license_no) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssss", $imp_cat_id, $title, $auth, $email, $mobile, $license);
                        $stmt->execute();
                        $added++;
                    } else { $skipped++; }
                }
            }
            fclose($file);
            $message = "Import: Added $added, Skipped $skipped."; $msg_type = "success";
        } else {
            $message = "Please select a category for import."; $msg_type = "error";
        }
    }
}

// --- 4. HANDLE USER ASSIGNMENT ---
if (isset($_POST['save_user_principles']) && $user_id > 0) {
    $conn->query("DELETE FROM user_principles WHERE user_id = $user_id");
    if (isset($_POST['assigned_principles']) && is_array($_POST['assigned_principles'])) {
        $stmt = $conn->prepare("INSERT INTO user_principles (user_id, principle_id) VALUES (?, ?)");
        foreach ($_POST['assigned_principles'] as $pId) {
            $stmt->bind_param("ii", $user_id, $pId);
            $stmt->execute();
        }
    }
    $message = "User principles updated."; $msg_type = "success";
}

// --- FETCH DATA ---
$categories = $conn->query("SELECT * FROM principle_categories ORDER BY id DESC");
$cats_data = [];
while($r = $categories->fetch_assoc()) $cats_data[] = $r;

$principles_res = $conn->query("SELECT p.*, pc.name as cat_name FROM principles p LEFT JOIN principle_categories pc ON p.category_id = pc.id ORDER BY p.category_id DESC, p.id DESC");
$principles_data = [];
while($r = $principles_res->fetch_assoc()) $principles_data[] = $r;

$user_assigned_ids = [];
if ($user_id > 0) {
    $uRes = $conn->query("SELECT username FROM users WHERE id = $user_id");
    if ($uRes->num_rows > 0) $selected_user = $uRes->fetch_assoc();
    
    $aRes = $conn->query("SELECT principle_id FROM user_principles WHERE user_id = $user_id");
    while($r = $aRes->fetch_assoc()) $user_assigned_ids[] = $r['principle_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Principles</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .col { flex: 1; min-width: 300px; background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        h3 { border-bottom: 2px solid #007bff; padding-bottom: 10px; color: #333; margin-top: 0; }
        label { display: block; margin: 5px 0 2px; font-weight: bold; font-size: 13px; }
        input, select, button { width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #007bff; color: white; font-weight: bold; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        
        /* Action Buttons */
        .btn-action { text-decoration: none; padding: 2px 6px; font-size: 11px; border-radius: 3px; margin-left: 5px; color: white; }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-delete { background-color: #dc3545; }
        .btn-cancel { background-color: #6c757d; display:inline-block; text-align:center; text-decoration:none; width:auto; padding:8px 15px; float:right;}

        .list-container { max-height: 300px; overflow-y: auto; background: white; border: 1px solid #eee; padding: 10px; }
        .list-item { padding: 8px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        
        .assign-section { background: #e9f5ff; border: 2px solid #007bff; padding: 20px; margin-top: 20px; }
        .group-header { background: #ddd; padding: 5px 10px; font-weight: bold; margin-top: 10px; }
        .checkbox-item { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 5px; border-bottom: 1px solid #eee; padding: 5px 0; }
        .checkbox-item label { display: flex; align-items: center; font-weight: normal; margin: 0; cursor: pointer; flex-grow: 1; }
        .p-actions { min-width: 100px; text-align: right; }
        
        .back-btn { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #555; font-weight: bold; }
        .detail-prev { font-size: 11px; color: #666; display:block; margin-left: 25px; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <a href="manage_users.php" class="back-btn">&larr; Back to Manage Users</a>
    <h2>Admin Principles Management</h2>
    
    <?php if ($message): ?>
        <div class="message <?= $msg_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col">
            <h3>1. Add Category</h3>
            <form method="POST">
                <label>Category Name:</label>
                <input type="text" name="cat_name" placeholder="e.g. Safety Guidelines" required>
                <button type="submit" name="add_category">Create Category</button>
            </form>
            
            <div class="list-container">
                <strong>Existing Categories:</strong>
                <?php foreach ($cats_data as $c): ?>
                    <div class="list-item">
                        <?= htmlspecialchars($c['name']); ?>
                        <a href="?delete_category=<?= $c['id'] ?>&user_id=<?= $user_id ?>" class="btn-action btn-delete" onclick="return confirm('Delete category?')">X</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col" id="formSection">
            <h3><?= $edit_mode ? 'Edit' : '2. Add' ?> Firm Details</h3>
            
            <form method="POST" action="admin_principles.php?user_id=<?= $user_id ?>">
                <input type="hidden" name="principle_id" value="<?= $edit_data['id'] ?>">
                
                <label>Select Category:</label>
                <select name="principle_category_id" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($cats_data as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $edit_data['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label>Firm Name:</label>
                <input type="text" name="principle_title" value="<?= htmlspecialchars($edit_data['title']) ?>" placeholder="Enter Firm Name" required>
                
                <div style="display:flex; gap:10px;">
                    <input type="text" name="auth_person" value="<?= htmlspecialchars($edit_data['authorized_person']) ?>" placeholder="Authorized Person">
                    <input type="text" name="mobile" value="<?= htmlspecialchars($edit_data['mobile_no']) ?>" placeholder="Mobile No">
                </div>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="email" value="<?= htmlspecialchars($edit_data['email_id']) ?>" placeholder="Email ID">
                    <input type="text" name="license" value="<?= htmlspecialchars($edit_data['license_no']) ?>" placeholder="License/Cert No">
                </div>

                <button type="submit" name="save_principle" style="background-color: <?= $edit_mode ? '#ffc107' : '#007bff' ?>; color: <?= $edit_mode ? '#000' : '#fff' ?>">
                    <?= $edit_mode ? 'Update Details' : 'Add Firm/Principle' ?>
                </button>
                
                <?php if ($edit_mode): ?>
                    <a href="admin_principles.php?user_id=<?= $user_id ?>" class="btn-cancel">Cancel Edit</a>
                <?php endif; ?>
            </form>
            
            <?php if (!$edit_mode): ?>
            <hr>
            <h4>Import CSV <small>(Cols: Firm Name, Auth, Email, Mobile, License)</small></h4>
            <form method="POST" enctype="multipart/form-data">
                <select name="import_category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php foreach ($cats_data as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" name="import_csv">Upload CSV</button>
            </form>
            <a href="?export_csv=1" style="font-size:12px; float:right;">Download All CSV</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($selected_user): ?>
    <div class="assign-section">
        <h3>3. Assign to: <span style="color:#007bff"><?= htmlspecialchars($selected_user['username']) ?></span></h3>
        <input type="text" id="search" placeholder="Search firm names..." onkeyup="filterP()" style="padding:10px; border:2px solid #ddd;">
        
        <form method="POST">
            <div id="pList" style="max-height:500px; overflow-y:auto; background:white; padding:15px; border:1px solid #ccc;">
                <?php 
                $current_cat = "";
                foreach ($principles_data as $p): 
                    $catName = $p['cat_name'] ? $p['cat_name'] : "Uncategorized";
                    if ($current_cat != $catName): 
                        $current_cat = $catName;
                        echo "<div class='group-header'>$current_cat</div>";
                    endif;
                ?>
                    <div class="checkbox-item">
                        <label>
                            <input type="checkbox" name="assigned_principles[]" value="<?= $p['id'] ?>" 
                            <?= in_array($p['id'], $user_assigned_ids) ? 'checked' : '' ?> style="width:auto; margin-right:10px;">
                            <div>
                                <span class="p-text"><strong><?= htmlspecialchars($p['title']) ?></strong></span>
                                <span class="detail-prev">
                                    <?= $p['authorized_person'] ? htmlspecialchars($p['authorized_person']) : "No Auth Person" ?> 
                                    <?= $p['mobile_no'] ? " | " . htmlspecialchars($p['mobile_no']) : "" ?>
                                </span>
                            </div>
                        </label>
                        <div class="p-actions">
                            <a href="admin_principles.php?user_id=<?= $user_id ?>&edit_principle=<?= $p['id'] ?>#formSection" class="btn-action btn-edit">Edit</a>
                            <a href="admin_principles.php?user_id=<?= $user_id ?>&delete_principle=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this Firm/Principle permanently?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" name="save_user_principles" style="margin-top:15px;">Save Assignments</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<script>
function filterP() {
    let input = document.getElementById('search').value.toUpperCase();
    let items = document.getElementsByClassName('checkbox-item');
    for (let i = 0; i < items.length; i++) {
        let txt = items[i].getElementsByClassName('p-text')[0].innerText;
        items[i].style.display = txt.toUpperCase().indexOf(input) > -1 ? "flex" : "none";
    }
}
</script>

</body>
</html>
<?php include 'footer.php'; ?>