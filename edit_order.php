<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

include './config.php';

// --- 1. FETCH ORDER DETAILS (Modified to get user_id) ---
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);

    // Change: Added o.user_id to the SELECT query
    $stmt = $conn->prepare("SELECT o.id, o.user_id, o.status, o.expiry_date, o.file, u.username AS customer_username, s.name AS service_name
                            FROM orders o
                            JOIN users u ON o.user_id = u.id
                            JOIN services s ON o.service_id = s.id
                            WHERE o.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo "Order not found!";
        exit;
    }
} else {
    header("Location: admin_orders.php");
    exit;
}

// --- Logic to determine where to go back ---
// Agar user_id hai, to wapas wahi bhejo, nahi to main list par
$redirect_link = "admin_orders.php";
if (!empty($order['user_id'])) {
    $redirect_link .= "?user_id=" . $order['user_id'];
}

// --- 2. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $expiry_date = $_POST['expiry_date'];

    if (empty($expiry_date)) {
        $expiry_date = NULL;
    }

    $uploaded_file = $order['file']; 
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "./uploads/";
        $file_name = basename($_FILES['file']['name']);
        $target_file = $target_dir . $file_name;

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $uploaded_file = $file_name;
        } else {
            echo "Failed to upload file.";
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE orders SET status = ?, expiry_date = ?, file = ? WHERE id = ?");
    $stmt->bind_param("sssi", $status, $expiry_date, $uploaded_file, $order_id);
    $stmt->execute();

    // Redirect back to the specific user list using the logic created above
    header("Location: " . $redirect_link);
    exit;
}

include './header.php'; 
?>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .container {
        width: 80%;
        margin: 30px auto;
        background-color: white;
        padding: 30px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }
    h2 { font-size: 24px; margin-bottom: 20px; }
    form { display: flex; flex-direction: column; }
    form label { margin-bottom: 8px; font-weight: bold; }
    form select, form input[type="file"], form input[type="text"] {
        margin-bottom: 15px; padding: 10px; border-radius: 4px; border: 1px solid #ccc;
    }
    form button {
        padding: 10px 20px; background-color: #007bff; color: white;
        border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px;
    }
    form button:hover { background-color: #0056b3; }
    
    .cancel-btn { background-color: #6c757d; margin-top: 10px; }
    .cancel-btn:hover { background-color: #5a6268; }
    
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    
    .back-btn {
        display: inline-block; margin-bottom: 20px; padding: 10px 15px;
        background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px;
    }
    .back-btn:hover { background-color: #0056b3; }
</style>

<div class="container">
    <a href="<?php echo htmlspecialchars($redirect_link); ?>" class="back-btn">
        ← Back to User Orders
    </a>
    
    <h2>Edit Order #<?php echo htmlspecialchars($order['id']); ?></h2>

    <p><strong>Customer username:</strong> <?php echo htmlspecialchars($order['customer_username']); ?></p>
    <p><strong>Service name:</strong> <?php echo htmlspecialchars($order['service_name']); ?></p>
    <br>

    <form method="POST" enctype="multipart/form-data">
        
        <label for="status">Order Status</label>
        <select name="status" id="status" required>
            <option value="Pending" <?php if ($order['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
            <option value="Completed" <?php if ($order['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
            <option value="Cancelled By Us" <?php if ($order['status'] === 'Cancelled By Us') echo 'selected'; ?>>Cancelled By Us</option>
            <option value="Cancelled By Government" <?php if ($order['status'] === 'Cancelled By Government') echo 'selected'; ?>>Cancelled By Government</option>
            <option value="We Are Working" <?php if ($order['status'] === 'We Are Working') echo 'selected'; ?>>We Are Working</option>
            <option value="Document Submitted to Government" <?php if ($order['status'] === 'Document Submitted to Government') echo 'selected'; ?>>Document Submitted to Government</option>
        </select>

        <label for="expiry_date">Expiry Date</label>
        <?php
        $expiry_date_val = $order['expiry_date'] ? date('Y-m-d', strtotime($order['expiry_date'])) : '';
        ?>
        <input type="text" name="expiry_date" id="expiry_date" value="<?php echo htmlspecialchars($expiry_date_val); ?>" class="flatpickr" placeholder="Select date">

        <label for="file">Upload File (optional)</label>
        <input type="file" name="file" id="file" accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.png" />

        <?php if (!empty($order['file'])) { ?>
            <p style="margin-top: 5px;">Current File: <a href="uploads/<?php echo htmlspecialchars($order['file']); ?>" download><?php echo htmlspecialchars($order['file']); ?></a></p>
        <?php } ?>

        <button type="submit">Update Order</button>
    </form>

    <form action="<?php echo htmlspecialchars($redirect_link); ?>" method="post" style="margin-top:0;">
        <a href="<?php echo htmlspecialchars($redirect_link); ?>" style="display:block; text-align:center; text-decoration:none;">
            <button type="button" class="cancel-btn" style="width:100%;">Cancel</button>
        </a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr(".flatpickr", {
            dateFormat: "Y-m-d",
            allowInput: true,
        });
    });
</script>

<?php include 'footer.php'; ?>