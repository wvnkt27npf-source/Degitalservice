<?php
// degitalservice/verify_payment.php
session_start();
include 'config.php';

// Yahan apni Mobikwik details dalein
$Authorization = "Aapka_Mobikwik_Auth_Token"; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = intval($_POST['order_id']);
    $utr_number = mysqli_real_escape_string($conn, $_POST['utr_number']);

    // Mobikwik API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://webapi.mobikwik.com/p/wallet/history/v2");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "authorization: $Authorization",
        "accept: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($response, true);

    $matchFound = false;
    if ($data && isset($data["data"]["historyData"])) {
        foreach ($data["data"]["historyData"] as $transaction) {
            // RRN (UTR) check karna
            if (isset($transaction["rrn"]) && $transaction["rrn"] == $utr_number && 
                $transaction["status"] == "success" && $transaction["mode"] == "credit") {
                $matchFound = true;
                $amount_received = $transaction['amount'] / 100; // Paise to Rupees
                break;
            }
        }
    }

    if ($matchFound) {
        // Order update logic
        $stmt = $conn->prepare("UPDATE orders SET status = 'Processing', payment_status = 'Success', payment_received = 1, utr = ? WHERE id = ?");
        $stmt->bind_param("si", $utr_number, $order_id);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "Payment Verified Successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "UTR not found in Mobikwik history or pending."]);
    }
}
?>