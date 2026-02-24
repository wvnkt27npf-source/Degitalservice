<?php
session_start();
include './config.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get the updated order from the POST data
$data = json_decode(file_get_contents("php://input"), true);
$order = $data['order'];

// Loop through each service and update its position in the database
foreach ($order as $index => $item) {
    $service_id = $item['id'];
    $new_order = $item['order'];

    // Update the service order in the database
    $stmt = $conn->prepare("UPDATE services SET `order` = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_order, $service_id);
    $stmt->execute();
}

echo json_encode(['status' => 'success']);
?>
