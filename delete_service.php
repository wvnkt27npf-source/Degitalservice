<?php
include 'config.php';
$id = $_GET['id'];
$conn->query("DELETE FROM services WHERE id=$id");
header("Location: manage_services.php");
