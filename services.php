<?php
// DS/services.php
session_start();

include './config.php';
include 'header.php';

$order_success = false;
$order_fail_message = '';

// 1. Flash messages check karein
if (isset($_SESSION['order_success_flash'])) {
    $order_success = true;
    unset($_SESSION['order_success_flash']);
}
if (isset($_SESSION['order_fail_flash'])) {
    $order_fail_message = $_SESSION['order_fail_flash'];
    unset($_SESSION['order_fail_flash']);
}

// Assigned documents fetch karne ka function
function getAssignedDocuments($service_id) {
    global $conn;
    $query = "SELECT d.document_name FROM required_documents d 
              JOIN service_document_assignments sda ON d.id = sda.document_id 
              WHERE sda.service_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return '';
    }

    $documents = '';
    while ($row = $result->fetch_assoc()) {
        $documents .= '<li>' . htmlspecialchars($row['document_name']) . '</li>';
    }
    return $documents;
}

// --- ORDER LOGIC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['service_id'])) {
    
    $service_id = $_POST['service_id'];
    $category_id_for_redirect = isset($_GET['category']) ? intval($_GET['category']) : 0;

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = "services.php?category=" . $category_id_for_redirect;
        header("Location: login.php");
        exit();
    } else {
        $user_id = $_SESSION['user_id'];

        // REORDER LOGIC: Active orders block karein
        $active_statuses = ['Pending', 'Processing', 'We Are Working', 'Document Submitted to Government'];
        $status_placeholders = implode(',', array_fill(0, count($active_statuses), '?'));
        
        $check_sql = "SELECT id FROM orders WHERE user_id = ? AND service_id = ? AND status IN ($status_placeholders)";
        $check_stmt = $conn->prepare($check_sql);
        
        $bind_params = array_merge([$user_id, $service_id], $active_statuses);
        $check_stmt->bind_param(str_repeat('i', 2) . str_repeat('s', count($active_statuses)), ...$bind_params);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $order_fail_message = "Aapka is service ka ek order pehle se process ho raha hai. Naya order tabhi kar sakte hain jab purana complete ya cancel ho jaye.";
        } else {
            $service_query = $conn->prepare("SELECT price, name, download_link FROM services WHERE id = ?");
            $service_query->bind_param("i", $service_id);
            $service_query->execute();
            $service_result = $service_query->get_result();

            if ($service_result->num_rows > 0) {
                $service = $service_result->fetch_assoc();
                $service_price = $service['price'];
                $initial_status = 'Pending';

                if (!empty($service['download_link']) && $service_price <= 0) {
                    $initial_status = 'Completed';
                }

                $order_query = $conn->prepare("INSERT INTO orders (user_id, service_id, price, status) VALUES (?, ?, ?, ?)");
                $order_query->bind_param("iids", $user_id, $service_id, $service_price, $initial_status);
                
                if ($order_query->execute()) {
                    $order_id = $conn->insert_id;
                    if ($service_price > 0) {
                        echo "<script>window.location.href = 'payment.php?service_id=$service_id&price=$service_price&order_id=$order_id';</script>";
                        exit();
                    } else {
                        $order_success = true;
                    }
                } else {
                    $order_fail_message = "Error placing order. Please try again.";
                }
            }
        }
    }
}

// Category filter logic
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'customer';

if ($user_role == 'admin') {
    $services = $conn->prepare("SELECT * FROM services WHERE category_id = ? ORDER BY `order` ASC");
    $services->bind_param("i", $category_id);
} else {
    $services = $conn->prepare("SELECT * FROM services WHERE category_id = ? AND (visible_to IS NULL OR visible_to = '' OR FIND_IN_SET(?, visible_to)) ORDER BY `order` ASC");
    $services->bind_param("is", $category_id, $user_role);
}

$services->execute();
$result = $services->get_result();

