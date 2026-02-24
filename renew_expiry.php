<?php
session_start();
include './config.php';

// YEH NAYA AUR SAHI AUTHENTICATION CHECK HAI
$loggedInUserRoles = ['user', 'client', 'customer']; // Sabhi allowed roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php"); // Agar login nahi hai ya admin hai, toh redirect karein
    exit();
}

include 'header.php';

// Flag to check if order is successfully placed
$order_success = false;

// Fetch completed orders for the user
$user_id = $_SESSION['user_id'];
$completed_orders_query = $conn->prepare("SELECT o.id, o.service_id, s.name AS service_name, o.price, o.status, o.expiry_date, o.file FROM orders o JOIN services s ON o.service_id = s.id WHERE o.user_id = ? AND o.status = 'Completed'");
$completed_orders_query->bind_param("i", $user_id);
$completed_orders_query->execute();
$completed_orders = $completed_orders_query->get_result();

// Renewal order processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['renew_order'])) {
    $service_id = $_POST['service_id'];

    // Check if there is already a pending order for this service
    $pending_order_query = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND service_id = ? AND status = 'Pending'");
    $pending_order_query->bind_param("ii", $user_id, $service_id);
    $pending_order_query->execute();
    $pending_result = $pending_order_query->get_result();

    if ($pending_result->num_rows > 0) {
        echo "<script>alert('Your renewal order for this service is already in process.');</script>";
    } else {
        // Fetch the price for the service
        $service_query = $conn->prepare("SELECT price, name FROM services WHERE id = ?");
        $service_query->bind_param("i", $service_id);
        $service_query->execute();
        $service_result = $service_query->get_result();

        if ($service_result->num_rows > 0) {
            $service = $service_result->fetch_assoc();
            $service_price = $service['price'];

            // Insert the new order into the orders table with 'Pending' status
            $order_query = $conn->prepare("INSERT INTO orders (user_id, service_id, price, status) VALUES (?, ?, ?, 'Pending')");
            $order_query->bind_param("iid", $user_id, $service_id, $service_price);
            if ($order_query->execute()) {
                $order_success = true; // Set success flag
            } else {
                echo "<script>alert('Error placing order. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Invalid service.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="renew_expiry.css">
</head>
<body>

<!-- Back to Orders Button -->
<div class="container">
    <a href="category.php" class="back-btn">Back to Services</a>
    <h2>Your Completed Orders</h2>

    <!-- Orders Table with Renew Button -->
    <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-top: 20px; text-align: center;">
        <thead>
            <tr>
                <th>Service Name</th>
                <th>File</th>
                <th>Expiry Date</th>
                <th>Days Remaining</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($completed_orders->num_rows > 0) {
            while ($order = $completed_orders->fetch_assoc()) {
                // Handle expiry date and calculate days remaining
                if ($order['expiry_date'] !== NULL && $order['expiry_date'] != 'N/A') {
                    $expiry_date = new DateTime($order['expiry_date']);
                    $formatted_expiry_date = $expiry_date->format('Y-m-d'); // Format as YYYY-MM-DD

                    // Calculate days remaining
                    $current_date = new DateTime();
                    $interval = $expiry_date->diff($current_date);
                    $days_remaining = $interval->days;
                    $status_text = $days_remaining > 0 ? $days_remaining . " days" : "Expired";
                    $show_renew_button = true;
                } else {
                    $formatted_expiry_date = 'N/A'; // If no expiry date, display N/A
                    $status_text = 'N/A'; // No days remaining if expiry date is missing
                    $show_renew_button = false; // Hide Renew button if expiry date is N/A
                }

                // Check if a pending order exists for the same service
                $pending_order_query = $conn->prepare("SELECT id FROM orders WHERE user_id = ? AND service_id = ? AND status = 'Pending'");
                $pending_order_query->bind_param("ii", $user_id, $order['service_id']);
                $pending_order_query->execute();
                $pending_result = $pending_order_query->get_result();
        ?>
            <tr>
                <td><?= htmlspecialchars($order['service_name']) ?></td>
                <td>
                    <?php if (!empty($order['file'])) { ?>
                        <a href="uploads/<?= htmlspecialchars($order['file']) ?>" download>
                            <button>Download</button>
                        </a>
                    <?php } else { ?>
                        No file available
                    <?php } ?>
                </td>
                <td><?= htmlspecialchars($formatted_expiry_date) ?></td>
                <td><?= $status_text ?></td>
                <td>
                    <?php
                    // Show the renew button only if the expiry date is not N/A
                    if ($show_renew_button) {
                        // Check if there is a pending order for the same service
                        if ($pending_result->num_rows > 0) {
                            echo '<span style="color: red; font-weight: bold;">Your renewal order is already in process.</span>';
                        } else {
                    ?>
                        <form method="POST">
                            <input type="hidden" name="service_id" value="<?= $order['service_id'] ?>">
                            <button type="submit" name="renew_order">Renew</button>
                        </form>
                    <?php
                        }
                    }
                    ?>
                </td>
            </tr>
        <?php
            }
        } else {
        ?>
        <tr>
            <td colspan="5">You have no completed orders to renew.</td>
        </tr>
        <?php } ?>
        </tbody>
    </table>

    <!-- Order Success Modal -->
    <?php if ($order_success) { ?>
        <div class="modal-overlay" id="modalOverlay"></div>
        <div class="modal" id="orderSuccessModal">
            <div class="modal-content">
                <div class="icon">
                    <img src="uploads/verified.gif" alt="Order Verified">
                </div>
                <h2>Your Renew Order has been Placed!</h2>
                <p class="message">Thank you for renewing your order. You can track it from the link below.</p>
                <a href="user_orders.php" class="btn btn-primary">Go to My Orders</a>
                <button class="close-btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    <?php } ?>
</div>

<script>
// Close the Order Success modal
function closeModal() {
    document.getElementById('orderSuccessModal').classList.remove('show');
    document.getElementById('modalOverlay').classList.remove('show');
}

// Open the Order Success modal
function openModal() {
    document.getElementById('orderSuccessModal').classList.add('show');
    document.getElementById('modalOverlay').classList.add('show');
}

<?php if ($order_success) { ?>
    window.onload = openModal;
<?php } ?>
</script>

</body>
</html>

<?php include 'footer.php'; ?>
