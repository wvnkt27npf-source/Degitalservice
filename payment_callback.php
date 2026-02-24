<?php
// degitalservice/payment_callback.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';              // Database connection $conn
include 'phonepe_config_data.php'; // PhonePe credentials

$merchantOrderId = $_GET['txnId'] ?? null;

if (!$merchantOrderId) {
    $_SESSION['order_fail_flash'] = "Transaction ID missing.";
    header("Location: services.php");
    exit();
}

$isLive       = ($phonepe_config['mode'] === 'live');
$clientId     = $isLive ? $phonepe_config['live_cid']    : $phonepe_config['test_cid'];
$clientSecret = $isLive ? $phonepe_config['live_secret'] : $phonepe_config['test_secret'];

// 1. Get OAuth Token from PhonePe
$tokenUrl = $isLive
    ? "https://api.phonepe.com/apis/identity-manager/v1/oauth/token"
    : "https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $tokenUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => "client_id=$clientId&client_version=1&client_secret=$clientSecret&grant_type=client_credentials",
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);

$tokenResponse = json_decode(curl_exec($curl), true);
$accessToken   = $tokenResponse['access_token'] ?? null;
curl_close($curl);

if (!$accessToken) {
    $_SESSION['order_fail_flash'] = "Authentication Failed: Unable to get token.";
    header("Location: services.php");
    exit();
}

// 2. Fetch Order Status from PhonePe API
$statusUrl = $isLive
    ? "https://api.phonepe.com/apis/pg/checkout/v2/order/{$merchantOrderId}/status?details=false"
    : "https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/{$merchantOrderId}/status?details=false";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $statusUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: O-Bearer ' . $accessToken
    ],
]);

$response   = curl_exec($curl);
$statusData = json_decode($response, true);
curl_close($curl);

// Order ID extract karein (Expected format: TXN{id}T...)
preg_match('/TXN(\d+)T/', $merchantOrderId, $matches);
$order_id = isset($matches[1]) ? intval($matches[1]) : 0;

if ($order_id <= 0) {
    $_SESSION['order_fail_flash'] = "Invalid Order Reference.";
    header("Location: services.php");
    exit();
}

// 3. Process Result
if (isset($statusData['state']) && $statusData['state'] === 'COMPLETED') {
    $amountReceived = ($statusData['amount'] ?? 0) / 100; // Paise to Rupees

    // Order aur User details check karein
    $checkOrder = $conn->prepare("SELECT user_id, status FROM orders WHERE id = ?");
    $checkOrder->bind_param("i", $order_id);
    $checkOrder->execute();
    $orderData = $checkOrder->get_result()->fetch_assoc();

    if ($orderData && $orderData['status'] !== 'Processing' && $orderData['status'] !== 'Completed') {
        // Status update
        $updateStmt = $conn->prepare("UPDATE orders SET status = 'Processing', payment_status = 'Success', payment_received = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $order_id);
        $updateStmt->execute();

        // Check for duplicate payment log
        $checkPay = $conn->prepare("SELECT id FROM payments WHERE utr_number = ?");
        $checkPay->bind_param("s", $merchantOrderId);
        $checkPay->execute();
        
        if ($checkPay->get_result()->num_rows == 0) {
            $insertPay = $conn->prepare("INSERT INTO payments (user_id, order_id, payment_amount, payment_method, payment_status, utr_number) VALUES (?, ?, ?, 'PhonePe', 'Completed', ?)");
            $insertPay->bind_param("iids", $orderData['user_id'], $order_id, $amountReceived, $merchantOrderId);
            $insertPay->execute();
        }
    }

    $_SESSION['order_success_flash'] = "Payment Successful!";
    header("Location: user_orders.php");
} else {
    // Payment Failed
    $updateStmt = $conn->prepare("UPDATE orders SET status = 'Failed', payment_status = 'Failed' WHERE id = ?");
    $updateStmt->bind_param("i", $order_id);
    $updateStmt->execute();

    $_SESSION['order_fail_flash'] = "Payment Failed: " . ($statusData['message'] ?? 'Unknown Error');
    header("Location: services.php");
}
exit();
?>