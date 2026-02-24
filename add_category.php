<?php
session_start();
include './config.php';
include 'header.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle Category Addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = $_POST['category_name'];
    $seo_title = $_POST['seo_title'];
    $seo_description = $_POST['seo_description'];
    
    // Handle Image Upload
    $target_dir = "uploads/categories/";
    $target_file = $target_dir . basename($_FILES["category_image"]["name"]);
    move_uploaded_file($_FILES["category_image"]["tmp_name"], $target_file);
    
    // Insert into Database
    $stmt = $conn->prepare("INSERT INTO categories (name, image, seo_title, seo_description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $category_name, $target_file, $seo_title, $seo_description);
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
    <title>Add New Category</title>
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
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Category</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Category Name:</label>
            <input type="text" name="category_name" placeholder="Enter category name" required>
            
            <label>SEO Title:</label>
            <input type="text" name="seo_title" placeholder="Enter SEO title" required>
            
            <label>SEO Description:</label>
            <textarea name="seo_description" placeholder="Enter SEO description" required></textarea>
            
            <label>Upload Category Image:</label>
            <input type="file" name="category_image" required>
            
            <button type="submit" name="add_category">Add Category</button>
        </form>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>
