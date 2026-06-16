<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

// Fetch unread notifications count
$user_id = $_SESSION['id'];
$unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = FALSE");
$unread_stmt->execute(['user_id' => $user_id]);
$unread_count = $unread_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('dashboard_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dash-accent: #10b981; /* Green for User */
            --dash-accent-glow: rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="dashboard-body">
    <nav class="dash-top-nav">
        <div class="dash-top-left">
            <div class="dash-menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <a href="index.php" class="logo">
                <img src="assets/images/logo.png" alt="Lost & Found">
            </a>
        </div>
        <div class="dash-top-right">
            <!-- Language Switcher -->
            <?php if (isset($_SESSION['lang']) && $_SESSION['lang'] === 'bn'): ?>
                <a href="change_lang.php?lang=en" class="dash-lang-switch"><i class="fas fa-language"></i> English</a>
            <?php else: ?>
                <a href="change_lang.php?lang=bn" class="dash-lang-switch"><i class="fas fa-language"></i> বাংলা</a>
            <?php endif; ?>

            <a href="notifications.php" class="dash-notif-btn" style="position: relative; text-decoration: none; color: inherit;">
                <i class="far fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                        <?php echo $unread_count; ?>
                    </span>
                <?php endif; ?>
            </a>
            <div class="dash-user-badge">
                <div class="dash-user-info">
                    <h4><?php echo htmlspecialchars($_SESSION["username"]); ?></h4>
                    <p><?php echo __('member'); ?></p>
                </div>
                <?php 
                    $header_pic = !empty($_SESSION['profile_pic']) ? 'uploads/profile_pics/'.$_SESSION['profile_pic'] : 'assets/images/default_profile.png';
                    if(!file_exists($header_pic) && !empty($_SESSION['profile_pic'])) $header_pic = 'assets/images/'.$_SESSION['profile_pic'];
                ?>
                <img src="<?php echo $header_pic; ?>" class="dash-avatar" onerror="this.src='assets/images/default_profile.png'">
            </div>
        </div>
    </nav>

    <div class="dash-sidebar" id="sidebar">
        <p class="dash-sidebar-label"><?php echo __('nav_main_menu'); ?></p>
        <ul class="dash-nav">
            <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> <?php echo __('nav_dashboard'); ?></a></li>
            <li><a href="post-item.php?type=lost" class="<?php echo ($current_page == 'post-item.php' && ($_GET['type']??'') == 'lost') ? 'active' : ''; ?>"><i class="far fa-times-circle"></i> <?php echo __('nav_report_lost'); ?></a></li>
            <li><a href="post-item.php?type=found" class="<?php echo ($current_page == 'post-item.php' && ($_GET['type']??'') == 'found') ? 'active' : ''; ?>"><i class="far fa-check-circle"></i> <?php echo __('nav_report_found'); ?></a></li>
            <li><a href="browse.php" class="<?php echo ($current_page == 'browse.php') ? 'active' : ''; ?>"><i class="fas fa-search"></i> <?php echo __('nav_browse'); ?></a></li>
            <li><a href="my-reports.php" class="<?php echo ($current_page == 'my-reports.php') ? 'active' : ''; ?>"><i class="far fa-file-alt"></i> <?php echo __('nav_my_reports'); ?></a></li>
            <li><a href="notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>"><i class="far fa-bell"></i> <?php echo __('nav_notifications'); ?></a></li>
            <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="far fa-user"></i> <?php echo __('nav_profile'); ?></a></li>
            <li style="margin-top: 30px; border-top: 1px solid var(--dash-border); padding-top: 20px;">
                <a href="logout.php" style="color: #ef4444;">
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

    <div class="dash-wrapper">
