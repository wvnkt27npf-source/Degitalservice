<?php
include 'config.php';

$name = $_POST['name'];
$description = $_POST['description'];
$price = $_POST['price'];
$category_id = $_POST['category_id'];
$seo_title = $_POST['seo_title'];
$seo_description = $_POST['seo_description'];
$image = $_FILES['image']['name'];

move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image);

$stmt = $conn->prepare("INSERT INTO services (name, description, price, category_id, seo_title, seo_description, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdisss", $name, $description, $price, $category_id, $seo_title, $seo_description, $image);
$stmt->execute();

header("Location: manage_services.php");
