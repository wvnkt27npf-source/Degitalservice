<?php
session_start();
include './config.php';
include 'header.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_categories.php");
    exit();
}

$category_id = intval($_GET['id']);

// Fetch category details
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->bind_param("i", $category_id);
$stmt->execute();
$result = $stmt->get_result();
$category = $result->fetch_assoc();

if (!$category) {
    header("Location: manage_categories.php");
    exit();
}

// Handle Category Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $category_name = $_POST['category_name'];
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    $seo_keywords = $_POST['seo_keywords'];  // Capture the SEO keywords
    $target_file = $category['image']; // Default to existing image

    // Handle Image Upload if provided
    if (!empty($_FILES["category_image"]["name"])) {
        $target_dir = "uploads/categories/";
        $target_file = $target_dir . basename($_FILES["category_image"]["name"]);
        move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file);
    }

    // Update Database with SEO keywords
    $stmt = $conn->prepare("UPDATE categories SET name = ?, image = ?, seo_title = ?, seo_description = ?, seo_keywords = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $category_name, $target_file, $seo_title, $seo_description, $seo_keywords, $category_id);
    $stmt->execute();

    header("Location: manage_categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input, textarea, button {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        img {
            display: block;
            margin: 10px auto;
            max-width: 100px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Category</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Category Name:</label>
            <input type="text" name="category_name" value="<?= htmlspecialchars($category['name']) ?>" required>
            
            <label>SEO Title:</label>
            <input type="text" name="seo_title" value="<?= htmlspecialchars($category['seo_title']) ?>" required>
            
            <label>SEO Description:</label>
            <textarea name="seo_description" required><?= htmlspecialchars($category['seo_description']) ?></textarea>
            
            <label>SEO Keywords (Comma separated):</label>
            <input type="text" name="seo_keywords" value="<?= htmlspecialchars($category['seo_keywords']) ?>" placeholder="e.g., electronics, mobile, gadgets">
            
            <label>Upload New Image (Optional):</label>
            <input type="file" name="category_image">
            
            <p>Current Image:</p>
            <img src="<?= htmlspecialchars($category['image']) ?>" alt="Category Image">
            
            <button type="submit" name="update_category">Update Category</button>
        </form>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>