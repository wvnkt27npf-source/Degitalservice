<?php
// degitalservice/payment.php
session_start();
include 'config.php'; // Aapka DB connection $conn

// Check karein ki zaroori parameters mil rahe hain ya nahi
if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    header("Location: services.php");
    exit();
}

$order_id = intval($_GET['order_id']);

// Price check: Error log fix karne ke liye
$amount = isset($_GET['price']) ? floatval($_GET['price']) : 0;

// Agar price URL mein nahi hai, to database se uthayein (Double Check)
if ($amount <= 0) {
    $checkOrder = $conn->prepare("SELECT price FROM orders WHERE id = ?");
    $checkOrder->bind_param("i", $order_id);
    $checkOrder->execute();
    $res = $checkOrder->get_result()->fetch_assoc();
    $amount = ($res) ? floatval($res['price']) : 0;
}

if ($amount <= 0) {
    die("Error: Invalid payment amount.");
}

// Unique Token for Security
$link_token = bin2hex(random_bytes(16)); 

// Link save karein track karne ke liye
$stmt = $conn->prepare("INSERT INTO payment_links (order_id, link_token) VALUES (?, ?)");
$stmt->bind_param("is", $order_id, $link_token);

if ($stmt->execute()) {
    // Note: Path ko apne folder structure ke hisaab se sahi rakhein
    // Agar pay_now.php 'payment/payment8/' folder mein hai:
    header("Location: payment/payment8/pay_now.php?token=" . $link_token);
    exit();
} else {
    echo "Error generating payment link: " . $conn->error;
}
?>