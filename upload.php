<?php
session_start();
include './config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
    $order_id = $_POST["order_id"];
    $upload_dir = "uploads/";

    // Ensure the uploads directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Capture file details
    $file_name = basename($_FILES["file"]["name"]);
    $file_path = $upload_dir . $file_name;

    // Debugging: Print file info
    echo "File Name: " . $file_name . "<br>";
    echo "Temp Path: " . $_FILES["file"]["tmp_name"] . "<br>";
    echo "File Type: " . $_FILES["file"]["type"] . "<br>";
    echo "File Size: " . $_FILES["file"]["size"] . "<br>";

    // Move file and check for errors
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $file_path)) {
        echo "File uploaded successfully to: " . $file_path . "<br>";

        // Update database
        $stmt = $conn->prepare("UPDATE orders SET file = ? WHERE id = ?");
        $stmt->bind_param("si", $file_name, $order_id);
        if ($stmt->execute()) {
            echo "Database updated successfully.<br>";
            header("Location: admin_orders.php?success=File uploaded!");
            exit();
        } else {
            echo "Database error: " . $conn->error . "<br>";
        }
    } else {
        echo "Error moving file to uploads folder.<br>";
    }
} else {
    echo "Invalid request.";
}
?>
