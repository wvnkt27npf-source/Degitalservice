<?php
session_start();
if ($_SESSION['role'] != 'user') {
    // Note: Is page ko 'user' ke alawa 'client' aur 'customer' ko bhi access dena chahiye
    // Main isse 'admin' ke alawa sabke liye allow kar raha hoon.
    if ($_SESSION['role'] == 'admin') {
        header("Location: manage_services.php"); // Admin ko manage page par bhejein
        exit();
    }
    // Agar user, client, ya customer nahi hai, toh login par bhejein
    if (!in_array($_SESSION['role'], ['user', 'client', 'customer'])) {
         header("Location: login.php");
         exit();
    }
}

include './config.php';
include 'header.php';

// Order Place Logic
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['service_id'])) {
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Please login to place an order!');</script>";
    } else {
        $user_id = $_SESSION['user_id'];
        $service_id = intval($_POST['service_id']);
        
        // Price fetch karein (dashboard par price nahi tha, isliye DB se lein)
        $price_query = $conn->prepare("SELECT price FROM services WHERE id = ?");
        $price_query->bind_param("i", $service_id);
        $price_query->execute();
        $price_result = $price_query->get_result();
        $service_price = 0;
        if($price_result->num_rows > 0) {
            $service_price = $price_result->fetch_assoc()['price'];
        }

        $order_query = $conn->prepare("INSERT INTO orders (user_id, service_id, price, status) VALUES (?, ?, ?, 'Pending')");
        $order_query->bind_param("iids", $user_id, $service_id, $service_price);

        if ($order_query->execute()) {
            echo "<script>alert('Order placed successfully!'); window.location.href='user_orders.php';</script>";
        } else {
            echo "<script>alert('Failed to place order. Try again later.');</script>";
        }
    }
}

// Fetch Categories
$categories = $conn->query("SELECT * FROM categories");

// --- NAYA SERVICE FILTER LOGIC ---
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$user_role = 'guest'; // Default
if (isset($_SESSION['role'])) {
    $user_role = $_SESSION['role'];
}

// Admin ko sab dikhega, baakiyon ko filtered
if ($user_role == 'admin') {
    $services = $conn->prepare("SELECT * FROM services WHERE category_id = ? OR ? = 0");
    $services->bind_param("ii", $category_id, $category_id);
} else {
    // Role ke hisaab se filter karein
    $services = $conn->prepare("SELECT * FROM services WHERE (category_id = ? OR ? = 0) AND FIND_IN_SET(?, visible_to)");
    $services->bind_param("iis", $category_id, $category_id, $user_role);
}
// --- END NAYA LOGIC ---

$services->execute();
$result = $services->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Service</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .category {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .category img {
            width: 100%;
            height: 80px;
            object-fit: contain;
            border-radius: 5px;
        }
        .category:hover {
            background: #007BFF;
            color: white;
        }
        .service-card {
            display: grid;
            gap: 20px;
            margin-top: 30px;
            grid-template-columns: repeat(4, 1fr);
        }
        .service {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .service img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
        }
        .service:hover {
            transform: translateY(-5px);
        }
        .service h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 10px 0;
        }
        .service p {
            font-size: 0rem;
            color: #555;
            flex-grow: 1;
            max-height: 0px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .price {
            font-size: 1.2rem;
            font-weight: 600;
            color: #007BFF;
            margin-bottom: 10px;
        }
        .service button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        .service button:hover {
            background-color: #218838;
        }
        @media (max-width: 768px) {
            .service-card {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .grid-toggle {
            margin-bottom: 15px;
            text-align: right;
        }
        .grid-toggle select {
            padding: 5px;
            font-size: 1rem;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Choose a Category</h2>
    <div class="category-grid">
        <?php while ($cat = $categories->fetch_assoc()) { ?>
            <div class="category" onclick="loadServices(<?= $cat['id'] ?>)">
                <img src="<?= $cat['image'] ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                <p><?= htmlspecialchars($cat['name']) ?></p>
            </div>
        <?php } ?>
    </div>

    <h2>Available Services</h2>

    <div class="grid-toggle">
        <label for="gridView">View Mode:</label>
        <select id="gridView" onchange="changeGrid()">
            <option value="4">4x4 Grid</option>
            <option value="2">2x2 Grid</option>
        </select>
    </div>

    <div id="service-container" class="service-card">
        <?php if ($result->num_rows > 0) { 
            while ($row = $result->fetch_assoc()) { ?>
                <div class="service">
                    <img src="<?= $row['image'] ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                    <h3><?= htmlspecialchars($row['name']) ?></h3>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <div class="price">₹<?= number_format($row['price'], 2) ?></div>
                    <form method="POST">
                        <input type="hidden" name="service_id" value="<?= $row['id'] ?>">
                        <button type="submit">Place Order</button>
                    </form>
                </div>
        <?php } 
        } else { ?>
            <p style="text-align:center; font-size:18px; color:#777;">No services available for this category or your role.</p>
        <?php } ?>
    </div>
</div>

<script>
    function loadServices(categoryId) {
        // dashboard.php ko reload karein category parameter ke saath
        window.location.href = 'dashboard.php?category=' + categoryId;
    }

    function changeGrid() {
        let grid = document.getElementById("gridView").value;
        let serviceContainer = document.getElementById("service-container");
        if (grid == "2") {
            serviceContainer.style.gridTemplateColumns = "repeat(2, 1fr)";
        } else {
            serviceContainer.style.gridTemplateColumns = "repeat(4, 1fr)";
        }
    }
</script>

</body>
</html>

<?php include 'footer.php'; ?>