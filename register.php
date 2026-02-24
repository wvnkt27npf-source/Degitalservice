<?php
session_start();
include './config.php'; // Database Connection

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $state = trim($_POST['state']);
    $role = trim($_POST['role']); 

    // 1. UPDATED: Removed 'user' from allowed roles
    $allowed_roles = ['client', 'customer'];
    
    if (!in_array($role, $allowed_roles)) {
        $message = "Please select a valid role.";
    }
    else if (!empty($username) && !empty($password) && !empty($email) && !empty($phone) && !empty($state) && !empty($role)) {
        
        if (!preg_match("/^\d{10}$/", $phone)) {
            $message = "Please enter a valid 10-digit phone number.";
        }
        else if (strpos($email, '@gmail.com') === false) {
            $message = "Only Gmail email addresses are allowed.";
        } else {
            // Check duplicates
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?");
            $checkStmt->bind_param("sss", $username, $email, $phone);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $message = "Username, email, or phone number already exists.";
            } else {
                // Save to session
                $_SESSION['temp_user'] = [
                    'username' => $username,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'email' => $email,
                    'phone' => $phone,
                    'state' => $state,
                    'role' => $role
                ];

                // OTP Logic
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;

                if (sendOtpEmail($email, $otp)) {
                    header('Location: otp_verification.php');
                    exit();
                } else {
                    $message = "Failed to send OTP. Please try again.";
                }
            }
            $checkStmt->close();
        }
    } else {
        $message = "Please fill in all fields.";
    }
}

// Function to send OTP email
function sendOtpEmail($email, $otp) {
    $subject = "Your OTP for Registration";
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
            .email-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
            .email-header h1 { font-size: 2rem; color: #007bff; margin: 0; text-align:center; }
            .otp-box { background: #f9f9f9; padding: 20px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 1px solid #ddd; margin: 20px 0; }
            .footer { text-align: center; font-size: 0.9rem; color: #777; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header"><h1>Digital Service</h1></div>
            <p>Dear User,</p>
            <p>Thank you for registering. Your OTP is:</p>
            <div class="otp-box">' . $otp . '</div>
            <p class="footer">If you did not request this, please ignore this email.</p>
        </div>
    </body>
    </html>';

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@degitalservice.com" . "\r\n";
    
    return mail($email, $subject, $body, $headers);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Digital Service</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Global Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #1f2937;
            padding: 20px;
            /* Scrollbar handling for longer form */
            overflow-x: hidden;
        }

        /* Particles Container Style */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        /* Register Card */
        .register-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            position: relative;
            z-index: 10;
            margin: 20px 0;
        }

        /* Logo Style */
        .register-logo {
            max-width: 160px;
            margin-bottom: 10px;
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .register-box p.title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 25px;
            text-align: center;
        }

        /* Error Message */
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
            text-align: center;
            font-weight: 500;
        }

        /* Form Fields */
        .user-box {
            margin-bottom: 16px;
        }

        .user-box label {
            font-size: 13px;
            color: #475569;
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        .user-box input, .user-box select {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            color: #111827;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
            background-color: #f9fafb;
        }

        .user-box input:focus, .user-box select:focus {
            border-color: #3b82f6;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        /* Submit Button */
        .register-btn {
            width: 100%;
            padding: 14px;
            background-color: #2563eb;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 15px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .register-btn:hover {
            background-color: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.4);
        }

        /* Links */
        .links {
            margin-top: 25px;
            font-size: 14px;
            color: #64748b;
            text-align: center;
        }

        .links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .links a:hover { text-decoration: underline; color: #1e40af; }

        @media (max-width: 480px) {
            .register-box { padding: 30px 20px; }
        }
    </style>
</head>
<body>

<div id="particles-js"></div>

<div class="register-box">
    <a href="index.php">
        <img src="uploads/Logo.gif" alt="Logo" class="register-logo">
    </a>

    <p class="title">Create Account</p>
    
    <?php if (!empty($message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        <div class="user-box">
            <label>Username</label>
            <input required name="username" type="text" placeholder="Choose a username">
        </div>
        <div class="user-box">
            <label>Password</label>
            <input required name="password" type="password" placeholder="Create a password" autocomplete="new-password">
        </div>
        <div class="user-box">
            <label>Email (Gmail only)</label>
            <input required name="email" type="email" placeholder="example@gmail.com">
        </div>
        <div class="user-box">
            <label>Phone Number</label>
            <input required name="phone" type="text" placeholder="10-digit mobile number" maxlength="10">
        </div>
        
        <div class="user-box">
            <label>Select State</label>
            <select name="state" required>
                <option value="" disabled selected>Choose your state</option>
                <?php
                $states = [
                    "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh", "Goa", "Gujarat", "Haryana", "Himachal Pradesh",
                    "Jharkhand", "Karnataka", "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram", "Nagaland", 
                    "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", 
                    "West Bengal", "Delhi", "Chandigarh"
                ];
                foreach ($states as $state_option) {
                    echo "<option value=\"$state_option\">$state_option</option>";
                }
                ?>
            </select>
        </div>

        <div class="user-box">
            <label>I am a...</label>
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="client">Client (Only License Services)</option>
                <option value="customer">Customer (All Digitel Services)</option>
            </select>
        </div>

        <button type="submit" class="register-btn">Register</button>
    </form>
    
    <div class="links">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": { 
                "value": 160, 
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