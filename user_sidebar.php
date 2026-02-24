<?php
// Get current page name to set active class
$current_page = basename($_SERVER['PHP_SELF']);
$username = $_SESSION['username'] ?? 'User';
?>

<style>
    /* --- DESKTOP SIDEBAR STYLES --- */
    .user-sidebar {
        width: 250px;
        background: #fff;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        border-right: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: 0.3s;
    }

    .sidebar-brand {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #f3f4f6;
    }

    .sidebar-user {
        padding: 20px;
        text-align: center;
        background: #f9fafb;
    }

    .sidebar-user p { margin: 0; font-weight: 600; color: #374151; }
    .badge-user { 
        background: #e0f2fe; color: #0369a1; 
        padding: 2px 8px; border-radius: 10px; font-size: 12px; 
    }

    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
        overflow-y: auto;
        flex-grow: 1;
    }

    .sidebar-nav li a {
        display: block;
        padding: 15px 20px;
        color: #4b5563;
        text-decoration: none;
        font-weight: 500;
        border-left: 4px solid transparent;
        transition: 0.2s;
    }

    .sidebar-nav li a:hover, 
    .sidebar-nav li a.active {
        background: #eff6ff;
        color: #2563eb;
        border-left-color: #2563eb;
    }

    .sidebar-nav li a i { margin-right: 10px; }

    /* --- MOBILE BOTTOM NAV STYLES --- */
    .mobile-bottom-nav {
        display: none; /* Hidden by default on desktop */
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #0b1a27d9;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        z-index: 2000;
        justify-content: space-around;
        padding: 10px 0;
        border-top: 1px solid #e5e7eb;
    }

    .nav-item {
        text-align: center;
        text-decoration: none;
        color: #64748b;
        font-size: 11px;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 20%;
    }

    .nav-item i {
        font-size: 20px;
        margin-bottom: 4px;
        display: block;
    }

    .nav-item.active {
        color: #2563eb;
        font-weight: 600;
    }

    /* --- MEDIA QUERIES --- */
    @media (max-width: 768px) {
        /* Hide Desktop Sidebar */
        .user-sidebar { display: none; }
        
        /* Show Mobile Bottom Nav */
        .mobile-bottom-nav { display: flex; }

        /* Adjust Main Content Margin for Mobile */
        .dashboard-wrapper, main {
            margin-left: 0 !important;
            padding-bottom: 80px !important; /* Space for bottom nav */
        }
    }

    @media (min-width: 769px) {
        /* Add margin to body/main to prevent overlap with sidebar */
        body { margin-left: 250px; }
    }
</style>

<div class="user-sidebar">
    <div class="sidebar-brand">
        <img src="uploads/Logo.webp" alt="Logo" width="120">
    </div>
    
    <div class="sidebar-user">
        <p>Hello, <strong><?= htmlspecialchars($username); ?></strong></p>
        <span class="badge-user">Client Panel</span>
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="user_dashboard.php" class="<?= ($current_page == 'user_dashboard.php') ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
        </li>
        <li>
            <a href="category.php" class="<?= ($current_page == 'category.php') ? 'active' : ''; ?>">
                🛍️ All Services
            </a>
        </li>
        <li>
            <a href="user_orders.php" class="<?= ($current_page == 'user_orders.php') ? 'active' : ''; ?>">
                📦 My Orders
            </a>
        </li>
        <li>
            <a href="user_documents.php" class="<?= ($current_page == 'user_documents.php') ? 'active' : ''; ?>">
                📄 My Documents
            </a>
        </li>
        <li>
            <a href="CustomRequestuser.php" class="<?= ($current_page == 'CustomRequestuser.php') ? 'active' : ''; ?>">
                📩 My Requests
            </a>
        </li>
        <li>
            <a href="user_profile.php" class="<?= ($current_page == 'user_profile.php') ? 'active' : ''; ?>">
                👤 My Profile
            </a>
        </li>
        <li style="margin-top: auto; border-top: 1px solid #f3f4f6;">
            <a href="logout.php" style="color: #ef4444;">
                🚪 Logout
            </a>
        </li>
    </ul>
</div>

<div class="mobile-bottom-nav">
    <a href="user_dashboard.php" class="nav-item <?= ($current_page == 'user_dashboard.php') ? 'active' : ''; ?>">
        <i>📊</i>
        <span>Home</span>
    </a>
    
    <a href="category.php" class="nav-item <?= ($current_page == 'category.php') ? 'active' : ''; ?>">
        <i>🛍️</i>
        <span>Shop</span>
    </a>
    
    <a href="user_orders.php" class="nav-item <?= ($current_page == 'user_orders.php') ? 'active' : ''; ?>">
        <i>📦</i>
        <span>Orders</span>
    </a>

    <a href="CustomRequestuser.php" class="nav-item <?= ($current_page == 'CustomRequestuser.php') ? 'active' : ''; ?>">
        <i>📩</i>
        <span> My Requests</span>
    </a>

    <a href="user_profile.php" class="nav-item <?= ($current_page == 'user_profile.php') ? 'active' : ''; ?>">
        <i>👤</i>
        <span>Profile</span>
    </a>
</div>