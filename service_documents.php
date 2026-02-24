<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include './config.php';
include 'header.php';

// Add document to service
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['service_id']) && isset($_POST['document_ids'])) {
        $service_id = $_POST['service_id'];
        $document_ids = $_POST['document_ids'];  // Array of selected document IDs

        // Step 1: Get already assigned documents for the selected service
        $assigned_documents_query = "SELECT document_id FROM service_document_assignments WHERE service_id = ?";
        $stmt = $conn->prepare($assigned_documents_query);
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $assigned_documents_result = $stmt->get_result();

        // Step 2: Get all assigned document ids in an array
        $assigned_document_ids = [];
        while ($row = $assigned_documents_result->fetch_assoc()) {
            $assigned_document_ids[] = $row['document_id'];
        }

        // Step 3: Insert only new documents that are not already assigned
        $insert_query = "INSERT INTO service_document_assignments (service_id, document_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);

        foreach ($document_ids as $document_id) {
            // Only insert if the document is not already assigned
            if (!in_array($document_id, $assigned_document_ids)) {
                $stmt->bind_param("ii", $service_id, $document_id);
                $stmt->execute();
            }
        }

        // Step 4: Remove any previously assigned documents that are no longer selected
        $delete_query = "DELETE FROM service_document_assignments WHERE service_id = ? AND document_id NOT IN (" . implode(',', array_fill(0, count($document_ids), '?')) . ")";
        $stmt = $conn->prepare($delete_query);

        // Merge service_id with document_ids array to pass all arguments in the correct order
        $all_params = array_merge([$service_id], $document_ids);
        $stmt->bind_param(str_repeat("i", count($all_params)), ...$all_params);
        $stmt->execute();

        echo "<script>alert('Documents updated for the service!');</script>";
    } else {
        // Handle error if required fields are not set
        echo "<script>alert('Error: Missing Service ID or Document IDs.');</script>";
    }
}

// Delete all document assignments for a service
if (isset($_GET['delete_service_id'])) {
    $service_id = $_GET['delete_service_id'];

    // Step 1: Delete all document assignments for the service
    $delete_query = "DELETE FROM service_document_assignments WHERE service_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();

    echo "<script>alert('All documents for this service have been deleted!');</script>";
}

// Delete document from service
if (isset($_GET['delete_assignment_id'])) {
    $assignment_id = $_GET['delete_assignment_id'];
    $delete_query = "DELETE FROM service_document_assignments WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();

    echo "<script>alert('Document assignment deleted successfully!');</script>";
}

// Fetch all services and documents
$services_query = "SELECT * FROM services";
$services_result = $conn->query($services_query);

$documents_query = "SELECT * FROM required_documents";
$documents_result = $conn->query($documents_query);

// Fetch document assignments for services
$assignments_query = "SELECT a.id AS assignment_id, s.name AS service_name, GROUP_CONCAT(d.document_name ORDER BY d.document_name ASC SEPARATOR ', ') AS documents, a.service_id
                      FROM service_document_assignments a
                      JOIN services s ON a.service_id = s.id
                      JOIN required_documents d ON a.document_id = d.id
                      GROUP BY a.service_id";
$assignments_result = $conn->query($assignments_query);

