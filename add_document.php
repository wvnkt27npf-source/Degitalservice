<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include './config.php';
include 'header.php';

// Add new document
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['document_name'])) {
    $document_name = $_POST['document_name'];

    $insert_query = "INSERT INTO required_documents (document_name) VALUES (?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("s", $document_name);
    $stmt->execute();

    echo "<script>alert('Document added successfully!');</script>";
}

// Edit document
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_document_name']) && isset($_POST['document_id'])) {
    $document_name = $_POST['edit_document_name'];
    $document_id = $_POST['document_id'];

    $update_query = "UPDATE required_documents SET document_name = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $document_name, $document_id);
    $stmt->execute();

    echo "<script>alert('Document updated successfully!');</script>";
}

// Delete document
if (isset($_GET['delete_document_id'])) {
    $document_id = $_GET['delete_document_id'];

    // First, delete the related assignments in the service_document_assignments table
    $delete_assignments_query = "DELETE FROM service_document_assignments WHERE document_id = ?";
    $stmt = $conn->prepare($delete_assignments_query);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    // Now, delete the document from the required_documents table
    $delete_query = "DELETE FROM required_documents WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();

    echo "<script>alert('Document and related assignments deleted successfully!');</script>";
}


// Fetch all documents
$documents_query = "SELECT * FROM required_documents";
$documents_result = $conn->query($documents_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Required Documents</title>
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
            max-width: 900px;
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
        h3 { font-size: 18px; margin-top: 0; color: #374151; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 15px; }

        /* Links & Buttons */
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4f46e5; /* Indigo */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-bottom: 25px;
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
            margin-bottom: 30px;
        }

        label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background-color: white;
            font-size: 14px;
            font-family: inherit;
            margin-bottom: 15px;
        }
        input[type="text"]:focus { outline: 2px solid #4f46e5; border-color: transparent; }

        button[type="submit"] {
            padding: 10px 20px;
            background-color: #10b981; /* Green */
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            width: auto;
        }
        button[type="submit"]:hover { background-color: #059669; transform: translateY(-1px); }

        /* Table Styles */
        .table-wrapper { overflow-x: auto; border-radius: 8px; border: 1px solid #e5e7eb; }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            min-width: 600px;
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
        .edit-link, .delete-link {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-right: 5px;
            transition: 0.2s;
        }

        .edit-link {
            background-color: #f59e0b; /* Amber */
            color: white;
        }
        .edit-link:hover { background-color: #d97706; }

        .delete-link {
            background-color: #ef4444; /* Red */
            color: white;
        }
        .delete-link:hover { background-color: #dc2626; }

        hr { margin: 40px 0; border: 0; border-top: 1px solid #e5e7eb; }

        @media (max-width: 768px) {
            .container { padding: 15px; width: 100%; margin: 10px auto; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Manage Required Documents</h2>
    <a href="service_documents.php" class="back-btn">&larr; Back to Assign Documents</a>

    <form method="POST">
        <h3>Add a New Document</h3>
        <label for="document_name">Document Name:</label>
        <input type="text" name="document_name" id="document_name" placeholder="Enter document name (e.g., Aadhar Card)" required>
        <button type="submit">Add Document</button>
    </form>

    <?php
    if (isset($_GET['edit_document_id'])) {
        $edit_document_id = $_GET['edit_document_id'];
        $edit_query = "SELECT * FROM required_documents WHERE id = ?";
        $stmt = $conn->prepare($edit_query);
        $stmt->bind_param("i", $edit_document_id);
        $stmt->execute();
        $edit_result = $stmt->get_result();
        $edit_doc = $edit_result->fetch_assoc();
        ?>
        <form method="POST" style="border-left: 4px solid #f59e0b;">
            <h3>Edit Document</h3>
            <input type="hidden" name="document_id" value="<?= $edit_doc['id'] ?>">
            <label for="edit_document_name">New Document Name:</label>
            <input type="text" name="edit_document_name" id="edit_document_name" value="<?= htmlspecialchars($edit_doc['document_name']) ?>" required>
            <button type="submit" style="background-color: #f59e0b;">Update Document</button>
            <a href="add_document.php" style="margin-left: 10px; text-decoration: none; color: #6b7280; font-size: 14px;">Cancel</a>
        </form>
        <?php
    }
    ?>

    <hr>

    <h3>Existing Documents List</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Document Name</th>
                    <th style="width: 180px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($documents_result->num_rows > 0): ?>
                    <?php while ($doc = $documents_result->fetch_assoc()) { ?>
                        <tr>
                            <td style="font-weight: 500;"><?= htmlspecialchars($doc['document_name']) ?></td>
                            <td style="text-align: center;">
                                <a href="add_document.php?edit_document_id=<?= $doc['id'] ?>" class="edit-link">Edit</a>
                                <a href="add_document.php?delete_document_id=<?= $doc['id'] ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this document?');">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align:center; color:#6b7280;">No documents found. Add one above.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php include 'footer.php'; ?>