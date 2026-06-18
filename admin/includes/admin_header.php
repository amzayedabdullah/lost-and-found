<?php
require_once __DIR__ . '/../../includes/config.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_panel_title'); ?> - Lost & Found</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 70px;
        }

        body {
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
        }

        /* Top Navigation */
        .admin-top-nav {
            height: var(--topbar-height);
            background: white;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 40px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1001;
        }

        .top-nav-left .logo img {
            height: 35px;
        }

        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .lang-switch {
            text-decoration: none;
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .notif-btn {
            color: #64748b;
            font-size: 1.1rem;
            position: relative;
            cursor: pointer;
        }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 20px;
            border-left: 1px solid #e2e8f0;
        }

        .user-profile-badge .info {
            text-align: right;
        }

        .user-profile-badge h4 {
            font-size: 0.85rem;
            margin: 0;
            color: #1e293b;
        }

        .user-profile-badge p {
            font-size: 0.7rem;
            margin: 0;
            color: #64748b;
        }

        .user-avatar-initial {
            width: 35px;
            height: 35px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Sidebar */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid #e2e8f0;
            height: calc(100vh - var(--topbar-height));
            position: fixed;
            top: var(--topbar-height);
            left: 0;
            padding: 30px 15px;
            z-index: 1000;
        }

        .sidebar-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 15px;
            padding-left: 15px;
        }

        .admin-nav {
            list-style: none;
        }

        .admin-nav li {
            margin-bottom: 8px;
        }

        .admin-nav a {
            text-decoration: none;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .admin-nav a:hover {
            color: var(--primary);
            background: #f8fafc;
        }

        .admin-nav a.active {
            background: linear-gradient(135deg, var(--secondary), #ff6b6b);
            color: white;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.2);
        }

        /* Main Content */
        .admin-wrapper {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 40px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Cards & Stats */
        .admin-stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-box-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-box-top h4 {
            font-size: 0.9rem;
            color: #64748b;
        }

        .stat-box-top i {
            color: #94a3b8;
            font-size: 1rem;
        }

        .stat-box-value h2 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-box-value p {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Search & Filters */
        .admin-actions-bar {
            background: white;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            outline: none;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 12px 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            color: #1e293b;
            font-weight: 500;
            outline: none;
            min-width: 150px;
        }

        /* Item Cards */
        .admin-items-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .admin-item-card {
            background: white;
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .admin-item-img {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            object-fit: cover;
        }

        .admin-item-info {
            flex: 1;
        }

        .admin-item-info h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .admin-item-info p {
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .admin-item-meta {
            display: flex;
            gap: 30px;
            font-size: 0.8rem;
            color: #64748b;
        }

        .admin-item-meta span strong {
            color: #1e293b;
        }

        .admin-item-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 0.8rem;
            border-radius: 10px;
        }

        /* User Cards */
        .admin-users-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .admin-user-card {
            background: white;
            padding: 20px 30px;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .user-card-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-card-avatar {
            width: 45px;
            height: 45px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        .user-card-details h4 {
            font-size: 1rem;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-tag {
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #64748b;
        }

        .role-admin { background: #fee2e2; color: #ef4444; }

        .user-card-details p {
            font-size: 0.85rem;
            color: #64748b;
        }

        .user-card-date {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Main Content */
        .admin-wrapper {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 40px;
            transition: var(--transition);
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Mobile Adjustments */
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #1e293b;
        }

        @media (max-width: 1024px) {
            .admin-stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 260px;
            }

            .menu-toggle {
                display: block;
            }

            .admin-sidebar {
                left: calc(-1 * var(--sidebar-width));
                transition: var(--transition);
            }

            .admin-sidebar.active {
                left: 0;
                box-shadow: 20px 0 50px rgba(0,0,0,0.1);
            }

            .admin-wrapper {
                margin-left: 0;
                padding: 20px;
            }

            .admin-top-nav {
                padding: 0 20px;
            }

            .top-nav-left {
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .admin-stats-grid {
                grid-template-columns: 1fr;
            }

            .admin-actions-bar {
                flex-direction: column;
            }

            .admin-item-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .admin-item-img {
                width: 100%;
                height: 200px;
            }

            .admin-item-meta {
                flex-direction: column;
                gap: 5px;
            }

            .user-profile-badge .info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="admin-top-nav">
        <div class="top-nav-left">
            <div class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <a href="dashboard.php" class="logo">
                <img src="../assets/images/logo.png" alt="Lost & Found">
            </a>
        </div>
        <div class="top-nav-right">
            <!-- Language Switcher -->
            <?php if (isset($_SESSION['lang']) && $_SESSION['lang'] === 'bn'): ?>
                <a href="../change_lang.php?lang=en" class="lang-switch"><i class="fas fa-language"></i> English</a>
            <?php else: ?>
                <a href="../change_lang.php?lang=bn" class="lang-switch"><i class="fas fa-language"></i> বাংলা</a>
            <?php endif; ?>

            <div class="notif-btn">
                <i class="far fa-bell"></i>
            </div>
            <div class="user-profile-badge">
                <div class="info">
                    <h4><?php echo htmlspecialchars($_SESSION["username"]); ?></h4>
                    <p><?php echo ucfirst($_SESSION["role"]); ?></p>
                </div>
                <div class="user-avatar-initial">
                    <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                </div>
            </div>
        </div>
    </nav>
 
    <div class="admin-sidebar" id="sidebar">
        <p class="sidebar-label"><?php echo __('nav_admin'); ?></p>
        <ul class="admin-nav">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> <?php echo __('nav_admin'); ?>
                </a>
            </li>
            <li>
                <a href="items.php" class="<?php echo ($current_page == 'items.php') ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> <?php echo __('manage_items'); ?>
                </a>
            </li>
            <li>
                <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> <?php echo __('manage_users'); ?>
                </a>
            </li>
            <li>
                <a href="claims.php" class="<?php echo ($current_page == 'claims.php') ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i> <?php echo __('manage_claims'); ?>
                </a>
            </li>
            <li>
                <a href="verify-matches.php" class="<?php echo ($current_page == 'verify-matches.php') ? 'active' : ''; ?>">
                    <i class="fas fa-magic"></i> <?php echo __('verify_matches'); ?>
                </a>
            </li>
            <li style="margin-top: 20px;">
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> <?php echo __('nav_logout'); ?>
                </a>
            </li>
        </ul>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>

    <div class="admin-wrapper">
