<?php
include 'phpqrcode/qrlib.php'; // Include the QR code library

// Initialize variables
$qr_code_path = '';
$upi_id = "rahulhaled@ybl"; // Your UPI ID
$payment_link = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['price'])) {
    $price = $_POST['price'];
    $service_name = "Test Service"; // You can customize this as needed

    // Generate the UPI payment link
    $payment_link = "upi://pay?pa=$upi_id&pn=YourName&mc=YourMerchantCode&tid=1234567890&am=$price&cu=INR&tn=Payment for $service_name";

    // Generate QR code
    $qr_code_path = 'uploads/qrcodes/' . uniqid() . '.png'; // Path to save QR code
    QRcode::png($payment_link, $qr_code_path, QR_ECLEVEL_L, 4); // Generate QR code
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR Code</title>
    <link rel="stylesheet" href="styles.css"> <!-- Optional: Include your CSS file -->
</head>
<body>

<div class="container">
    <h2>Generate QR Code for UPI Payment</h2>
    <form method="POST" action="">
        <label for="price">Enter Price (in INR):</label>
        <input type="number" id="price" name="price" required>
        <button type="submit">Generate QR Code</button>
    </form>

    <?php if ($qr_code_path) { ?>
        <h3>Generated QR Code:</h3>
        <img src="<?= htmlspecialchars($qr_code_path) ?>" alt="Payment QR Code" style="width: 200px; height: 200px;">
        <p>Scan the QR code to make a payment of ₹<?= htmlspecialchars($price) ?>.</p>
    <?php } ?>
</div>

</body>
</html>
