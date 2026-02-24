<?php
session_start();
include './config.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Export (CSV)
if (isset($_GET['export'])) {
    // Fetch Services with Categories
    $services = $conn->query("SELECT services.*, categories.name AS category_name FROM services 
                              JOIN categories ON services.category_id = categories.id
                              ORDER BY services.`order` ASC");
    
    // Set CSV Headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="services_export.csv"');

    $output = fopen('php://output', 'w');
    // Column headers for CSV
    fputcsv($output, ['ID', 'Service Name', 'Category', 'Price', 'SEO Title', 'SEO Description', 'SEO Keywords', 'Image Path']);

    // Output each row of data
    while ($row = $services->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category_name'],
            $row['price'],
            $row['seo_title'],
            $row['seo_description'],
            $row['seo_keywords'],
            $row['image']
        ]);
    }
    fclose($output);
    exit; // Stop further execution after export
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

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
            max-width: 1300px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
            color: #111827;
        }

        /* Control Panel (Import/Export/Add) */
        .bulk-options {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .import-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .import-form input[type="file"] {
            font-size: 13px;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
        }

        /* Action Buttons (Top) */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
            border: none;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .btn:hover { transform: translateY(-1px); }

        .btn-import { background-color: #0ea5e9; } /* Sky Blue */
        .btn-import:hover { background-color: #0284c7; }

        .btn-export { background-color: #10b981; } /* Green */
        .btn-export:hover { background-color: #059669; }

        .btn-add { background-color: #4f46e5; } /* Indigo */
        .btn-add:hover { background-color: #4338ca; }
        
        .btn-cat { background-color: #6366f1; } /* Violet */
        .btn-cat:hover { background-color: #4f46e5; }

        /* Search Bar */
        #search {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            background-color: #fff;
        }
        #search:focus { outline: 2px solid #4f46e5; border-color: transparent; }

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
            min-width: 900px;
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
            cursor: pointer;
        }

        tr:hover td { background-color: #f9fafb; }

        /* Drag Handle */
        .drag-handle {
            cursor: grab;
            text-align: center;
            color: #9ca3af;
            font-size: 18px;
        }
        .drag-handle:active { cursor: grabbing; color: #4b5563; }

        /* Row Action Buttons */
        .action-links { display: flex; gap: 5px; }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            color: white;
            transition: 0.2s;
        }

        .edit-btn { background-color: #f59e0b; } /* Amber */
        .edit-btn:hover { background-color: #d97706; }

        .delete-btn { background-color: #ef4444; } /* Red */
        .delete-btn:hover { background-color: #dc2626; }

        .demo-btn { background-color: #3b82f6; } /* Blue */
        .demo-btn:hover { background-color: #2563eb; }

        img { border-radius: 4px; border: 1px solid #e5e7eb; }

        @media (max-width: 768px) {
            .container { padding: 15px; margin: 15px auto; }
            .bulk-options { flex-direction: column; align-items: stretch; }
            .import-form { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <h1>Manage Services</h1>
    
    <div class="bulk-options">
        <form action="bulk_import.php" method="POST" enctype="multipart/form-data" class="import-form">
            <input type="file" name="import_file" id="import_file" accept=".csv" required>
            <button type="submit" name="import" class="btn btn-import">Import CSV</button>
        </form>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="manage_services.php?export=true" class="btn btn-export">Export All (CSV)</a>
            <a href="add_service.php" class="btn btn-add">+ Add Service</a>
            <a href="manage_categories.php" class="btn btn-cat">Manage Categories</a>
        </div>
    </div>

    <input type="text" id="search" onkeyup="searchTable()" placeholder="Type to search services...">
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">Sort</th>
                    <th>ID</th>
                    <th>Service Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>SEO Title</th>
                    <th>Image</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch Services for table display, ordered by the 'order' column
                $services = $conn->query("SELECT services.*, categories.name AS category_name FROM services 
                                          JOIN categories ON services.category_id = categories.id
                                          ORDER BY services.`order` ASC");

                while ($row = $services->fetch_assoc()) { ?>
                <tr data-id="<?= $row['id'] ?>"> <td class="drag-handle">&#x2630;</td>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td style="font-weight: 500; color: #111827;"><?= htmlspecialchars($row['name']) ?></td>
                    <td><span style="background:#eef2ff; color:#4f46e5; padding:2px 6px; border-radius:4px; font-size:12px; border:1px solid #c7d2fe;"><?= htmlspecialchars($row['category_name']) ?></span></td>
                    <td style="font-weight:600; color:#059669;">₹<?= htmlspecialchars($row['price']) ?></td>
                    <td style="font-size:12px; color:#6b7280; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($row['seo_title']) ?></td>
                    <td>
                        <?php if($row['image']): ?>
                            <img src="<?= htmlspecialchars($row['image']) ?>" width="50" height="50" style="object-fit:cover;">
                        <?php else: ?>
                            <span style="color:#9ca3af; font-size:12px;">No Img</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-links">
                            <a href="edit_service.php?id=<?= $row['id'] ?>" class="action-btn edit-btn">Edit</a>
                            <a href="delete_service.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php if (!empty($row['demo_link'])) { ?>
                                <a href="<?= htmlspecialchars($row['demo_link']) ?>" target="_blank" class="action-btn demo-btn">Demo</a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize SortableJS for table row sorting
    document.addEventListener('DOMContentLoaded', function() {
        var tableBody = document.querySelector('table tbody');

        var sortable = new Sortable(tableBody, {
            handle: '.drag-handle', 
            animation: 150,
            onEnd: function(evt) {
                updateOrder(evt);
            }
        });
    });

    // Function to update the order after dragging and dropping
    function updateOrder(evt) {
        var rows = document.querySelectorAll('table tbody tr');
        var order = [];
        
        rows.forEach(function(row, index) {
            var serviceId = row.getAttribute('data-id'); 
            order.push({ id: serviceId, order: index + 1 });
        });

        // Send order via AJAX to the server
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "update_service_order.php", true);
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send(JSON.stringify({ order: order }));
    }

    function searchTable() {
        let input = document.getElementById("search").value.toLowerCase();
        let rows = document.querySelectorAll("table tbody tr");
        rows.forEach(row => {
            // Allow searching by Name, Category, Price, or SEO Title
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(input) ? "" : "none";
        });
    }
</script>

</body>
</html>

<?php include 'footer.php'; ?>