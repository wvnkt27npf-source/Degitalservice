<?php
// STEP 1: ERROR LOGGING CHALU KAREIN
ini_set('display_errors', 0); // User ko error na dikhayein
ini_set('log_errors', 1); // Errors ko log karein
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); // Log file ka path
error_reporting(E_ALL); // Sabhi errors log karein

session_start();
include './config.php';
// 'header.php' KO YAHAN SE HATA KAR NEECHE MOVE KIYA GAYA HAI

// STEP 2: AUTHENTICATION CHECK (Redirect Fix)
// Yeh check header include hone se PEHLE hona zaroori hai
$loggedInUserRoles = ['user', 'client', 'customer']; // Sabhi logged-in roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? 'guest', $loggedInUserRoles)) {
    header("Location: login.php"); // Agar login nahi hai ya admin hai, toh redirect karein
    exit();
}

// Ab jab authentication check ho gaya hai, hum header ko safely include kar sakte hain
include './header.php';

// Fetch the user's current details from the database
$userId = $_SESSION['user_id']; // YEH VARIABLE NAAM (userId) AAPKE ORIGINAL CODE SE LIYA GAYA HAI
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userDetails = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch list of Indian States for the dropdown
$states = [
    "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", "Chhattisgarh",
    "Goa", "Gujarat", "Haryana", "Himachal Pradesh", "Jharkhand", "Karnataka",
    "Kerala", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", "Mizoram",
    "Nagaland", "Odisha", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", "Telangana",
    "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal"
];

