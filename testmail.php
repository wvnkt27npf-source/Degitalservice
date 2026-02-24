<?php
session_start();

// Default message
$message = '';

// Test connection functionality
function testSMTPConnection() {
    // Change these parameters as per your SMTP server
    $smtpHost = 'mail.degitalservice.com';
    $smtpPort = 465;
    $smtpUsername = 'support@degitalservice.com';
    $smtpPassword = '1212@Rahul';

    // Attempt to connect to the SMTP server
    $connection = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);

    if ($connection) {
        fclose($connection);
        return true; // Connection successful
    } else {
        return false; // Connection failed
    }
}

// Send Test Email functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_mail'])) {
    $recipientEmail = trim($_POST['email']);

    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    } else {
        if (sendTestEmail($recipientEmail)) {
            $message = 'Test email sent successfully!';
        } else {
            $message = 'Failed to send test email.';
        }
    }
}

// Function to send test email
function sendTestEmail($email) {
    $subject = "Test Email from PHP";
    $body = "This is a test email to check the email sending functionality.";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@example.com" . "\r\n";
    $headers .= "Reply-To: no-reply@example.com" . "\r\n";

    // Send email
    return mail($email, $subject, $body, $headers);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMTP Connection and Send Test Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
            transition: background-color 0.3s;
        }
        .button.green {
            background-color: #4CAF50;
            color: white;
        }
        .button.red {
            background-color: #f44336;
            color: white;
        }
        .button:disabled {
            background-color: #ccc;
        }
        .input-email {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .message {
            text-align: center;
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Test SMTP Connection and Send Test Email</h2>

    <!-- Test Connection Button -->
    <form method="POST">
        <button type="submit" name="test_connection" class="button" id="testConnectionBtn">Test Connection</button>
    </form>

    <!-- Send Test Email Form -->
    <form method="POST">
        <input type="email" name="email" class="input-email" placeholder="Enter email address" required>
        <button type="submit" name="send_test_mail" class="button">Send Test Email</button>
    </form>

    <div class="message">
        <?php
        // Display connection status
        if (isset($_POST['test_connection'])) {
            if (testSMTPConnection()) {
                echo '<span class="green">Connection Successful!</span>';
                echo '<script>document.getElementById("testConnectionBtn").classList.add("green");</script>';
            } else {
                echo '<span class="red">Connection Failed!</span>';
                echo '<script>document.getElementById("testConnectionBtn").classList.add("red");</script>';
            }
        }

        // Display result of sending test email
        if (!empty($message)) {
            echo $message;
        }
        ?>
    </div>
</div>

</body>
</html>
