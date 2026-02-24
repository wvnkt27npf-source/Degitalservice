<?php
session_start();
include './config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id']);
    $download_link = filter_var($_POST['download_link'], FILTER_SANITIZE_URL);

    // Update the download link for the service
    $stmt = $conn->prepare("UPDATE services SET download_link = ? WHERE id = ?");
    $stmt->bind_param("si", $download_link, $service_id);

    if ($stmt->execute()) {
        // Redirect to the admin_set_download_link.php page after successful update
        header("Location: admin_set_download_link.php?message=success");
        exit;
    } else {
        // If update fails, you can display an error message or handle it accordingly
        header("Location: admin_set_download_link.php?message=error");
        exit;
    }
}
?>