$message = '';
$is_error = false; // Error message ko track karne ke liye
$otp_sent = false;
$show_forgot_password_email_only = false;
$email_verified = true; // default true unless email changed and OTP pending

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Email OTP verification step
    if (isset($_POST['verify_otp'])) {
        $inputOtp = trim($_POST['otp']);
        $storedOtp = $_SESSION['email_otp'] ?? '';
        if ($inputOtp === $storedOtp) {
            $_SESSION['email_verified'] = true;
            $email_verified = true;
            $message = "Email verified successfully! Please update your profile now.";
            $is_error = false;
        } else {
            $message = "Invalid OTP. Please try again.";
            $is_error = true;
            $email_verified = false;
        }
    } 
    
    // 2. User requests OTP for new email
    else if (isset($_POST['send_otp'])) {
        $emailToVerify = trim($_POST['email_verify']);
        if (preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $emailToVerify)) {
            $otp = random_int(100000, 999999);
            $_SESSION['email_otp'] = (string)$otp;
            $_SESSION['email_new'] = $emailToVerify;
            $_SESSION['email_verified'] = false;
            $email_verified = false;
            $show_forgot_password_email_only = false;

            $subject = "Your Email Verification OTP";
            $msg = "Your OTP for email verification is: $otp. It is valid for 10 minutes.";
            $headers = "From: no-reply@degitalservice.com";
            mail($emailToVerify, $subject, $msg, $headers);

            $message = "OTP sent to your new Gmail address. Please enter OTP to verify.";
            $is_error = false;
            $otp_sent = true;
        } else {
            $message = "Only Gmail addresses are allowed. Please enter a valid Gmail account.";
            $is_error = true;
            $email_verified = false;
        }
    } 
    
    // 3. NAYA FEATURE: Role Change Request
    else if (isset($_POST['request_role_change'])) {
        try {
            $requested_role = $_POST['requested_role'];
            $reason_message = trim($_POST['reason_message']);
            $allowed_roles_request = ['user', 'client', 'customer'];

            if (empty($reason_message)) {
                $message = "Lagega, kripaya batayein ki aap role kyu badalna chahte hain.";
                $is_error = true;
            } elseif (!in_array($requested_role, $allowed_roles_request)) {
                $message = "Aapne galat role chuna hai.";
                $is_error = true;
            } else {
                // Check for existing pending request
                $check_stmt = $conn->prepare("SELECT id FROM `role_change_requests` WHERE `user_id` = ? AND `status` = 'Pending'");
                $check_stmt->bind_param("i", $userId); // $userId variable (capital 'I')
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $message = "Aapki ek role change request pehle se hi pending hai.";
                    $is_error = true;
                } else {
                    
                    // ===== YEH RAHI FIX KI HUI QUERY (BACKTICKS KE SAATH) =====
                    $insert_stmt = $conn->prepare(
                        "INSERT INTO `role_change_requests` (`user_id`, `username`, `user_phone`, `current_role`, `requested_role`, `reason_message`) 
                         VALUES (?, ?, ?, ?, ?, ?)"
                    );
                    // ==============================================================
                    
                    $insert_stmt->bind_param("isssss", $userId, $userDetails['username'], $userDetails['phone'], $userDetails['role'], $requested_role, $reason_message); // $userId variable (capital 'I')
                    
                    if ($insert_stmt->execute()) {
                        $message = "Aapki role change request bhej di gayi hai. Admin jald hi isse review karega.";
                        $is_error = false;
                    } else {
                        $message = "Request bhejne mein koi samasya hui. (DB Error)";
                        $is_error = true;
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        } catch (Exception $e) {
            error_log("Role Request Error: " . $e->getMessage());
            $message = "Ek technical samasya aa gayi hai. Kripaya baad mein try karein.";
            $is_error = true;
        }
    }
    
    // 4. Password change handling
    else if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];

        if (password_verify($currentPassword, $userDetails['password'])) {
            if (strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashedPassword, $userId);
                if ($stmt->execute()) {
                    $message = "Password updated successfully!";
                    $is_error = false;
                } else {
                    $message = "Failed to update password. Please try again.";
                    $is_error = true;
                }
                $stmt->close();
            } else {
                $message = "New password must be at least 8 characters long.";
                $is_error = true;
            }
        } else {
            $message = "Current password is incorrect.";
            $is_error = true;
        }
    }
    
    // 5. Default action: Profile update (Jab koi doosra button nahi daba ho)
    else if (isset($_POST['username'])) { // Check karein ki profile update form submit hua hai
        // Sanitize inputs
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $state = isset($_POST['state']) ? trim($_POST['state']) : '';
        $selectedImage = isset($_POST['selected_image']) ? $_POST['selected_image'] : '';

        // Validate phone length
        if (!preg_match('/^\d{10}$/', $phone)) {
            $message = 'Phone number must be exactly 10 digits.';
            $is_error = true;
        } elseif (!empty($email) && !preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/i', $email)) {
            $message = "Only Gmail addresses are allowed.";
            $is_error = true;
            $email_verified = false;
        } else {
            // Check if new email differs from old and if email_verified flag is set
            if (($email !== $userDetails['email']) && !($_SESSION['email_verified'] ?? true)) {
                // Prompt OTP verification for email change
                $show_forgot_password_email_only = true;
                $message = "Aap email badal rahe hain. Kripaya naya email verify karein.";
                $is_error = true;
            } else {
                // Email verified or unchanged, proceed with update
                // Check if username exists for other users
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->bind_param("si", $username, $userId);
                $stmt->execute();
                $existingUser = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existingUser) {
                    $message = "Username already taken. Please choose a different username.";
                    $is_error = true;
                } else {
                    // Prepare profile image path
                    $profileImage = !empty($selectedImage) ? 'uploads/profile_images/' . $selectedImage : $userDetails['profile_image'];

                    // Update user data
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone = ?, state = ?, profile_image = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $username, $email, $phone, $state, $profileImage, $userId);
                    if ($stmt->execute()) {
                        $message = "Profile updated successfully!";
                        $is_error = false;
                        $_SESSION['email_verified'] = true;
                        // Refresh user details
                        $userDetails['email'] = $email;
                        $userDetails['username'] = $username;
                        $userDetails['phone'] = $phone;
                        $userDetails['state'] = $state;
                        $userDetails['profile_image'] = $profileImage;
                    } else {
                        $message = "Failed to update profile. Please try again.";
                        $is_error = true;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Fetch user's role change request history (for live status)
$history_stmt = $conn->prepare("SELECT `requested_role`, `reason_message`, `status`, `admin_notes`, `created_at` FROM `role_change_requests` WHERE `user_id` = ? ORDER BY `created_at` DESC");
$history_stmt->bind_param("i", $userId);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Profile - <?= htmlspecialchars($userDetails['username']); ?></title>
<link rel="stylesheet" href="userprofile.css">

<style>
    /* user_profile.css (Basic Styles) */
    body {
        background-color: #f4f7f6;
        font-family: Arial, sans-serif;
    }
    .container, .profile-container {
        max-width: 800px;
        margin: 30px auto;
        padding: 25px;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    h2 {
        text-align: center;
        color: #333;
        margin-bottom: 25px;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
    }
    /* Profile Header (Display) */
    .profile-header {
        text-align: center;
        margin-bottom: 30px;
        background: #fdfdfd;
        border: 1px solid #eaeaea;
        padding: 20px;
        border-radius: 8px;
    }
    .profile-pic {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f0f0f0;
        margin-bottom: 15px;
    }
    .profile-header h3 {
        margin: 10px 0;
        font-size: 1.8rem;
        color: #2c3e50;
    }
    .profile-header p {
        font-size: 1rem;
        color: #555;
        line-height: 1.6;
        margin: 5px 0;
    }
    .profile-header p strong {
        color: #333;
    }
    /* Messages */
    .message {
        padding: 12px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    .message-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .message-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    /* Forms */
    form {
        margin-bottom: 20px;
        background: #fff;
        border: 1px solid #eee;
        padding: 25px;
        border-radius: 8px;
    }
    form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #444;
    }
    form input[type="text"],
    form input[type="email"],
    form input[type="password"],
    form select,
    form textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 0.95rem;
    }
    form button {
        background-color: #007bff;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        display: block;
        width: 100%;
        margin-top: 15px;
    }
    form button:hover {
        background-color: #0056b3;
    }
    /* Image Selection */
    .profile-image-selection {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }
    .image-option {
        cursor: pointer;
        border: 2px solid transparent;
        border-radius: 50%;
        padding: 3px;
        transition: border-color 0.2s;
    }
    .image-option input[type="radio"] {
        display: none;
    }
    .image-option img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
    }
    .image-option input[type="radio"]:checked + img {
        border: 3px solid #007bff;
        box-shadow: 0 0 10px rgba(0,123,255,0.5);
    }
    /* Password Popup */
    .popup {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
    }
    .popup-content {
        background-color: #fff;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        position: relative;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        color: #aaa;
        background: none;
        border: none;
        cursor: pointer;
    }
    
    /* Role Change Form */
    .role-change-form {
        background: #fdfdfd;
        border: 1px solid #eaeaea;
        border-radius: 8px;
        padding: 25px;
        margin-top: 30px;
    }
    .role-change-form h3 {
        margin-top: 0;
        color: #333;
        text-align: center;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 20px;
        text-align: left;
    }
    .form-group textarea {
        height: 120px;
        resize: vertical;
    }
    .btn-submit-request {
        background-color: #28a745; /* Green color for request */
    }
    .btn-submit-request:hover {
        background-color: #218838;
    }

    /* Request History Table */
    .request-history {
        margin-top: 40px;
        background: #fff;
        border: 1px solid #eee;
        padding: 25px;
        border-radius: 8px;
    }
    .request-history h3 {
        color: #333;
        text-align: center;
        margin-bottom: 20px;
    }
    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 0.9rem;
    }
    .history-table th, 
    .history-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
        line-height: 1.5;
    }
    .history-table th {
        background-color: #f9f9f9;
        font-weight: bold;
    }
    .history-table tr:nth-child(even) {
        background-color: #fcfcfc;
    }
    .status-pending { 
        color: #f39c12; 
        font-weight: bold;
    }
    .status-approved { 
        color: #2ecc71; 
        font-weight: bold;
    }
    .status-rejected { 
        color: #e74c3c; 
        font-weight: bold;
    }
    /* Mobile Optimization for User Profile */
