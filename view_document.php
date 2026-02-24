<?php
session_start();
include './config.php';
include './header.php';

if (!isset($_GET['doc_id'])) {
    echo "<div class='error-msg'>No document specified.</div>";
    exit();
}

$docId = intval($_GET['doc_id']);
$returnUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Fetch document details
$stmt = $conn->prepare("SELECT file_path, file_name FROM user_documents WHERE id = ?");
$stmt->bind_param("i", $docId);
$stmt->execute();
$stmt->bind_result($filePath, $fileName);
$stmt->fetch();
$stmt->close();

if (!$filePath || !file_exists($filePath)) {
    echo "<div class='container'><h3 style='color:red; text-align:center;'>Document not found or file missing.</h3></div>";
    exit();
}

$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Document - <?= htmlspecialchars($fileName); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; color: #1f2937; margin: 0; padding-bottom: 50px; }
        
        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header-bar h2 {
            margin: 0;
            font-size: 20px;
            color: #111827;
            word-break: break-all;
        }

        .btn {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .btn-back:hover { background-color: #e5e7eb; }

        .btn-download {
            background-color: #2563eb;
            color: white;
        }
        .btn-download:hover { background-color: #1d4ed8; }

        .preview-box {
            text-align: center;
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            border: 1px dashed #d1d5db;
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .unsupported {
            padding: 40px;
            font-size: 16px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-bar">
        <?php 
            $backLink = ($returnUserId > 0) ? "Manage_Documents.php?user_id=$returnUserId" : "Manage_Documents.php"; 
        ?>
        <a href="<?= $backLink; ?>" class="btn btn-back">&larr; Back</a>
        
        <h2><?= htmlspecialchars($fileName); ?></h2>
        
        <a href="<?= htmlspecialchars($filePath); ?>" class="btn btn-download" download>
            ⬇ Download
        </a>
    </div>

    <div class="preview-box">
        <?php if (in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])): ?>
            <img src="<?= htmlspecialchars($filePath); ?>" alt="Document Preview">
        <?php elseif ($fileExtension === 'pdf'): ?>
            <object data="<?= htmlspecialchars($filePath); ?>" type="application/pdf" width="100%" height="800px">
                <p>Your browser does not support PDFs. <a href="<?= htmlspecialchars($filePath); ?>">Download the PDF</a>.</p>
            </object>
        <?php else: ?>
            <div class="unsupported">
                <p>🚫 Preview not available for this file type (<?= strtoupper($fileExtension); ?>).</p>
                <p>Please use the download button above to view the file.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>