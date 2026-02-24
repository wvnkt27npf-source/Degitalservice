<?php
session_start();
include './config.php'; // Database Connection
$error = '';
$blockedMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['login_input']);
    $password = trim($_POST['password']);

    if (!empty($loginInput) && !empty($password)) {
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT id, username, password, role, status, block_reason FROM users WHERE email = ?");
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, role, status, block_reason FROM users WHERE username = ?");
        }

        $stmt->bind_param("s", $loginInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            if ($row['status'] === 'blocked') {
                $blockedMessage = $row['block_reason'];
            } else {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['username'] = $row['username'];

                    if ($row['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        // --- REDIRECT LOGIC FOR USERS ---
                        if (isset($_SESSION['place_order_service_id']) && isset($_SESSION['redirect_after_login'])) {
                            $service_id = $_SESSION['place_order_service_id'];
                            $redirect_url = $_SESSION['redirect_after_login'];
                            
                            unset($_SESSION['place_order_service_id']);
                            unset($_SESSION['redirect_after_login']);

                            // Auto-place order logic
                            $user_id = $_SESSION['user_id'];
                            $service_query = $conn->prepare("SELECT price FROM services WHERE id = ?");
                            $service_query->bind_param("i", $service_id);
                            $service_query->execute();
                            $service_result = $service_query->get_result();

                            if ($service_result->num_rows > 0) {
                                $service = $service_result->fetch_assoc();
                                $service_price = $service['price'];

                                $order_query = $conn->prepare("INSERT INTO orders (user_id, service_id, price, status) VALUES (?, ?, ?, 'Pending')");
                                $order_query->bind_param("iid", $user_id, $service_id, $service_price);
                                
                                if ($order_query->execute()) {
                                    $_SESSION['order_success_flash'] = true;
                                } else {
                                    $_SESSION['order_fail_flash'] = "Error placing order after login.";
                                }
                            }
                            header("Location: " . $redirect_url);
                            exit();
                        } else {
                            header("Location: category.php");
                            exit();
                        }
                    }
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Service</title>
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

        /* Login Card */
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
        .login-logo {
            max-width: 180px;
            margin-bottom: 10px;
            height: auto;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        .login-box p.title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 25px;
        }

        /* Error Message */
        .error-message {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
        }

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
        .login-btn {
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
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .login-btn:hover {
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
        
        .links p { margin: 8px 0; }

        .links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .links a:hover { text-decoration: underline; color: #1e40af; }

        /* --- Blocked Popup Modal Styles --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            animation: scaleUp 0.3s ease;
            border-top: 6px solid #ef4444; 
        }

        .modal-content h3 { color: #ef4444; font-size: 22px; margin-bottom: 15px; }
        .modal-content p { color: #4b5563; font-size: 15px; margin-bottom: 10px; line-height: 1.5; }
        
        .reason-box {
            background-color: #fef2f2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: 600;
            border: 1px solid #fecaca;
        }

        .close-btn {
            margin-top: 20px;
            padding: 12px 30px;
            background-color: #1f2937;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }
        .close-btn:hover { background-color: #000; }

        /* Animations */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleUp { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }

    </style>
</head>
<body>

    <div id="particles-js"></div>

    <?php if (!empty($blockedMessage)): ?>
    <div class="modal-overlay" id="blockedPopup">
        <div class="modal-content">
            <h3>Account Blocked</h3>
            <p>Your account has been temporarily suspended.</p>
            
            <div class="reason-box">
                Reason: "<?php echo htmlspecialchars($blockedMessage); ?>"
            </div>
            
            <p>If you believe this is an error, please contact support:</p>
            <p style="font-weight:bold; margin-top:10px;">📞 +91 9351545935</p>
            
            <button class="close-btn" onclick="closePopup()">Close</button>
        </div>
    </div>
    <script>
        function closePopup() {
            document.getElementById('blockedPopup').style.display = 'none';
        }
    </script>
    <?php endif; ?>


    <div class="login-box">
        <a href="index.php">
            <img src="uploads/Logo.gif" alt="Logo" class="login-logo">
        </a>

        <p class="title">Welcome Back</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <div class="user-box">
                <label>Username or Email</label>
                <input required name="login_input" type="text" placeholder="Enter your username">
            </div>
            
            <div class="user-box">
                <label>Password</label>
                <input required name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-btn">Login to Dashboard</button>
        </form>

        <div class="links">
            <p>Don't have an account? <a href="register.php">Sign up</a></p>
            <p><a href="forgot_password.php">Forgot password?</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            "particles": {
                "number": { 
                    "value": 160, /* Increased Particles from 80 to 160 */
                    "density": { "enable": true, "value_area": 800 } 
                },
                "color": { "value": "#ffffff" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.4, "random": true }, /* Increased Opacity slightly */
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