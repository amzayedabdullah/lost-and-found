<?php 
include 'includes/user_layout_header.php'; 

// Fetch user notifications
$user_id = $_SESSION['id'];

// Mark all unread notifications as read (since user is opening the page)
$update_stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id AND is_read = FALSE");
$update_stmt->execute(['user_id' => $user_id]);

// Query all notifications
$notif_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC");
$notif_stmt->execute(['user_id' => $user_id]);
$notifications = $notif_stmt->fetchAll();
?>

<div class="dash-page-header">
    <h1><?php echo __('notif_title'); ?></h1>
    <p><?php echo __('notif_subtitle'); ?></p>
</div>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <?php foreach ($notifications as $notif): ?>
        <div class="dash-stat-box" style="padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: #f0fdf4; color: var(--dash-accent); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                    <i class="far fa-bell"></i>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.95rem; font-weight: 500; color: var(--dash-text-main);"><?php echo htmlspecialchars($notif['message']); ?></p>
                    <p style="margin: 5px 0 0; font-size: 0.8rem; color: #94a3b8;"><i class="far fa-clock"></i> <?php echo date("F j, Y, g:i a", strtotime($notif['created_at'])); ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($notifications)): ?>
        <div class="dash-stat-box" style="text-align: center; padding: 80px 40px;">
            <div style="width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
                <i class="far fa-bell-slash" style="font-size: 2.5rem; color: #cbd5e1;"></i>
            </div>
            <h3 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 12px; color: var(--dash-text-main);"><?php echo __('notif_empty_title'); ?></h3>
            <p style="color: var(--dash-text-muted); max-width: 450px; margin: 0 auto 30px; line-height: 1.7;">
                <?php echo __('notif_empty_desc'); ?>
            </p>
            <a href="browse.php" class="btn btn-primary" style="padding: 14px 35px;"><?php echo __('nav_browse'); ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/user_layout_footer.php'; ?>
