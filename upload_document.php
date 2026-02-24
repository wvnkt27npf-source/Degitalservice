<?php
include './config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $docId = $_POST['doc_id'];
    $document = $_FILES['document'];

    // Ensure there is no upload error
    if ($document['error'] === 0) {
        $targetDir = "uploads/user_documents/";

        // Get file extension
        $fileExtension = pathinfo($document['name'], PATHINFO_EXTENSION);

        // Fetch document name from the required_documents table based on the document ID
        $stmt = $conn->prepare("SELECT doc_name FROM required_documents WHERE id = ?");
        $stmt->bind_param("i", $docId);
        $stmt->execute();
        $stmt->bind_result($docName);
        $stmt->fetch();
        $stmt->close();

        // Get the username
        $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($username);
        $stmt->fetch();
        $stmt->close();

        // Construct new file name: username_documentname.extension
        $newFileName = $username . '_' . $docName . '.' . $fileExtension;

        // Combine the target directory with the new file name
        $targetFile = $targetDir . basename($newFileName);

        // Check if file already exists
        if (!file_exists($targetFile)) {
            // Move the file to the target directory with the new name
            if (move_uploaded_file($document['tmp_name'], $targetFile)) {
                // Insert document into database with 'Pending' status
                $stmt = $conn->prepare("INSERT INTO user_documents (user_id, doc_id, file_name, file_path, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->bind_param("iiss", $_SESSION['user_id'], $docId, $newFileName, $targetFile);
                $stmt->execute();
                $stmt->close();

                echo "File uploaded successfully.";
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "File already exists.";
        }
    } else {
        echo "No file was uploaded or there was an error.";
    }
}
?>
