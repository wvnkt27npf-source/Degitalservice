<?php
session_start();
include './config.php';

// 1. Check karein ki user logged-in hai ya nahi
$loggedInUserRoles = ['user', 'client', 'customer'];
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    // Agar login nahi hai, toh error bhej kar ruk jaayein
    http_response_code(403); // Forbidden (Access nahi hai)
    echo "Authentication failed. Please log in again.";
    exit();
}

// 2. Check karein ki yeh POST request hai aur order_id bhi hai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    
    $order_id = $_POST['order_id'];
    $cancel_reason = trim($_POST['cancel_reason']);
    $user_id = $_SESSION['user_id'];

    // 3. Database ko UPDATE karein
    // Sirf 'Pending' status waale orders hi cancel karein jo iss user ke hain
    $stmt = $conn->prepare(
        "UPDATE orders SET status = 'Cancelled By User', cancel_reason = ? 
         WHERE id = ? AND user_id = ? AND status = 'Pending'"
    );
    
    // Agar user ne reason nahi likha, toh ek default message daalein
    if (empty($cancel_reason)) {
        $cancel_reason = "No reason provided.";
    }
    
    $stmt->bind_param("sii", $cancel_reason, $order_id, $user_id);
    
    if ($stmt->execute()) {
        // 4. Check karein ki order update hua ya nahi
        if ($stmt->affected_rows > 0) {
            // Safal! (1 ya zyada rows update hui)
            http_response_code(200); // OK
            echo "Order cancelled successfully.";
        } else {
            // Order update nahi hua
            // (Ho sakta hai order 'Pending' na ho, ya order ID galat ho)
            http_response_code(400); // Bad Request
            echo "Could not cancel order. It may have already been processed or does not belong to you.";
        }
    } else {
        // Database query hi fail ho gayi
        http_response_code(500); // Internal Server Error
        echo "A database error occurred.";
    }
    $stmt->close();
    
} else {
    // Agar koi POST request ke bina is page ko khole
    http_response_code(405); // Method Not Allowed
    echo "Invalid request method.";
}
?>