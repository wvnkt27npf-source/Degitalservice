<?php
session_start();
include './config.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_services.php");
    exit();
}

$service_id = $_GET['id'];

// Fetch the service details
// 'visible_to' column ko bhi fetch karein
$query = $conn->prepare("SELECT services.*, categories.id AS category_id, categories.name AS category_name FROM services
                        JOIN categories ON services.category_id = categories.id WHERE services.id = ?");
$query->bind_param("i", $service_id);
$query->execute();
$result = $query->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    header("Location: manage_services.php");
    exit();
}

// Naya: Pehle se selected roles ko ek array mein store karein
$visible_roles = explode(',', $service['visible_to'] ?? '');

// Handle the form submission to update the service details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = implode(',', $_POST['seo_keywords']);
    $demo_link = $_POST['demo_link'];
    $image = $service['image'];

    // Naya logic: Visibility roles ko process karein
    $visible_to = [];
    if (!empty($_POST['visible_to']) && is_array($_POST['visible_to'])) {
        $visible_to = $_POST['visible_to'];
    }
    $visible_to_str = implode(',', $visible_to); // Comma-separated string banayein

    // Handle Image Upload
    if (!empty($_FILES['service_image']['name'])) {
        $target_dir = "uploads/services/";
        $image_name = basename($_FILES["service_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
                $image = $target_file;
            } else {
                echo "<script>alert('Image upload failed. Please check permissions.');</script>";
            }
        } else {
            echo "<script>alert('Invalid image type. Only JPG, JPEG, PNG & GIF are allowed.');</script>";
        }
    }

    // Update the service in the database (visible_to column ke saath)
    $stmt = $conn->prepare("UPDATE services SET name = ?, category_id = ?, price = ?, image = ?, seo_title = ?, seo_description = ?, seo_keywords = ?, demo_link = ?, visible_to = ? WHERE id = ?");
    $stmt->bind_param("sisssssssi", $name, $category_id, $price, $image, $seo_title, $seo_description, $seo_keywords, $demo_link, $visible_to_str, $service_id);

    if ($stmt->execute()) {
        header("Location: manage_services.php");
        exit();
    } else {
        echo "<script>alert('Error updating service.');</script>";
    }
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service</title>
    <style>
    
        
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
    color: black;
}    
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            font-weight: bold;
            text-align: left;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            padding: 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        /* Naya CSS visibility checkboxes ke liye */
        .visibility-group {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: left; /* Aligns checkboxes */
        }
        .visibility-group label {
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }
        .visibility-group div {
            display: inline-block;
            margin-right: 20px;
        }
        .visibility-group input[type="checkbox"] {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Service</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form action="edit_service.php?id=<?= $service_id ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Service Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($service['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" required>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories");
                    while ($category = $categories->fetch_assoc()) {
                        $selected = $category['id'] == $service['category_id'] ? 'selected' : '';
                        echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" value="<?= htmlspecialchars($service['price']) ?>" required>
            </div>

            <div class="form-group">
                <label for="seo_title">SEO Title</label>
                <input type="text" id="seo_title" name="seo_title" value="<?= htmlspecialchars($service['seo_title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="seo_description">SEO Description</label>
                <textarea id="seo_description" name="seo_description" required><?= htmlspecialchars($service['seo_description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="seo_keywords">SEO Keywords</label>
                <textarea id="seo_keywords" name="seo_keywords[]" placeholder="SEO keywords" rows="4" required><?= htmlspecialchars($service['seo_keywords']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="demo_link">Demo Link (optional)</label>
                <input type="url" id="demo_link" name="demo_link" value="<?= htmlspecialchars($service['demo_link']) ?>" placeholder="Enter demo link (if applicable)">
            </div>

            <div class="visibility-group">
                <label>Service Kisko Dikhni Chahiye? (Visible To):</label>
                <div>
                    <input type="checkbox" id="vis_user" name="visible_to[]" value="user" <?php echo in_array('user', $visible_roles) ? 'checked' : ''; ?>>
                    <label for="vis_user">User</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_client" name="visible_to[]" value="client" <?php echo in_array('client', $visible_roles) ? 'checked' : ''; ?>>
                    <label for="vis_client">Client</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_customer" name="visible_to[]" value="customer" <?php echo in_array('customer', $visible_roles) ? 'checked' : ''; ?>>
                    <label for="vis_customer">Customer</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_guest" name="visible_to[]" value="guest" <?php echo in_array('guest', $visible_roles) ? 'checked' : ''; ?>>
                    <label for="vis_guest">Guest (Not Logged In)</label>
                </div>
            </div>
            <div class="form-group">
                <label for="service_image">Service Image</label>
                <input type="file" id="service_image" name="service_image">
                <?php if (!empty($service['image'])): ?>
                    <img src="<?= htmlspecialchars($service['image']) ?>" width="100" alt="Current Image">
                <?php endif; ?>
            </div>

            <button type="submit">Update Service</button>
        </form>

        <a href="manage_services.php">Back to Manage Services</a>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>