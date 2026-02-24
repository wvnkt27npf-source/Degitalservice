<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if user_id is set in session
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo 'User ID is missing or invalid';
    exit;
}

// Include database config file
include 'config.php';

if (isset($_POST['order_id'], $_POST['feedback'], $_POST['feedback_score'])) {
    $orderId = $_POST['order_id'];
    $feedback = htmlspecialchars($_POST['feedback']);
    $feedbackScore = (int) $_POST['feedback_score'];
    $userId = $_SESSION['user_id']; // User ID from the session

    // Check if the parameters are valid
    if (empty($feedback) || !in_array($feedbackScore, [1, 2, 3, 4, 5])) {
        echo 'Invalid feedback or score';
        exit;
    }

    // Step 1: Fetch the service_id from the orders table based on the order_id
    $stmt = $conn->prepare("SELECT service_id FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $serviceId = $row['service_id']; // Get the service_id from the result

        // Step 2: Insert feedback into the feedbacks table
        $stmt = $conn->prepare("INSERT INTO feedbacks (order_id, user_id, service_id, feedback, feedback_score) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisi", $orderId, $userId, $serviceId, $feedback, $feedbackScore);

        if ($stmt->execute()) {
            echo 'Feedback submitted successfully';
        } else {
            echo 'Error: ' . $stmt->error; // Show detailed error if execution fails
        }

        $stmt->close();
    } else {
        echo 'Order not found or invalid user for this order';
    }

    $stmt->close();
} else {
    echo 'Missing required parameters';
}
?>
