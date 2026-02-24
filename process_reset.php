<?php
session_start();
include './config.php';
include './header.php';

// Check if the reset token is passed in the URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    echo "Invalid or expired token.";
    exit();
}

// Get the token from the URL
$token = $_GET['token'];

// Check if the token exists in the password_resets table and is not expired
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expiration > NOW()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // No valid token found or token expired
    echo "Invalid or expired token.";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Handle the password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';

    // Validate new password
    if (empty($newPassword)) {
        echo "Please enter a new password.";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user's password in the users table
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $user['email']);
        if ($stmt->execute()) {
            // Delete the reset token after successful password update
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $stmt->close();

            // Redirect to login page after password reset
            echo "Your password has been successfully reset. You can now log in with your new password.";
            header("Location: login.php");
            exit();
        } else {
            echo "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="reset_password.css">
</head>
<body>

<div class="container">
    <h2>Reset Your Password</h2>

    <form method="POST" action="process_reset.php?token=<?php echo htmlspecialchars($token); ?>">
        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" id="new_password" required>
        
        <button type="submit">Reset Password</button>
    </form>
</div>

</body>
</html>
