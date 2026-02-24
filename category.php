<?php
session_start();
include './config.php';
include 'header.php';

// 1. Vartamaan user ka role praapt karein
$userRole = $_SESSION['role'] ?? 'guest';

// 2. Role ke aadhaar par categories fetch karein
if ($userRole === 'admin') {
    $query = $conn->prepare("SELECT * FROM categories");
} else {
    $query = $conn->prepare(
        "SELECT DISTINCT c.* FROM categories c
         JOIN services s ON c.id = s.category_id
         WHERE FIND_IN_SET(?, s.visible_to)"
    );
    $query->bind_param("s", $userRole);
}

$query->execute();
$categories = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a Service Category</title>
    <link rel="stylesheet" href="category.css">
</head>
<body>

<div class="container">
    <h2>Choose a Service Category</h2>
    <div class="category-grid">
        
        <?php if ($userRole === 'client'): ?>
            <div class="category" onclick="window.location.href='renew_expiry.php'">
                <img src="uploads/customimage.png" alt="My Document">
                <p>My Document</p>
            </div>

            <div class="category" onclick="window.location.href='user_view_principles.php'">
                <img src="uploads/principles_icon.png" alt="Principles" onerror="this.src='uploads/default_cat.png'">
                <p>My Principles</p>
            </div>
        <?php endif; ?>

        <?php if ($categories->num_rows > 0): ?>
            <?php while ($cat = $categories->fetch_assoc()) { ?>
                <div class="category" onclick="window.location.href='services.php?category=<?php echo urlencode($cat['id']); ?>'">
                    <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
                    <p><?= htmlspecialchars($cat['name']) ?></p>
                </div>
            <?php } ?>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>
<?php include 'footer.php'; ?>