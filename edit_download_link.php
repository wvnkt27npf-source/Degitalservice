<?php
session_start();
include './config.php';
include './header.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if the ID is provided and valid
if (isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    
    // Fetch the current download link for the selected service
    $stmt = $conn->prepare("SELECT name, download_link FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
    } else {
        echo "Service not found!";
        exit;
    }
} else {
    echo "No service selected!";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Download Link</title>
</head>
<body>
<style>/* General Reset */
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body */
body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f7fc;
    color: #333;
    line-height: 1.6;
}

/* Container */
.container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Header */
.container h2 {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    text-align: center;
}

/* Form */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

label {
    font-size: 16px;
    font-weight: 500;
    color: #333;
}

input[type="url"] {
    padding: 12px;
    font-size: 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #f9f9f9;
    color: #333;
    transition: border-color 0.3s ease;
}

input[type="url"]:focus {
    border-color: #007bff;
    outline: none;
}

/* Button */
button.button {
    padding: 12px 20px;
    font-size: 16px;
    color: #fff;
    background-color: #007bff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button.button:hover {
    background-color: #0056b3;
}

/* Cancel Button */
button.cancel-button {
    padding: 12px 20px;
    font-size: 16px;
    color: #fff;
    background-color: #dc3545;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button.cancel-button:hover {
    background-color: #c82333;
}

/* Success/Failure Messages */
.success-message, .error-message {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
}

.success-message {
    background-color: #28a745;
    color: white;
}

.error-message {
    background-color: #dc3545;
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        width: 90%;
        padding: 15px;
    }

    .container h2 {
        font-size: 20px;
    }

    label, input[type="url"], button.button, button.cancel-button {
        font-size: 14px;
    }
}

</style>
<div class="container">
    <h2>Edit Download Link for <?php echo htmlspecialchars($service['name']); ?></h2>

    <form action="update_download_link.php" method="POST">
        <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">

        <label for="download_link">Custom Download Link:</label>
        <input type="url" name="download_link" id="download_link" value="<?php echo htmlspecialchars($service['download_link']); ?>" required>

        <button type="submit" class="button">Update Download Link</button>
        <!-- Cancel Button -->
        <button type="button" class="cancel-button" onclick="window.location.href='admin_set_download_link.php';">Cancel</button>
    
    </form>

</div>

</body>
</html>