// Fetch specific service's document assignments for edit
if (isset($_GET['edit_service_id'])) {
    $edit_service_id = $_GET['edit_service_id'];
    $service_documents_query = "SELECT document_id FROM service_document_assignments WHERE service_id = ?";
    $stmt = $conn->prepare($service_documents_query);
    $stmt->bind_param("i", $edit_service_id);
    $stmt->execute();
    $service_documents_result = $stmt->get_result();
    $assigned_document_ids = [];
    while ($row = $service_documents_result->fetch_assoc()) {
        $assigned_document_ids[] = $row['document_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Documents to Services</title>
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
            max-width: 1000px;
            width: 95%;
            margin: 30px auto;
            padding: 25px;
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }

        h2, h3 {
            color: #111827;
            font-weight: 600;
            margin-bottom: 20px;
        }
        h2 { text-align: center; font-size: 26px; }
        h3 { font-size: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-top: 30px; }

        /* Links & Buttons */
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4f46e5; /* Indigo */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 20px;
            transition: 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .back-btn:hover { background-color: #4338ca; transform: translateY(-1px); }

        /* Form Styles */
        form {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .form-group { margin-bottom: 20px; }
        
        label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background-color: white;
            font-size: 14px;
            font-family: inherit;
        }

        /* Checkbox Grid Layout */
        .checkbox-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 10px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            padding: 8px;
            border: 1px solid #f3f4f6;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .checkbox-group:hover { background-color: #f3f4f6; }

        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin-right: 10px;
            accent-color: #4f46e5;
            cursor: pointer;
        }
        
        .checkbox-group label { margin: 0; cursor: pointer; font-size: 14px; width: 100%; }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #10b981; /* Green */
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            margin-top: 10px;
        }
        button[type="submit"]:hover { background-color: #059669; transform: translateY(-1px); }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #d1d5db; /* Full Border */
            font-size: 14px;
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

        /* Action Buttons in Table */
        .edit-btn, .delete-btn {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin: 2px;
            transition: 0.2s;
        }

        .edit-btn {
            background-color: #f59e0b; /* Amber */
            color: white;
        }
        .edit-btn:hover { background-color: #d97706; }

        .delete-btn {
            background-color: #ef4444; /* Red */
            color: white;
        }
        .delete-btn:hover { background-color: #dc2626; }

        hr { margin: 40px 0; border: 0; border-top: 1px solid #e5e7eb; }

        @media (max-width: 768px) {
            .checkbox-container { grid-template-columns: 1fr; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
<div class="container">
    <h2>Assign Documents to Services</h2>

    <a href="add_document.php" class="back-btn">+ Add New Documents</a>

    <h3>Assign Documents</h3>
    <form method="POST">
        <div class="form-group">
            <label for="service_id">Select Service:</label>
            <select name="service_id" id="service_id" required>
                <?php while ($row = $services_result->fetch_assoc()) { ?>
                    <option value="<?= $row['id'] ?>" <?= isset($edit_service_id) && $edit_service_id == $row['id'] ? 'selected' : '' ?>><?= $row['name'] ?></option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Select Documents:</label>
            <div class="checkbox-container">
                <?php while ($doc = $documents_result->fetch_assoc()) { ?>
                    <div class="checkbox-group">
                        <input type="checkbox" name="document_ids[]" value="<?= $doc['id'] ?>" id="doc_<?= $doc['id'] ?>"
                            <?= isset($assigned_document_ids) && in_array($doc['id'], $assigned_document_ids) ? 'checked' : '' ?>>
                        <label for="doc_<?= $doc['id'] ?>"><?= htmlspecialchars($doc['document_name']) ?></label>
                    </div>
                <?php } ?>
            </div>
        </div>

        <button type="submit">Assign Documents</button>
    </form>

    <hr>

    <h3>Assigned Documents to Services</h3>
    <div style="overflow-x:auto;">
        <table class="assignments-table">
            <thead>
                <tr>
                    <th>Service Name</th>
                    <th>Assigned Documents</th>
                    <th style="width: 200px; text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assignments_result->num_rows > 0): ?>
                    <?php while ($assignment = $assignments_result->fetch_assoc()) { ?>
                        <tr>
                            <td style="font-weight: 500;"><?= htmlspecialchars($assignment['service_name']) ?></td>
                            <td><?= htmlspecialchars($assignment['documents']) ?></td>
                            <td style="text-align:center;">
                                <a href="service_documents.php?edit_service_id=<?= $assignment['service_id'] ?>" class="edit-btn">Edit</a>
                                <a href="service_documents.php?delete_service_id=<?= $assignment['service_id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete all documents for this service?');">Delete All</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#6b7280;">No documents assigned to any service yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>