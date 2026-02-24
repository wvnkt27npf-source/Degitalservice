<?php
session_start();
include './config.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if the 'id' parameter is set
if (isset($_GET['id'])) {
    $orderId = intval($_GET['id']); // Sanitize input

    // 1. Fetch the user ID before deleting (So we know where to return)
    $stmt = $conn->prepare("SELECT user_id, price, status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($userId, $orderPrice, $orderStatus);
    $stmt->fetch();
    $stmt->close();

    // If the order is found
    if ($userId) {
        // 2. Delete the order from the orders table
        $deleteStmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $deleteStmt->bind_param("i", $orderId);
        $deleteStmt->execute();
        $deleteStmt->close();

        // 3. Recalculate lifetime purchases for the user (only completed orders)
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET lifetime_purchases = (
                SELECT IFNULL(SUM(price), 0) 
                FROM orders 
                WHERE user_id = ? AND status = 'Completed'
            )
            WHERE id = ?
        ");
        $updateStmt->bind_param("ii", $userId, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        // --- THE FIX IS HERE ---
        // Redirect back to the Specific User's Detail View
        $redirectUrl = "admin_orders.php?user_id=" . $userId;

        echo "<script>
                alert('Order deleted successfully!'); 
                window.location.href='" . $redirectUrl . "';
              </script>";
        exit();

    } else {
        // If the order is not found
        echo "<script>alert('Order not found!'); window.location.href='admin_orders.php';</script>";
        exit();
    }
} else {
    // If the ID is not set
    echo "<script>alert('Invalid order ID!'); window.location.href='admin_orders.php';</script>";
    exit();
}
?>