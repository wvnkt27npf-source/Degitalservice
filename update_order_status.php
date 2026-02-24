<?php
include './config.php';

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // If order is marked as 'completed', update completed_date
    if ($status == 'completed') {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, completed_date = NOW() WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    }

    if ($stmt) {
        $stmt->bind_param("si", $status, $order_id);
        if ($stmt->execute()) {
            echo "Order updated successfully!";
        } else {
            echo "Failed to update order.";
        }
    } else {
        echo "Query preparation failed!";
    }
}
?>
