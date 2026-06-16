<?php include 'includes/user_layout_header.php'; ?>

<?php
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
}

// Fetch User Stats
$user_id = $_SESSION['id'];

$lost_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND type = 'lost'");
$lost_stmt->execute([$user_id]);
$total_lost = $lost_stmt->fetchColumn();

$found_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND type = 'found'");
$found_stmt->execute([$user_id]);
$total_found = $found_stmt->fetchColumn();

$matched_stmt = $pdo->prepare("SELECT COUNT(DISTINCT item_id) FROM claims WHERE claimer_id = ? AND status = 'accepted'");
$matched_stmt->execute([$user_id]);
$matched_items = $matched_stmt->fetchColumn();

$active_users  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Recent Activity
$recent_stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent_stmt->execute([$user_id]);
$recent_items = $recent_stmt->fetchAll();

// Recent Notifications
$recent_notifs_stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
$recent_notifs_stmt->execute(['user_id' => $user_id]);
$recent_notifs = $recent_notifs_stmt->fetchAll();
?>

<div class="dash-page-header">
    <h1><?php echo __('welcome'); ?>, <span style="color: var(--dash-accent);"><?php echo htmlspecialchars($_SESSION['username']); ?></span> 👋</h1>
    <p><?php echo __('dash_subtitle'); ?></p>
</div>

<!-- Stats Grid -->
<div class="dash-stats-grid">
    <div class="dash-stat-box">
        <div class="dash-stat-top">
            <h4><?php echo __('total_lost'); ?></h4>
            <div class="dash-stat-icon" style="background: #fee2e2; color: #ef4444;"><i class="fas fa-box"></i></div>
        </div>
        <div class="dash-stat-value">
            <h2><?php echo $total_lost; ?></h2>
            <p><?php echo __('total_lost_desc'); ?></p>
        </div>
    </div>
    <div class="dash-stat-box">
        <div class="dash-stat-top">
            <h4><?php echo __('total_found'); ?></h4>
            <div class="dash-stat-icon" style="background: #dcfce7; color: #10b981;"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="dash-stat-value">
            <h2><?php echo $total_found; ?></h2>
            <p><?php echo __('total_found_desc'); ?></p>
        </div>
    </div>
    <div class="dash-stat-box">
        <div class="dash-stat-top">
            <h4><?php echo __('total_matched'); ?></h4>
            <div class="dash-stat-icon" style="background: #dbeafe; color: #3b82f6;"><i class="fas fa-link"></i></div>
        </div>
        <div class="dash-stat-value">
            <h2><?php echo $matched_items; ?></h2>
            <p><?php echo __('total_matched_desc'); ?></p>
        </div>
    </div>
    <div class="dash-stat-box">
        <div class="dash-stat-top">
            <h4><?php echo __('community'); ?></h4>
            <div class="dash-stat-icon" style="background: #f3e8ff; color: #a855f7;"><i class="fas fa-users"></i></div>
        </div>
        <div class="dash-stat-value">
            <h2><?php echo $active_users; ?></h2>
            <p><?php echo __('community_desc'); ?></p>
        </div>
    </div>
</div>

