<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

include './config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $uploaded_file = null;

    // Handle file upload if provided
    if (!empty($_FILES['file']['name'])) {
        $uploaded_file = $_FILES['file']['name'];
        move_uploaded_file($_FILES['file']['tmp_name'], "../uploads/$uploaded_file");
    }

    // Update request status and file
    $stmt = $conn->prepare("UPDATE custom_requests SET status = ?, uploaded_file = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $uploaded_file, $request_id);

    if ($stmt->execute()) {
        echo "<p>Request updated successfully!</p>";
    } else {
        echo "<p>Error updating request.</p>";
    }
    $stmt->close();
    header("Location: admin.php");
    exit();
}
?>
