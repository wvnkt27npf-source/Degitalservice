<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $number = htmlspecialchars($_POST['number']);
    $message = htmlspecialchars($_POST['message']);

    // Recipient email
    $to = "rahulhaled1545@gmail.com";
    
    // Email subject
    $subject = "New Message from Contact Us Form";

    // Email body
    $body = "You have received a new message from your website's contact form.\n\n";
    $body .= "Name: $name\n";
    $body .= "Phone Number: $number\n";
    $body .= "Message: $message\n";

    // Headers
    $headers = "From: admin@degitalservice.com" . "\r\n";
    $headers .= "Reply-To: $number" . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8" . "\r\n";

    // Send email
    if (mail($to, $subject, $body, $headers)) {
        echo "<script>alert('Message sent successfully!'); window.location.href = 'index.php';</script>";
    } else {
        echo "<script>alert('Message failed to send. Please try again later.'); window.location.href = 'index.php';</script>";
    }
}
?>
