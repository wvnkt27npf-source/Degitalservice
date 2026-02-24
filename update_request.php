<?php
session_start();
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

include './config.php';
include 'header.php';

// Fetch the request data based on ID
if (isset($_GET['id'])) {
    $request_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT cr.*, u.username FROM custom_requests cr JOIN users u ON cr.user_id = u.id WHERE cr.id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo "<script>alert('Request not found!'); window.location.href = 'CustomRequestadmin.php';</script>";
        exit();
    }

    $row = $result->fetch_assoc();
}

// Handle admin action (update request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $admin_message = isset($_POST['admin_message']) ? $_POST['admin_message'] : null;
    $response_link = isset($_POST['response_link']) ? $_POST['response_link'] : null;
    $response_file = null;

    if (!empty($_FILES['response_file']['name'])) {
        $response_file = time() . "_" . $_FILES['response_file']['name'];
        move_uploaded_file($_FILES['response_file']['tmp_name'], "./uploads/$response_file");
    }

    // Update the request in the database
    $stmt = $conn->prepare("UPDATE custom_requests SET status = ?, admin_message = ?, response_link = ?, response_file = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $status, $admin_message, $response_link, $response_file, $request_id);

    if ($stmt->execute()) {
        echo "<script>alert('Request updated successfully!'); window.location.href = 'CustomRequestadmin.php';</script>";
    } else {
        echo "<script>alert('Failed to update request!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Update Request</title>
    <link rel="stylesheet" href="./style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 70%;
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
            margin-top: 15px;
            display: block;
        }

        select, textarea, input[type="url"], input[type="file"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0 20px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: #4CAF50;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-right: 4%;
        }

        button:hover {
            background-color: #45a049;
        }

        .cancel-btn {
            background-color: #f44336; /* Red color */
            margin-right: 0;
        }

        .cancel-btn:hover {
            background-color: #e53935;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .alert {
            padding: 10px;
            color: #fff;
            background-color: #d9534f;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #5bc0de;
        }

        .alert-error {
            background-color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Request - ID: <?= $row['id'] ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="status">Status:</label>
                <select name="status" required>
                    <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Accepted" <?= $row['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="Declined" <?= $row['status'] === 'Declined' ? 'selected' : '' ?>>Declined</option>
                </select>
            </div>

            <div class="form-group">
                <label for="admin_message">Admin Message:</label>
                <textarea name="admin_message" placeholder="Enter your message"><?= $row['admin_message'] ?></textarea>
            </div>

            <div class="form-group">
                <label for="response_link">Response Link (Optional):</label>
                <input type="url" name="response_link" placeholder="Enter link" value="<?= $row['response_link'] ?>">
            </div>

            <div class="form-group">
                <label for="response_file">Upload File (Optional):</label>
                <input type="file" name="response_file">
            </div>

            <div style="display: flex; justify-content: space-between;">
                <button type="submit">Update Request</button>
                <a href="CustomRequestadmin.php">
                    <button type="button" class="cancel-btn">Cancel</button>
                </a>
            </div>
        </form>
    </div>
</body>
</html>

<?php include 'footer.php'; ?>