$category_name = 'Services';
if ($category_id > 0) {
    $cat_q = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_q->bind_param("i", $category_id);
    $cat_q->execute();
    $cat_res = $cat_q->get_result();
    if ($row = $cat_res->fetch_assoc()) { $category_name = $row['name']; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category_name) ?></title>
    <link rel="stylesheet" href="services.css">
    <style>
        /* Modern & Stylish Popup Styling */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(5px); z-index: 2000; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.show { display: block; opacity: 1; }
        
        .modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.8); z-index: 2001; opacity: 0; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); width: 90%; max-width: 420px; }
        .modal.show { display: block; opacity: 1; transform: translate(-50%, -50%) scale(1); }
        
        .modal-content { background: #fff; border-radius: 20px; padding: 35px 25px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; max-height: 85vh; overflow-y: auto; }
        
        .close-btn-icon { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #bbb; line-height: 1; }
        .close-btn-icon:hover { color: #555; }

        /* Animation Containers */
        .swal-icon-container { width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; }
        
        /* Success Tick Animation */
        .swal-success { border: 4px solid #4caf50; position: relative; }
        .swal-success::after { content: ''; position: absolute; width: 30px; height: 15px; border-left: 4px solid #4caf50; border-bottom: 4px solid #4caf50; transform: rotate(-45deg); margin-top: -5px; animation: checkmark 0.5s ease-in-out; }
        
        /* Error Cross Animation */
        .swal-error { border: 4px solid #f44336; position: relative; }
        .swal-error::before, .swal-error::after { content: ''; position: absolute; width: 40px; height: 4px; background-color: #f44336; transform: rotate(45deg); animation: cross-show 0.3s ease-in-out; }
        .swal-error::after { transform: rotate(-45deg); }

        @keyframes checkmark { 0% { height: 0; width: 0; opacity: 0; } 50% { height: 0; width: 30px; opacity: 1; } 100% { height: 15px; width: 30px; opacity: 1; } }
        @keyframes cross-show { 0% { transform: scale(0) rotate(45deg); opacity: 0; } 100% { transform: scale(1) rotate(45deg); opacity: 1; } }

        .modal-content h2 { color: #333; margin-bottom: 10px; font-size: 1.6rem; }
        .modal-content p { color: #666; font-size: 1rem; line-height: 1.5; margin-bottom: 25px; }
        
        .btn-action { display: block; width: 100%; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 1rem; transition: 0.2s; border: none; cursor: pointer; }
        .btn-blue { background: #2563eb; color: #fff; margin-bottom: 10px; }
        .btn-blue:hover { background: #1d4ed8; }
        .btn-outline { background: #f3f4f6; color: #4b5563; }
        .btn-outline:hover { background: #e5e7eb; }

        /* SEO Content Fix */
        .seo-popup-content { text-align: left; border-top: 6px solid #2563eb; }
        .seo-popup-content h3 { border-bottom: 2px solid #f3f4f6; padding-bottom: 15px; margin-bottom: 15px; }

        /* Reorder Logic Style */
        .place-order-btn { transition: transform 0.2s; }
        .place-order-btn:active { transform: scale(0.95); }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="font-weight: 800;"><?= htmlspecialchars($category_name) ?></h2>
        <a href="category.php" class="back-btn" style="text-decoration:none; font-weight:600; color:#2563eb;">&larr; Categories</a>
    </div>

    <div class="service-card">
    <?php if ($result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) { 
            $assigned_docs = getAssignedDocuments($row['id']);
    ?>
    <div class="service">
        <div class="service-image">
            <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" 
                 onclick="showSEOPopup('<?= htmlspecialchars(addslashes($row['seo_title'])) ?>', '<?= htmlspecialchars(addslashes($row['seo_description'])) ?>')" 
                 style="cursor:pointer; border-radius: 12px;">
        </div>
        <h3><?= htmlspecialchars($row['name']) ?></h3>
        <p><?= htmlspecialchars($row['description']) ?></p>
        <div class="price">₹<?= number_format($row['price'], 2) ?></div>
        
        <div class="service-actions" style="margin-bottom: 15px; display: flex; gap: 10px; justify-content: center;">
            <?php if (!empty($row['demo_link'])) { ?>
                <a href="<?= htmlspecialchars($row['demo_link']) ?>" class="demo-link" target="_blank" style="font-size: 0.85rem;">View Demo</a>
            <?php } ?>
            
            <?php if (!empty($assigned_docs)) { ?>
                <button class="view-documents-btn" onclick="openDocumentsModal(<?= $row['id'] ?>)" style="font-size: 0.85rem;">Required Docs</button>
            <?php } ?>
        </div>

        <form method="POST">
            <input type="hidden" name="service_id" value="<?= $row['id'] ?>">
            <button type="submit" class="place-order-btn" style="width: 100%; border-radius: 8px;">Place Order</button>
        </form>
    </div>
    <?php } } else { ?>
        <p style="grid-column: 1/-1; text-align:center; padding:80px; color:#94a3b8; font-size: 1.1rem;">No services found in this category.</p>
    <?php } ?>
    </div>

    <div class="modal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>

    <div class="modal" id="orderSuccessModal">
        <div class="modal-content">
            <span class="close-btn-icon" onclick="closeAllModals()">&times;</span>
            <div class="swal-icon-container swal-success"></div>
            <h2>Order Placed!</h2>
            <p>Aapka order successfully receive ho gaya hai. Ab aap orders section mein status dekh sakte hain.</p>
            <a href="user_orders.php" class="btn-action btn-blue">My Orders</a>
            <button class="btn-action btn-outline" onclick="closeAllModals()">Close</button>
        </div>
    </div>
    
    <div class="modal" id="orderFailModal">
        <div class="modal-content">
            <span class="close-btn-icon" onclick="closeAllModals()">&times;</span>
            <div class="swal-icon-container swal-error"></div>
            <h2 style="color:#ef4444;">Order Failed</h2>
            <p id="failMsgText"><?= htmlspecialchars($order_fail_message) ?></p>
            <button class="btn-action btn-outline" onclick="closeAllModals()">Close</button>
        </div>
    </div>

    <div id="seoPopup" class="modal">
        <div class="modal-content seo-popup-content">
            <span class="close-btn-icon" onclick="closeAllModals()">&times;</span>
            <h3><span id="seoTitle"></span></h3>
            <p id="seoDescription"></p>
        </div>
    </div>

    <div id="documentsModal" class="modal">
        <div class="modal-content" style="text-align: left;">
            <span class="close-btn-icon" onclick="closeAllModals()">&times;</span>
            <h2 style="font-size: 1.3rem; margin-bottom: 20px; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;">Required Documents</h2>
            <ul id="documentList" style="line-height: 2; color: #4b5563; padding-left: 20px;"></ul>
        </div>
    </div>
</div>

<script>
const overlay = document.getElementById('modalOverlay');

function showModal(modalId) {
    overlay.classList.add('show');
    document.getElementById(modalId).classList.add('show');
}

function closeAllModals() {
    overlay.classList.remove('show');
    document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
}

function showSEOPopup(title, desc) {
    document.getElementById('seoTitle').innerText = title || 'Service Info';
    document.getElementById('seoDescription').innerText = desc || 'Description not provided.';
    showModal('seoPopup');
}

function openDocumentsModal(id) {
    const docList = document.getElementById('documentList');
    docList.innerHTML = '<li>Loading...</li>';
    showModal('documentsModal');
    
    fetch('fetch_assigned_documents.php?service_id=' + id)
        .then(res => res.text())
        .then(data => { docList.innerHTML = data || '<li>No specific documents required.</li>'; })
        .catch(() => { docList.innerHTML = '<li>Error loading documents.</li>'; });
}

window.onload = function() {
    <?php if ($order_success) { ?> setTimeout(() => showModal('orderSuccessModal'), 300); <?php } ?>
    <?php if (!empty($order_fail_message)) { ?> setTimeout(() => showModal('orderFailModal'), 300); <?php } ?>
};
</script>

</body>
</html>
<?php include 'footer.php'; ?>