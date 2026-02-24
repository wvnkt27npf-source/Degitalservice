<?php
session_start();
include './config.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'header.php';
$user_id = $_SESSION['user_id'];
$selected_cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principles & Guidelines</title>
    
    <link rel="stylesheet" href="category.css">
    
    <style>
        /* Table Styles for Detail View */
        .table-container {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        .p-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            color: black;
        }
        .p-table th, .p-table td {
            padding: 12px 15px;
            border: 1px solid #000000;
            text-align: left;
            font-size: 14px;
        }
        .p-table th {
            background-color: #007bff;
            color: black;
            font-weight: 600;
            white-space: nowrap;
        }
        .p-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .p-table tr:hover {
            background-color: #f1f1f1;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background: black;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .back-link:hover { background: #5a6268; }
        
        .page-header {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 30px;
            color: #ffffff;
        }
        
        /* Custom icon style if needed */
        .category img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">

    <?php if ($selected_cat_id > 0): ?>
        <?php
        // Fetch principles for this category only, assigned to the user
        $stmt = $conn->prepare("
            SELECT p.*, pc.name as cat_name
            FROM principles p
            JOIN user_principles up ON p.id = up.principle_id
            LEFT JOIN principle_categories pc ON p.category_id = pc.id
            WHERE up.user_id = ? AND p.category_id = ?
            ORDER BY p.title ASC
        ");
        $stmt->bind_param("ii", $user_id, $selected_cat_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Get Category Name for the header
        $catName = "Principles List";
        $rows = [];
        
        if ($result->num_rows > 0) {
            while($r = $result->fetch_assoc()) {
                $rows[] = $r;
                // Use category name from first row
                if ($catName === "Principles List") {
                    $catName = $r['cat_name'];
                }
            }
        } else {
            // Agar koi Principle nahi hai, tab bhi Category ka naam sahi dikhane ke liye alag se fetch karein
            $catNameQuery = $conn->prepare("SELECT name FROM principle_categories WHERE id = ?");
            $catNameQuery->bind_param("i", $selected_cat_id);
            $catNameQuery->execute();
            $cnRes = $catNameQuery->get_result();
            if($cRow = $cnRes->fetch_assoc()) {
                $catName = $cRow['name'];
            }
        }
        ?>
        
        <div class="table-container">
            <a href="user_view_principles.php" class="back-link">&larr; Back to Categories</a>
            
            <h2 style="margin-top:0; color:#007bff;"><?= htmlspecialchars($catName) ?></h2>
            
            <?php if (!empty($rows)): ?>
                <table class="p-table">
                    <thead>
                        <tr>
                            <th>Firm Name</th>
                            <th>Authorized Person</th>
                            <th>Email ID</th>
                            <th>Mobile No</th>
                            <th>License / Cert No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td><?= htmlspecialchars($row['authorized_person']) ?></td>
                                <td><?= htmlspecialchars($row['email_id']) ?></td>
                                <td><?= htmlspecialchars($row['mobile_no']) ?></td>
                                <td><?= htmlspecialchars($row['license_no']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; padding:20px; color:#666;">No assigned principles found in this category.</p>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <?php
        // CHANGE: Fetch ALL categories (Chahe user ko assign ho ya na ho)
        $cat_query = $conn->prepare("SELECT id, name FROM principle_categories ORDER BY name ASC");
        $cat_query->execute();
        $categories = $cat_query->get_result();
        ?>
        
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
            <a href="category.php" class="back-link" style="margin-top: 20px;">&larr; Back to Dashboard</a>
            <h2 class="page-header">Select Principles Category</h2>
            
            <div class="category-grid">
                <?php if ($categories->num_rows > 0): ?>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <div class="category" onclick="window.location.href='user_view_principles.php?cat_id=<?= $cat['id'] ?>'">
                            <img src="uploads/folder_icon.png" alt="Category" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3767/3767084.png'">
                            <p><?= htmlspecialchars($cat['name']) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="width:100%; text-align:center; padding:20px;">No categories available.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
<?php include 'footer.php'; ?>