<!-- Quick Actions & Notifications -->
<div class="resp-grid-2col" style="margin-bottom: 40px;">
    <div class="dash-action-card">
        <h3 style="font-weight: 700; margin-bottom: 25px;"><?php echo __('quick_actions'); ?></h3>
        <a href="post-item.php?type=lost" class="action-btn-pill" style="background: #fee2e2; color: #ef4444;">
            <i class="far fa-times-circle" style="font-size: 1.3rem;"></i>
            <div>
                <p style="font-weight: 700; margin: 0;"><?php echo __('btn_report_lost'); ?></p>
                <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.7; margin: 0;"><?php echo __('quick_lost_desc'); ?></p>
            </div>
        </a>
        <a href="post-item.php?type=found" class="action-btn-pill" style="background: #dcfce7; color: #10b981;">
            <i class="far fa-check-circle" style="font-size: 1.3rem;"></i>
            <div>
                <p style="font-weight: 700; margin: 0;"><?php echo __('btn_report_found'); ?></p>
                <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.7; margin: 0;"><?php echo __('quick_found_desc'); ?></p>
            </div>
        </a>
        <a href="browse.php" class="action-btn-pill" style="background: #f1f5f9; color: #1e293b;">
            <i class="fas fa-search" style="font-size: 1.3rem;"></i>
            <div>
                <p style="font-weight: 700; margin: 0;"><?php echo __('nav_browse'); ?></p>
                <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.7; margin: 0;"><?php echo __('quick_browse_desc'); ?></p>
            </div>
        </a>
        <a href="my-reports.php" class="action-btn-pill" style="background: #ede9fe; color: #7c3aed;">
            <i class="far fa-file-alt" style="font-size: 1.3rem;"></i>
            <div>
                <p style="font-weight: 700; margin: 0;"><?php echo __('nav_my_reports'); ?></p>
                <p style="font-size: 0.8rem; font-weight: 400; opacity: 0.7; margin: 0;"><?php echo __('quick_my_reports_desc'); ?></p>
            </div>
        </a>
    </div>

    <div class="dash-action-card">
        <h3 style="font-weight: 700; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
            <?php echo __('recent_notifications'); ?>
            <a href="notifications.php" style="font-size: 0.8rem; font-weight: 600; color: var(--dash-accent); text-decoration: none;"><?php echo __('view_all'); ?></a>
        </h3>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach($recent_notifs as $notif): ?>
                <div style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 12px;">
                    <div style="color: var(--dash-accent); margin-top: 2px;"><i class="far fa-bell"></i></div>
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 0.85rem; font-weight: 500; color: var(--dash-text-main);"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <p style="margin: 3px 0 0; font-size: 0.75rem; color: #94a3b8;"><?php echo date("M j, g:i a", strtotime($notif['created_at'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($recent_notifs)): ?>
                <div class="dash-empty-state" style="padding: 40px;">
                    <i class="far fa-bell" style="font-size: 3rem;"></i>
                    <h3 style="font-size: 1rem; margin-top: 10px;"><?php echo __('all_caught_up'); ?></h3>
                    <p style="font-size: 0.85rem;"><?php echo __('no_new_notif'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<h3 style="font-weight: 700; margin-bottom: 25px; font-size: 1.3rem;"><?php echo __('recent_activity'); ?></h3>
<div class="dash-items-list">
    <?php foreach($recent_items as $item): 
        $typeBg = $item['type'] == 'lost' ? '#fee2e2' : '#dcfce7';
        $typeColor = $item['type'] == 'lost' ? '#ef4444' : '#10b981';
    ?>
    <div class="dash-item-card" style="padding: 20px; gap: 20px;">
        <img src="<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/placeholder.png'; ?>"
             style="width: 90px; height: 90px; border-radius: 18px; object-fit: cover; flex-shrink: 0;"
             onerror="this.src='assets/images/placeholder.png'">
        <div class="dash-item-info" style="flex: 1;">
            <div style="display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                <span class="dash-role-tag" style="background: <?php echo $typeBg; ?>; color: <?php echo $typeColor; ?>;">
                    <?php echo strtoupper(__('type_' . $item['type'])); ?>
                </span>
                <span class="dash-role-tag" style="background: <?php echo $item['status'] == 'resolved' ? '#dcfce7' : '#fef9c3'; ?>; color: <?php echo $item['status'] == 'resolved' ? '#10b981' : '#a16207'; ?>;">
                    <?php echo strtoupper(__($item['status'])); ?>
                </span>
            </div>
            <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 5px;"><?php echo htmlspecialchars($item['title']); ?></h4>
            <p style="font-size: 0.85rem; color: var(--dash-text-muted); margin-bottom: 10px;"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
            <div style="font-size: 0.8rem; color: #94a3b8; display: flex; gap: 15px; flex-wrap: wrap;">
                <span><i class="far fa-clock"></i> <?php echo date("M j, Y", strtotime($item['created_at'])); ?></span>
                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
            </div>
        </div>
        <a href="item-details.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="padding: 10px 15px; white-space: nowrap;">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    <?php endforeach; ?>

    <?php if(empty($recent_items)): ?>
        <div class="dash-empty-state">
            <i class="fas fa-box-open"></i>
            <h3><?php echo __('no_recent_act'); ?></h3>
            <p><?php echo __('start_by_reporting'); ?></p>
            <a href="post-item.php?type=lost" class="btn btn-primary" style="margin-top: 20px;"><?php echo __('report_item'); ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/user_layout_footer.php'; ?>
