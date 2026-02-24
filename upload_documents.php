<?php
session_start();
include './config.php';

// Authentication Check
$loggedInUserRoles = ['user', 'client', 'customer'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php");
    exit();
}

include 'header.php';

$userId = $_SESSION['user_id'];
$serviceId = isset($_GET['service_id']) ? $_GET['service_id'] : null;

// If service_id is missing, redirect
if (!$serviceId) {
    header('Location: user_orders.php'); 
    exit();
}

// Fetch required documents
$stmt = $conn->prepare("SELECT rd.id AS document_id, rd.document_name FROM required_documents rd
                        JOIN service_document_assignments sda ON rd.id = sda.document_id
                        WHERE sda.service_id = ?");
$stmt->bind_param("i", $serviceId);
$stmt->execute();
$result = $stmt->get_result();
$documents = [];
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}
$stmt->close();

// Fetch uploaded documents status
$uploadedDocuments = [];
$stmt = $conn->prepare("SELECT doc_id, status FROM user_documents WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$uploadedResult = $stmt->get_result();
while ($row = $uploadedResult->fetch_assoc()) {
    $uploadedDocuments[$row['doc_id']] = $row['status'];
}
$stmt->close();

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $docId = $_POST['doc_id'];
    $document = $_FILES['document'];

    if ($document['error'] === 0) {
        $targetDir = "uploads/user_documents/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileExtension = pathinfo($document['name'], PATHINFO_EXTENSION);

        // Get doc name
        $stmt = $conn->prepare("SELECT document_name FROM required_documents WHERE id = ?");
        $stmt->bind_param("i", $docId);
        $stmt->execute();
        $stmt->bind_result($docName);
        $stmt->fetch();
        $stmt->close();

        // Get username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();

        $newFileName = $username . '_' . preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '_', $docName)) . '.' . $fileExtension;
        $targetFile = $targetDir . basename($newFileName);

        // Check if exists (Update vs Insert)
        $checkStmt = $conn->prepare("SELECT id FROM user_documents WHERE user_id = ? AND doc_id = ?");
        $checkStmt->bind_param("ii", $userId, $docId);
        $checkStmt->execute();
        $checkStmt->store_result();
        $exists = $checkStmt->num_rows > 0;
        $checkStmt->close();

        if (move_uploaded_file($document['tmp_name'], $targetFile)) {
            if ($exists) {
                // Update
                $stmt = $conn->prepare("UPDATE user_documents SET file_name = ?, file_path = ?, status = 'Pending', upload_date = CURRENT_TIMESTAMP WHERE user_id = ? AND doc_id = ?");
                $stmt->bind_param("ssii", $newFileName, $targetFile, $userId, $docId);
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO user_documents (user_id, doc_id, file_name, file_path, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("iiss", $userId, $docId, $newFileName, $targetFile);
            }
            $stmt->execute();
            $stmt->close();
            
            header('Location: upload_documents.php?service_id=' . $serviceId . '&success=1');
            exit();
        } else {
            $message = "Error uploading file.";
            $msg_type = "error";
        }
    } else {
        $message = "No file selected or upload error.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Reset */
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
            margin: 40px auto;
            padding: 30px;
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

        /* Back Button */
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #6b7280;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .back-btn:hover { background-color: #4b5563; transform: translateY(-1px); }

        /* Message */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
        }
        .message.success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .message.error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

        /* Grid Layout */
        .upload-section {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* Document Card */
        .document-item {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .document-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-color: #d1d5db;
        }

        .document-item h3 {
            font-size: 18px;
            color: #111827;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* File Input */
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px dashed #d1d5db;
            border-radius: 8px;
            background-color: #f9fafb;
            font-size: 13px;
            color: #4b5563;
            cursor: pointer;
        }
        input[type="file"]:hover { background-color: #f3f4f6; border-color: #9ca3af; }

        /* Upload Button */
        .upload-btn {
            width: 100%;
            padding: 12px;
            background-color: #4f46e5; /* Indigo */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .upload-btn:hover { background-color: #4338ca; transform: translateY(-1px); }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .status-pending { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .status-approved { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .container { padding: 20px; margin: 20px auto; }
            .upload-section { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <a href="user_orders.php" class="back-btn">&larr; Back to My Services</a>

    <h2>Upload Your Documents</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="message success">File uploaded successfully!</div>
    <?php elseif (isset($message)): ?>
        <div class="message <?php echo $msg_type; ?>"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="upload-section">
        <?php foreach ($documents as $document): ?>
            <div class="document-item">
                <h3><?= htmlspecialchars($document['document_name']); ?></h3>

                <?php if (isset($uploadedDocuments[$document['document_id']])): ?>
                    <?php 
                        $status = $uploadedDocuments[$document['document_id']];
                        $badgeClass = 'status-' . strtolower($status);
                    ?>
                    
                    <div>
                        <span class="status-badge <?= $badgeClass ?>"><?= $status; ?></span>
                    </div>

                    <?php if ($status === 'Rejected'): ?>
                        <form method="POST" enctype="multipart/form-data">
                            <p style="font-size:13px; color:#dc2626; margin-bottom:10px;">Please re-upload correct file.</p>
                            <input type="file" name="document" required>
                            <input type="hidden" name="doc_id" value="<?= $document['document_id']; ?>">
                            <button type="submit" name="upload_document" class="upload-btn" style="background-color:#ef4444;">Re-upload</button>
                        </form>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="document" required>
                        <input type="hidden" name="doc_id" value="<?= $document['document_id']; ?>">
                        <button type="submit" name="upload_document" class="upload-btn">Upload</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (empty($documents)): ?>
        <p style="text-align:center; margin-top:40px; color:#6b7280;">No documents required for this service.</p>
    <?php endif; ?>
</div>

</body>
</html>