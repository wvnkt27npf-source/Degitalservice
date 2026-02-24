<?php
session_start();
include './config.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if (isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // 1. Generate Unique Key (Format: KEY-XXXX-XXXX-XXXX)
    $random_part = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
    $key = 'KEY-' . $random_part;
    
    // 2. Update USERS table
    $stmt = $conn->prepare("UPDATE users SET license_key = ? WHERE id = ?");
    $stmt->bind_param("si", $key, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'key' => $key]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Update Failed']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No User ID']);
}
?>