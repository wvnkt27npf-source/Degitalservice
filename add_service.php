<?php
// 1. Start session and output buffering to prevent header issues
ob_start();
session_start();
include './config.php';

// 2. Check Admin Role BEFORE any HTML is sent
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 3. Handle Service Addition Logic BEFORE including header.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $service_name = $_POST['service_name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = implode(',', $_POST['seo_keywords']);
    $image_path = "";
    $download_link = "";
    $demo_link = $_POST['demo_link'];
    
    $visible_to = [];
    if (!empty($_POST['visible_to']) && is_array($_POST['visible_to'])) {
        $visible_to = $_POST['visible_to'];
    }
    $visible_to_str = implode(',', $visible_to);

    if (!empty($_FILES["service_image"]["name"])) {
        $service_type = 'digital';
        $target_dir = "uploads/services/";
        $image_name = basename($_FILES["service_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $error_msg = "Image upload failed. Please check permissions.";
            }
        } else {
            $error_msg = "Invalid image type. Only JPG, JPEG, PNG & GIF are allowed.";
        }
    } elseif (!empty($_POST["download_link"])) {
        $service_type = 'download';
        $download_link = $_POST["download_link"];
        $price = 0;
    } else {
        $error_msg = "Please upload an image for digital services or provide a download link.";
    }

    // Only proceed to insert if there are no errors
    if (!isset($error_msg)) {
        $stmt = $conn->prepare("INSERT INTO services (name, category_id, price, image, seo_title, seo_description, seo_keywords, service_type, download_link, demo_link, visible_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssssss", $service_name, $category_id, $price, $image_path, $seo_title, $seo_description, $seo_keywords, $service_type, $download_link, $demo_link, $visible_to_str);
        
        if ($stmt->execute()) {
            header("Location: manage_services.php");
            exit();
        } else {
            $error_msg = "Error adding service to database.";
        }
    }
}

// 4. Fetch Categories for the dropdown
$categories = $conn->query("SELECT * FROM categories");

// 5. Now that logic is done, we can include the UI
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Service</title>
    <link rel="stylesheet" href="add_service.css">
    <style>
        textarea#seo_keyword_1 { width: 550px; height: 50px; padding: 10px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px; resize: none; }
        .visibility-group { border: 1px solid #ddd; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .visibility-group label { font-weight: bold; display: block; margin-bottom: 10px; }
        .visibility-group div { display: inline-block; margin-right: 20px; }
        .visibility-group input[type="checkbox"] { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Service</h2>
        
        <?php if (isset($error_msg)): ?>
            <div style="color: red; margin-bottom: 15px;"><strong>Error:</strong> <?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Service Name:</label>
            <input type="text" name="service_name" placeholder="Enter service name" required>
            
            <label>Category:</label>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php while ($row = $categories->fetch_assoc()) { ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php } ?>
            </select>

            <label>Price:</label>
            <input type="text" name="price" placeholder="Enter price" required>

            <label>SEO Title:</label>
            <input type="text" name="seo_title" placeholder="Enter SEO title" required>
            
            <label>SEO Description:</label>
            <textarea name="seo_description" placeholder="Enter SEO description" required></textarea>
            
            <label>SEO Keywords:</label>
            <div class="keywords-container" id="keywords-container">
                <div class="keyword-box">
                    <textarea name="seo_keywords[]" placeholder="Paste keywords here, separated by commas" id="seo_keyword_1" rows="4" required></textarea>
                </div>
            </div>

            <label>Demo Link (optional):</label>
            <input type="url" name="demo_link" placeholder="Enter demo link (if applicable)">

            <div class="visibility-group">
                <label>Service Kisko Dikhni Chahiye? (Visible To):</label>
                <div>
                    <input type="checkbox" id="vis_user" name="visible_to[]" value="user">
                    <label for="vis_user">User</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_client" name="visible_to[]" value="client">
                    <label for="vis_client">Client</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_customer" name="visible_to[]" value="customer">
                    <label for="vis_customer">Customer</label>
                </div>
                <div>
                    <input type="checkbox" id="vis_guest" name="visible_to[]" value="guest">
                    <label for="vis_guest">Guest (Not Logged In)</label>
                </div>
            </div>
            <div id="image_div">
                <label>Upload Service Image:</label>
                <input type="file" name="service_image">
            </div>

            <button type="submit" name="add_service">Add Service</button>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const keywordsInput = document.querySelector('textarea[name="seo_keywords[]"]').value;
            const keywordsArray = keywordsInput.split(',')
                .map(keyword => keyword.trim())
                .filter(keyword => keyword.length > 0);
            document.querySelector('textarea[name="seo_keywords[]"]').value = keywordsArray.join(',');
        });
    </script>
</body>
</html>
<?php 
include 'footer.php'; 
ob_end_flush(); // Send the output buffer
?>