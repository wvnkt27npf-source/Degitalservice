<?php
session_start();
include './config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];

    // Prepare statement to update the download_link to NULL for the specific service
    $stmt = $conn->prepare("UPDATE services SET download_link = NULL WHERE id = ?");
    $stmt->bind_param('i', $service_id);

    if ($stmt->execute()) {
        // Redirect to the page with a success message
        header("Location: admin_set_download_link.php?success=1");
    } else {
        echo "Error deleting download link.";
    }

    $stmt->close();
}
?>
