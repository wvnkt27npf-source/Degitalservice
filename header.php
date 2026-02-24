<?php
// Start session only if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once './config.php'; 

// 1. Fetch Global Settings
$site_settings = [];
if (isset($conn)) {
    $settings_query = "SELECT * FROM settings";
    $settings_res = $conn->query($settings_query);
    if ($settings_res) {
        while ($s_row = $settings_res->fetch_assoc()) {
            $site_settings[$s_row['setting_key']] = $s_row['setting_value'];
        }
    }
}

// 2. Set Optimized Default SEO Values
if (!isset($page_title)) {
    $page_title = !empty($site_settings['site_title']) ? $site_settings['site_title'] : 'Digital Services | #1 Web Design, DBT POS & Licensing Agency';
}
if (!isset($page_desc)) {
    $page_desc  = !empty($site_settings['site_desc']) ? $site_settings['site_desc'] : 'We provide expert DBT POS software, Krushi license renewals, web development, and IT support services.';
}
if (!isset($page_keys)) {
    $page_keys  = !empty($site_settings['site_keywords']) ? $site_settings['site_keywords'] : 'DBT POS Software, Web Development, IT Support, Krushi License';
}

$userRole = $_SESSION['role'] ?? 'guest'; 
$username = $_SESSION['username'] ?? ''; 
$loggedInUserRoles = ['user', 'client', 'customer']; 

$isUserLoggedIn = in_array($userRole, $loggedInUserRoles);
$isAdminLoggedIn = ($userRole === 'admin');

// Schema Markup (Same as before)
$schemaData = [
    "@context" => "https://schema.org",
    "@type" => "Organization",
    "name" => "Digital Services",
    "url" => "https://degitalservice.com/",
    "logo" => "https://degitalservice.com/uploads/Logo.webp",
    "description" => $page_desc
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if (!empty($site_settings['header_scripts'])) echo $site_settings['header_scripts']; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9904937471916771" crossorigin="anonymous"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= htmlspecialchars($page_title); ?></title> 
    <meta name="description" content="<?= htmlspecialchars($page_desc); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keys); ?>">
    <meta name="robots" content="index, follow"> 
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="header.css">
    
    <?php if ($isAdminLoggedIn): ?>
    <link rel="stylesheet" href="admin_style.css">
    <?php endif; ?>
    
    <link rel="icon" href="uploads/favicon.ico" type="image/x-icon">
    <script type="application/ld+json"><?= json_encode($schemaData); ?></script>

    <?php if ($isUserLoggedIn || $isAdminLoggedIn): ?>
    <style>
        @media (min-width: 769px) {
            .sticky-header {
                left: 250px !important;
                width: calc(100% - 250px) !important;
            }
            .menu-toggle { display: none; } /* Hide Hamburger on Desktop */
        }
        @media (max-width: 768px) {
            .sticky-header { left: 0 !important; width: 100% !important; }
        }
    </style>
    <?php endif; ?>
</head>
<body>

<?php 
// 3. AUTO INCLUDE SIDEBARS
if ($isAdminLoggedIn) {
    include './admin_sidebar.php';
} elseif ($isUserLoggedIn) {
    include './user_sidebar.php';
}
?>

<header class="sticky-header">
    <div class="header-container">
        <div class="logo">
            <a href="https://degitalservice.com/" title="Digital Service">
                <img src="uploads/Logo.webp" alt="Logo" width="150" height="50">
            </a>
        </div>
        
        <div class="menu-toggle" onclick="toggleMenu()" aria-label="Toggle Menu">☰</div>
    </div>

    <nav>
        <ul class="nav-links">
            <?php if ($userRole !== 'guest'): ?>
                <li class="welcome">Welcome, <?= htmlspecialchars($username); ?></li>
            <?php endif; ?>

            <li><a href="tel:+919921060207">+91 9921060207</a></li>

            <?php if ($userRole === 'guest'): ?>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            <?php else: ?>
                <li><a href="logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<script>
    function toggleMenu() {
        const navLinks = document.querySelector(".nav-links");
        navLinks.classList.toggle("active");
        
        // Also toggle Admin Sidebar on Mobile if present
        const adminSidebar = document.getElementById("adminSidebar");
        if (adminSidebar) {
            adminSidebar.classList.toggle("active");
        }
    }
</script>

<main>