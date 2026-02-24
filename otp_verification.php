<?php
session_start();
include './config.php'; // Database Connection

$message = '';

// Handle OTP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $entered_otp = trim($_POST['otp']);

    if ($entered_otp == $_SESSION['otp']) {
        // Get user details from session
        $user = $_SESSION['temp_user'];

        // Save user details to the database including phone and state
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, phone, state, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user['username'], $user['password'], $user['email'], $user['phone'], $user['state'], $role);
        $role = 'user'; // Default role

        if ($stmt->execute()) {
            // Clear temporary session data
            unset($_SESSION['temp_user'], $_SESSION['otp']);

            // Automatically log the user in
            $_SESSION['user_id'] = $conn->insert_id; // Assuming 'id' is auto-incremented
            $_SESSION['role'] = 'user'; // Set role to 'user'
            $_SESSION['username'] = $user['username']; // Set username

            // Set success message for the front-end
            $_SESSION['registration_success'] = true;

            // Redirect to the same page to prevent resubmission of form
            header("Location: otp_verification.php");
            exit(); // Ensure no further code is executed
        } else {
            $message = "Failed to complete registration. Please try again.";
        }

        $stmt->close();
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}


// Resend OTP functionality
if (isset($_POST['resend_otp'])) {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;

    if (sendOtpEmail($_SESSION['temp_user']['email'], $otp)) {
        $message = "OTP has been resent to your email.";
    } else {
        $message = "Failed to resend OTP. Please try again.";
    }
}

// Function to send OTP email
function sendOtpEmail($email, $otp) {
    $subject = "Your OTP for Registration";
    $body = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .email-container {
                width: 100%;
                max-width: 600px;
                margin: 0 auto;
                background-color: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                text-align: center;
                margin-bottom: 20px;
            }
            .email-header h1 {
                font-size: 2rem;
                color: #007bff;
                margin: 0;
            }
            .email-content {
                font-size: 1rem;
                color: #333;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .otp-box {
                background-color: #f9f9f9;
                padding: 20px;
                border-radius: 5px;
                border: 1px solid #ddd;
                text-align: center;
                font-size: 1.5rem;
                font-weight: bold;
                color: #333;
                margin-bottom: 20px;
            }
            .footer {
                text-align: center;
                font-size: 0.9rem;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <h1>Digital Service</h1>
            </div>
            <div class="email-content">
                <p>Dear ' . htmlspecialchars($_SESSION['temp_user']['username']) . ',</p>
                <p>Thank you for registering with Digital Service. Below is your One-Time Password (OTP) for verification:</p>
                <div class="otp-box">
                    OTP: <strong>' . $otp . '</strong>
                </div>
                <p>Please use this OTP to complete your registration. If you did not request this, kindly ignore this email.</p>
            </div>
            <div class="footer">
                <p>Best regards,</p>
                <p>The Digital Service Team</p>
                <p>For support, contact us at <a href="mailto:support@degitalservice.com">support@degitalservice.com</a></p>
            </div>
        </div>
    </body>
    </html>';

    // Set headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: admin@degitalservice.com" . "\r\n";
    $headers .= "Reply-To: support@degitalservice.com" . "\r\n";

    // Send the email
    return mail($email, $subject, $body, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <link rel="stylesheet" href="otp_verification.css">
</head>
<body>

<div class="container">
    <h2>OTP Verification</h2>

    <?php if (!empty($message)): ?>
        <div class="error-message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" id="otpForm">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit">Verify OTP</button>
    </form>


    <!-- Resend OTP Button -->
    <form method="POST" id="resendOtpForm">
        <button type="submit" name="resend_otp" id="resendOtpButton">Resend OTP</button>
    </form>

    <!-- Verification Success Popup -->
    <div id="successPopup">
        <img src="uploads/verified.gif" alt="Verified">
        <p>Registration successful! Redirecting...</p>
        <button class="ok-button" onclick="window.location.href='order_service.php';">OK</button>
    </div>
</div>

<script>
    // Check if registration was successful and show success popup after a successful OTP verification
    <?php if (isset($_SESSION['registration_success']) && $_SESSION['registration_success'] === true): ?>
        setTimeout(function() {
            document.getElementById('otpForm').style.display = 'none'; // Hide OTP form
            document.getElementById('resendOtpForm').style.display = 'none'; // Hide Resend OTP button
            document.getElementById('successPopup').style.display = 'block'; // Show success popup
        }, 500); // Short delay before showing the popup
        <?php unset($_SESSION['registration_success']); // Clear the session variable after showing popup ?>

        // Redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'index.php'; // Auto-redirect to order_service.php
        }, 5000);
    <?php endif; ?>
</script>

</body>
</html>
