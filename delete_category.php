<?php
include 'config.php';
$id = $_GET['id'];
$conn->query("DELETE FROM categories WHERE id=$id");
header("Location: manage_categories.php");
