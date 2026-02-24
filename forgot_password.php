<?php
session_start();
include './config.php'; // Database Connection

$message = '';
$msg_type = ''; // To style success/error messages differently

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if the email exists in the database
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            // Generate a unique reset token
            $token = bin2hex(random_bytes(50)); 
            $tokenExpiration = date("Y-m-d H:i:s", strtotime('+1 hour')); 

            // Insert the token into the password_resets table
            $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expiration) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $email, $token, $tokenExpiration);
            $stmt->execute();
            $stmt->close();

            // Send the reset link
            $resetLink = "https://degitalservice.com/reset_password.php?token=" . $token;
            $subject = "Password Reset Request";
            $emailBody = "Hello, \n\nTo reset your password, click the link below:\n$resetLink\n\nIf you didn't request a password reset, please ignore this email.";
            $headers = "From: no-reply@degitalservice.com";

            if (mail($email, $subject, $emailBody, $headers)) {
                $message = "Password reset link has been sent to your email.";
                $msg_type = "success";
            } else {
                $message = "Failed to send the reset link. Please try again.";
                $msg_type = "error";
            }
        } else {
            $message = "No account found with that email address.";
            $msg_type = "error";
        }
    } else {
        $message = "Please enter a valid email address.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Digital Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Global Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #1f2937;
            overflow: hidden; /* Prevent scrollbars due to particles */
        }

        /* Particles Container Style */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1; /* Send to back */
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); /* Deep Navy Theme */
        }

        /* Main Card - Glassy Effect */
        .login-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 400px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.6);
            position: relative;
            z-index: 10;
        }

        /* Logo Style */
        .auth-logo {
            max-width: 160px;
            margin-bottom: 15px;
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .login-box p.title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .login-box p.subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* Message Box */
        .message {
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .message.error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .message.success { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        /* Form Fields */
        .user-box {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .user-box input {
            width: 100%;
            padding: 14px 15px;
            font-size: 15px;
            color: #111827;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
            background-color: #f9fafb;
        }

        .user-box input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            background-color: #fff;
        }

        .user-box label {
            font-size: 14px;
            color: #475569;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        /* Submit Button */
        .reset-btn {
            width: 100%;
            padding: 14px;
            background-color: #2563eb; /* Bright Blue */
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 5px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .reset-btn:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4);
        }

        /* Links */
        .links {
            margin-top: 25px;
            font-size: 14px;
            color: #64748b;
        }

        .links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .links a:hover { text-decoration: underline; color: #1e40af; }

    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="login-box">
    <a href="index.php">
        <img src="uploads/Logo.gif" alt="Logo" class="auth-logo">
    </a>

    <p class="title">Forgot Password?</p>
    <p class="subtitle">Enter your email address and we'll send you a link to reset your password.</p>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="forgot_password.php">
        <div class="user-box">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="Enter your registered email">
        </div>
        
        <button type="submit" class="reset-btn">Send Reset Link</button>
    </form>

    <div class="links">
        <a href="login.php">&larr; Back to Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { 
                "value": 120, /* Balanced count */
                "density": { "enable": true, "value_area": 800 } 
            },
            "color": { "value": "#ffffff" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.4, "random": true },
            "size": { "value": 3, "random": true },
            "line_linked": { "enable": true, "distance": 150, "color": "#a5b4fc", "opacity": 0.3, "width": 1 },
            "move": { "enable": true, "speed": 2, "direction": "none", "random": true, "out_mode": "out" }
        },
        "interactivity": {
            "detect_on": "window",
            "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" } },
            "modes": { "grab": { "distance": 180, "line_linked": { "opacity": 0.6 } } }
        },
        "retina_detect": true
    });
</script>

</body>
</html>