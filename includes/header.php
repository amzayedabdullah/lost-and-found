<?php 
require_once 'config.php'; 

// Restrict admins to the admin panel
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["role"] === 'admin'){
    // Ensure we don't cause a redirect loop if header.php is somehow included in admin
    // but admin files use admin_header.php, so this is safe.
    header("location: admin/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found | Find What You Lost</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">
                    <img src="assets/images/logo.png" alt="Logo" style="height: 40px; margin-right: 10px;">
                </a>
                <ul class="nav-links">
                    <li><a href="index.php"><?php echo __('nav_home'); ?></a></li>
                    <li><a href="browse.php"><?php echo __('nav_browse'); ?></a></li>
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li><a href="post-item.php"><?php echo __('nav_post'); ?></a></li>
                        <?php if($_SESSION["role"] === 'admin'): ?>
                            <li><a href="admin/dashboard.php" style="color: var(--secondary); font-weight: 700;"><i class="fas fa-user-shield"></i> <?php echo __('nav_admin'); ?></a></li>
                        <?php else: ?>
                            <li><a href="dashboard.php"><?php echo __('nav_dashboard'); ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Language Switcher -->
                    <?php if (isset($_SESSION['lang']) && $_SESSION['lang'] === 'bn'): ?>
                        <li><a href="change_lang.php?lang=en" style="display: flex; align-items: center; gap: 5px;"><i class="fas fa-language"></i> English</a></li>
                    <?php else: ?>
                        <li><a href="change_lang.php?lang=bn" style="display: flex; align-items: center; gap: 5px;"><i class="fas fa-language"></i> বাংলা</a></li>
                    <?php endif; ?>

                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        <li><a href="logout.php" class="btn btn-outline" style="padding: 8px 20px;"><?php echo __('nav_logout'); ?></a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn btn-primary" style="padding: 10px 25px;"><?php echo __('login'); ?></a></li>
                    <?php endif; ?>
                </ul>
                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </div>
            </nav>
        </div>
    </header>
    <main>
    <script>
        function toggleMobileMenu() {
            var nav = document.querySelector('.nav-links');
            nav.classList.toggle('active');
        }
    </script>
