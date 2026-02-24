<?php
session_start();
include './config.php';
include './header.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$order_id = $_GET['order_id']; // Get the order ID (passed in URL)
$service_id = $_GET['service_id']; // Get the selected service ID

// Fetch the custom download link for the service
$stmt = $conn->prepare("SELECT download_link FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $service = $result->fetch_assoc();
    $download_link = $service['download_link'];

    // Update the order status to "Completed" and set the download link
    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', download_link = ? WHERE id = ?");
    $stmt->bind_param("si", $download_link, $order_id);
    $stmt->execute();

    echo "Order marked as completed, and download link assigned.";
} else {
    echo "Service not found.";
}
?>
