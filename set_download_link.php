<?php
session_start();
include './config.php';

// Ensure the user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $service_id = $_POST['service_id'];
    $download_link = $_POST['download_link'];

    // Validate the input data (ensure valid URL)
    if (filter_var($download_link, FILTER_VALIDATE_URL)) {
        // Prepare and execute the update query
        $stmt = $conn->prepare("UPDATE services SET download_link = ? WHERE id = ?");
        $stmt->bind_param("si", $download_link, $service_id);

        if ($stmt->execute()) {
            // After successful update, redirect to admin_set_download_link.php with success flag
            header("Location: admin_set_download_link.php?success=1");
            exit;
        } else {
            // Handle errors (if the update failed)
            echo "Error updating the download link. Please try again.";
        }
    } else {
        // Handle invalid URL case
        echo "Please enter a valid download link.";
    }
}
?>
