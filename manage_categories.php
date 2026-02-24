<?php
session_start();
include './config.php';
include 'header.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch Categories
$categories = $conn->query("SELECT * FROM categories");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Reset & Fonts */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            color: #111827;
        }

        /* Top Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn:hover { transform: translateY(-1px); }

        .back-btn { background-color: #6b7280; } /* Gray */
        .back-btn:hover { background-color: #4b5563; }

        .add-btn { background-color: #4f46e5; } /* Indigo */
        .add-btn:hover { background-color: #4338ca; }

        /* Table Styles */
        .table-container {
            width: 100%;
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            min-width: 800px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #d1d5db; /* Full Border */
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        tr:hover td { background-color: #f9fafb; }

        /* Images in table */
        td img {
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        /* Row Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
            margin-right: 5px;
            display: inline-block;
        }

        .edit-btn { background-color: #f59e0b; } /* Amber */
        .edit-btn:hover { background-color: #d97706; }

        .delete-btn { background-color: #ef4444; } /* Red */
        .delete-btn:hover { background-color: #dc2626; }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 15px; margin: 15px auto; }
            .action-bar { flex-direction: column; align-items: stretch; }
            .btn { justify-content: center; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <h2>Manage Categories</h2>
    
    <div class="action-bar">
        <a href="manage_services.php" class="btn back-btn">&larr; Manage Services</a>
        <a href="add_category.php" class="btn add-btn">+ Add New Category</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Image</th>
                    <th>SEO Title</th>
                    <th>SEO Description</th>
                    <th>SEO Keywords</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $categories->fetch_assoc()) { ?>
                    <tr>
                        <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($row['name']) ?></td>
                        <td>
                            <?php if($row['image']): ?>
                                <img src="<?= htmlspecialchars($row['image']) ?>" alt="Category Image">
                            <?php else: ?>
                                <span style="color:#9ca3af; font-size:12px;">No Img</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#4b5563; font-size:13px;"><?= htmlspecialchars($row['seo_title']) ?></td>
                        <td style="color:#6b7280; font-size:13px; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($row['seo_description']) ?></td>
                        <td style="color:#6b7280; font-size:13px; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($row['seo_keywords']) ?></td>
                        <td>
                            <a href="edit_category.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">Edit</a>
                            <a href="delete_category.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>