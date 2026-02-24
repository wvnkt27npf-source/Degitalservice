<?php
// Get current page name to set active class
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* --- ADMIN SIDEBAR STYLES --- */
    .admin-sidebar {
        width: 250px;
        background: #ffffff;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        border-right: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: 0.3s;
        box-shadow: 2px 0 10px rgba(0,0,0,0.03);
        overflow-y: auto; /* Scrollable if list is long */
    }

    .sidebar-brand {
        padding: 20px;
        text-align: center;
        border-bottom: 1px solid #f3f4f6;
    }

    .sidebar-user {
        padding: 15px 20px;
        text-align: center;
        background: #f8fafc;
        border-bottom: 1px solid #f3f4f6;
    }

    .sidebar-user p { margin: 0; font-weight: 600; color: #374151; font-size: 0.9rem; }
    .badge-admin { 
        background: #fee2e2; color: #b91c1c; 
        padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;
        display: inline-block; margin-top: 5px;
    }

    .sidebar-nav {
        list-style: none;
        padding: 10px 0;
        margin: 0;
    }

    .nav-label {
        padding: 15px 20px 5px;
        font-size: 11px;
        text-transform: uppercase;
        color: #9ca3af;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .sidebar-nav li a {
        display: block;
        padding: 10px 20px;
        color: #4b5563;
        text-decoration: none;
        font-weight: 500;
        border-left: 4px solid transparent;
        transition: 0.2s;
        font-size: 14px;
    }

    .sidebar-nav li a:hover, 
    .sidebar-nav li a.active {
        background: #eff6ff;
        color: #2563eb;
        border-left-color: #2563eb;
    }

    .logout-item {
        margin-top: 20px;
        border-top: 1px solid #f3f4f6;
        padding-top: 10px;
    }

    /* --- RESPONSIVE HANDLER --- */
    @media (max-width: 768px) {
        .admin-sidebar { 
            transform: translateX(-100%); /* Hide on mobile by default */
        }
        .admin-sidebar.active {
            transform: translateX(0); /* Show when toggled */
        }
    }

    @media (min-width: 769px) {
        /* Shift body content when admin sidebar is present */
        body { margin-left: 10px; }
    }
</style>

<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <img src="uploads/Logo.webp" alt="Logo" width="120">
    </div>
    
    <div class="sidebar-user">
        <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></p>
        <span class="badge-admin">Administrator</span>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-label">Main</li>
        <li><a href="admin_dashboard.php" class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">📊 Dashboard</a></li>
        
        <li class="nav-label">Management</li>
        <li><a href="admin_orders.php" class="<?= ($current_page == 'admin_orders.php') ? 'active' : ''; ?>">🛒 Manage Orders</a></li>
        <li><a href="admin_order_payment.php" class="<?= ($current_page == 'admin_order_payment.php') ? 'active' : ''; ?>">💰 Manage Payment</a></li>
        <li><a href="manage_users.php" class="<?= ($current_page == 'manage_users.php') ? 'active' : ''; ?>">👥 Manage Users</a></li>
        <li><a href="admin_blog.php" class="<?= ($current_page == 'admin_blog.php') ? 'active' : ''; ?>">📝 Manage Blog</a></li>
        
        <li class="nav-label">Service</li>
        <li><a href="manage_services.php" class="<?= ($current_page == 'manage_services.php') ? 'active' : ''; ?>">🛒 Manage Services</a></li>
        <li><a href="manage_categories.php" class="<?= ($current_page == 'manage_categories.php') ? 'active' : ''; ?>">📂 Manage Categories</a></li>
        <li><a href="admin_set_download_link.php" class="<?= ($current_page == 'admin_set_download_link.php') ? 'active' : ''; ?>">🔗 Download Link</a></li>
        
        <li class="nav-label">Requests & Docs</li>
        <li><a href="CustomRequestadmin.php" class="<?= ($current_page == 'CustomRequestadmin.php') ? 'active' : ''; ?>">📩 Custom Requests</a></li>
        <li><a href="Manage_Documents.php" class="<?= ($current_page == 'Manage_Documents.php') ? 'active' : ''; ?>">📄 Documents</a></li>
        <li><a href="manage_submissions.php" class="<?= ($current_page == 'manage_submissions.php') ? 'active' : ''; ?>">📤 Submissions</a></li>
        <li><a href="admin_role_requests.php" class="<?= ($current_page == 'admin_role_requests.php') ? 'active' : ''; ?>">🔑 Role Requests</a></li>

        <li class="nav-label">Settings</li>
        <li><a href="admin_seo.php" class="<?= ($current_page == 'admin_seo.php') ? 'active' : ''; ?>">🚀 SEO Manager</a></li>
        <li><a href="admin_payment_settings.php" class="<?= ($current_page == 'admin_payment_settings.php') ? 'active' : ''; ?>">💳 Payment Gateway</a></li>
        <li><a href="admin_tool_updates.php" class="<?= ($current_page == 'admin_tool_updates.php') ? 'active' : ''; ?>">🔄 System Updates</a></li>
        
        <li class="logout-item">
            <a href="logout.php" style="color: #ef4444;">🚪 Logout</a>
        </li>
    </ul>
</div>