@media (max-width: 768px) {
    .container, .profile-container {
        margin: 10px;
        padding: 15px;
        width: auto;
    }

    .profile-header h3 {
        font-size: 1.4rem;
    }

    /* Profile Image Selection Fix */
    .profile-image-selection {
        justify-content: center; /* Images center mein dikhengi */
        gap: 8px;
    }

    .image-option img {
        width: 50px; /* Choti screen par size kam kiya */
        height: 50px;
    }

    /* Form Optimization */
    form {
        padding: 15px;
    }

    /* Table Responsiveness Fix */
    .request-history {
        overflow-x: auto; /* Side scroll bar for table */
        padding: 10px;
    }

    .history-table {
        min-width: 600px; /* Table ko sikadne se rokega */
        font-size: 0.8rem;
    }

    .history-table th, .history-table td {
        padding: 8px;
    }

    /* Popup Fix */
    .popup-content {
        width: 95%;
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .profile-header {
        padding: 10px;
    }
    
    .profile-pic {
        width: 100px;
        height: 100px;
    }

    button, form button {
        padding: 10px;
        font-size: 0.9rem;
    }
}
</style>
</head>
<body>

<div class="container">
    <h2>User Profile</h2>
    
    <?php if ($message): ?>
        <div class="message <?= $is_error ? 'message-error' : 'message-success' ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($show_forgot_password_email_only && !$email_verified): ?>
        <form method="POST" action="user_profile.php" novalidate>
            <label for="email_verify">Enter your new Gmail address to verify:</label>
            <input 
                type="email" 
                id="email_verify" 
                name="email_verify" 
                pattern="[a-z0-9._%+-]+@gmail\.com$"
                title="Only Gmail addresses allowed"
                required 
                value="<?= htmlspecialchars($_SESSION['email_new'] ?? '') ?>"
                autofocus
            >
            <?php if ($otp_sent): ?>
            <label for="otp">Enter OTP:</label>
            <input type="text" id="otp" name="otp" pattern="\d{6}" maxlength="6" required autocomplete="one-time-code">
            <button type="submit" name="verify_otp">Verify OTP</button>
            <?php else: ?>
            <button type="submit" name="send_otp">Send OTP</button>
            <?php endif; ?>
        </form>
    
    <?php else: ?>
        <div class="profile-header">
            <img src="<?= htmlspecialchars($userDetails['profile_image'] ?: 'uploads/profile_images/default.png') ?>" alt="Profile Image" class="profile-pic">
            <h3><?= htmlspecialchars($userDetails['username']) ?></h3>
            <p><strong>Aapka Vartamaan Type:</strong> <strong style="color: #0056b3; text-transform: capitalize;"><?= htmlspecialchars($userDetails['role']) ?></strong></p>
        </div>

        <form method="POST" action="user_profile.php" novalidate>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($userDetails['username']); ?>" autocomplete="username">

            <label for="email">Email (Gmail only)</label>
            <input type="email" id="email" name="email" required pattern="[a-z0-9._%+-]+@gmail\.com$" title="Only Gmail addresses allowed" value="<?= htmlspecialchars($userDetails['email']); ?>" autocomplete="email">

            <label for="phone">Phone (10 digits)</label>
            <input type="text" id="phone" name="phone" required pattern="\d{10}" maxlength="10" title="Enter exactly 10 digits" value="<?= htmlspecialchars($userDetails['phone']); ?>" autocomplete="tel-national">

            <label for="state">State</label>
            <select id="state" name="state" required>
                <?php foreach ($states as $stateOption): ?>
                <option value="<?= htmlspecialchars($stateOption); ?>" <?= $stateOption === $userDetails['state'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($stateOption); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <label>Select Profile Image</label>
            <div class="profile-image-selection" role="radiogroup" aria-label="Profile image selection">
                <?php
                $images = ['female1.png', 'female2.png', 'female3.png','female4.png','female5.png', 'male1.png', 'male2.png', 'male3.png', 'male4.png','male5.png'];
                foreach ($images as $image) {
                    $checked = ($userDetails['profile_image'] === 'uploads/profile_images/' . $image) ? 'checked' : '';
                    $id_radio = 'profile_img_' . pathinfo($image, PATHINFO_FILENAME);
                    ?>
                    <label class="image-option" for="<?= $id_radio ?>">
                        <input 
                            type="radio" 
                            id="<?= $id_radio ?>" 
                            name="selected_image" 
                            value="<?= htmlspecialchars($image) ?>" 
                            <?= $checked ?>>
                        <img src="uploads/profile_images/<?= htmlspecialchars($image) ?>" alt="Profile image <?= htmlspecialchars($image) ?>" class="profile-image">
                    </label>
                <?php } ?>
            </div>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>
        
        <button style="margin-top:1.5rem; width: 100%; background-color: #6c757d;" onclick="openPopup()">Change Password</button>

        <div class="role-change-form">
            <form method="POST" action="user_profile.php">
                <h3>Role Badalne Ke Liye Request Karein</h3>
                <div class="form-group">
                    <label for="requested_role">Naya Role Chunein (Aap kya banna chahte hain?):</label>
                    <select id="requested_role" name="requested_role" required>
                        <option value="user" <?= ($userDetails['role'] == 'user') ? 'disabled' : '' ?>>User</option>
                        <option value="client" <?= ($userDetails['role'] == 'client') ? 'disabled' : '' ?>>Client</option>
                        <option value="customer" <?= ($userDetails['role'] == 'customer') ? 'disabled' : '' ?>>Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reason_message">Kaaran (Aap role kyu badalna chahte hain?):</label>
                    <textarea id="reason_message" name="reason_message" rows="4" placeholder="Kripaya yahaan vistar se batayein..." required></textarea>
                </div>
                <button type="submit" name="request_role_change" class="btn-submit-request">Request Bhejein</button>
            </form>
        </div>

        <div class="request-history">
            <h3>Aapki Role Change Requests Ka Status</h3>
            <?php if ($history_result->num_rows > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Request Kab Ki</th>
                            <th>Kaunsa Role Chaha</th>
                            <th>Aapka Kaaran</th>
                            <th>Status (Live)</th>
                            <th>Admin Ka Jawab</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
                                <td style="text-transform: capitalize;"><?= htmlspecialchars($row['requested_role']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['reason_message'])) ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    if ($row['status'] == 'Pending') $status_class = 'status-pending';
                                    if ($row['status'] == 'Approved') $status_class = 'status-approved';
                                    if ($row['status'] == 'Rejected') $status_class = 'status-rejected';
                                    ?>
                                    <span class="<?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                                <td><?= nl2br(htmlspecialchars($row['admin_notes'] ?: 'N/A')) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #777;">Aapne abhi tak koi role change request nahi ki hai.</p>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    </div>

<div class="popup" id="passwordPopup" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
    <div class="popup-content">
        <button class="close-btn" aria-label="Close password change form" onclick="closePopup()">&times;</button>
        <h3 id="popupTitle">Change Your Password</h3>
        <form method="POST" action="user_profile.php" novalidate>
            <label for="current_password">Current Password:</label>
            <input type="password" name="current_password" id="current_password" required autocomplete="current-password">

            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required autocomplete="new-password" minlength="8">

            <button type="submit" name="change_password" class="submit-btn" style="margin-top:1rem;">Submit</button>
        </form>
    </div>
</div>

<script>
    function openPopup() {
        document.getElementById("passwordPopup").style.display = "flex";
        document.getElementById("current_password").focus();
    }
    function closePopup() {
        document.getElementById("passwordPopup").style.display = "none";
    }
</script>

</body>
</html>
<?php include 'footer.php'; ?>