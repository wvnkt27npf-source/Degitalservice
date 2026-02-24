<?php
session_start();
include './config.php';

// Sirf Admin hi yeh action le sakta hai
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id'])) {
    
    $request_id = $_POST['request_id'];
    $user_id = $_POST['user_id'];
    $action = $_POST['action']; // 'Approve' ya 'Reject'
    $requested_role = $_POST['requested_role'];
    $admin_notes = trim($_POST['admin_notes']);
    $message = '';

    // Database operations ko transaction mein daalein
    $conn->begin_transaction();

    try {
        if ($action == 'Approve') {
            // Step 1: User ki role 'users' table mein update karein
            $update_user_stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $update_user_stmt->bind_param("si", $requested_role, $user_id);
            $update_user_stmt->execute();
            $update_user_stmt->close();
            
            // Step 2: Request ka status 'role_change_requests' table mein update karein
            $update_request_stmt = $conn->prepare("UPDATE role_change_requests SET status = 'Approved', admin_notes = ? WHERE id = ?");
            $update_request_stmt->bind_param("si", $admin_notes, $request_id);
            $update_request_stmt->execute();
            $update_request_stmt->close();
            
            $message = "Request successfully Approve ho gayi hai aur user ka role update ho gaya hai.";

        } elseif ($action == 'Reject') {
            // Sirf request ka status 'Rejected' karein
            $update_request_stmt = $conn->prepare("UPDATE role_change_requests SET status = 'Rejected', admin_notes = ? WHERE id = ?");
            $update_request_stmt->bind_param("si", $admin_notes, $request_id);
            $update_request_stmt->execute();
            $update_request_stmt->close();
            
            $message = "Request successfully Reject kar di gayi hai.";
        }
        
        // Agar sab sahi raha, toh changes ko save karein
        $conn->commit();
        $_SESSION['message'] = $message;

    } catch (Exception $e) {
        // Agar koi error aaye, toh changes ko roll back karein
        $conn->rollback();
        $_SESSION['message'] = "Ek error aa gaya: " . $e->getMessage();
    }
}

// Wapas admin page par bhej dein
header("Location: admin_role_requests.php");
exit();
?>