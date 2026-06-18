<?php include 'includes/user_layout_header.php'; ?>

<?php
$user_id = $_SESSION['id'];
$type = $_GET['type'] ?? 'lost';
if ($type !== 'lost' && $type !== 'found') {
    $type = 'lost';
}

$stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = :user_id AND type = :type ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user_id, 'type' => $type]);
$items = $stmt->fetchAll();

$lost_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = :user_id AND type = 'lost'");
$lost_stmt->execute(['user_id' => $user_id]);
$lost_count = $lost_stmt->fetchColumn();

$found_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = :user_id AND type = 'found'");
$found_stmt->execute(['user_id' => $user_id]);
$found_count = $found_stmt->fetchColumn();
?>

<div class="page-header">
    <h1><?php echo __('my_reports_title'); ?></h1>
    <p><?php echo __('my_reports_subtitle'); ?></p>
</div>

<div class="dash-tabs">
    <a href="my-reports.php?type=lost" class="dash-tab-item <?php echo $type == 'lost' ? 'active' : ''; ?>">
        <i class="fas fa-box"></i> <?php echo sprintf(__('lost_items_count'), $lost_count); ?>
    </a>
    <a href="my-reports.php?type=found" class="dash-tab-item <?php echo $type == 'found' ? 'active' : ''; ?>">
        <i class="fas fa-search"></i> <?php echo sprintf(__('found_items_count'), $found_count); ?>
    </a>
</div>

<div class="dash-items-list animate-fade">
    <?php foreach($items as $item): ?>
    <div class="dash-item-card">
        <img src="<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/placeholder.png'; ?>" class="dash-item-img" onerror="this.src='assets/images/placeholder.png'">
        <div class="dash-item-info">
            <div class="dash-item-header">
                <h3 style="margin: 0; font-weight: 700;"><?php echo htmlspecialchars($item['title']); ?></h3>
                <span class="dash-role-tag" style="background: <?php echo $item['status'] == 'resolved' ? '#dcfce7' : ($item['status'] == 'pending' ? '#fef9c3' : '#dbeafe'); ?>; color: <?php echo $item['status'] == 'resolved' ? '#10b981' : ($item['status'] == 'pending' ? '#a16207' : '#3b82f6'); ?>; padding: 4px 10px; font-size: 0.75rem; font-weight: 700; border-radius: 8px;">
                    <?php echo strtoupper(__($item['status'])); ?>
                </span>
            </div>
            <p class="dash-item-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 150)); ?>...</p>
            <div class="dash-item-meta">
                <span><?php echo __('category'); ?>: <strong><?php echo htmlspecialchars($item['category']); ?></strong></span>
                <span><?php echo __('location'); ?>: <strong><?php echo htmlspecialchars($item['location']); ?></strong></span>
                <span><?php echo __('date'); ?>: <strong><?php echo date("n/j/Y", strtotime($item['created_at'])); ?></strong></span>
            </div>
            
            <div class="dash-item-actions" style="margin-top: 20px;">
                <a href="item-details.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 12px; text-decoration: none; border: 1px solid #cbd5e1; color: var(--dash-text-main); background: white;">
                    <i class="far fa-eye" style="margin-right: 5px;"></i> <?php echo __('view_details'); ?>
                </a>
                <a href="edit-item.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 12px; text-decoration: none; border: 1px solid #cbd5e1; color: var(--dash-text-main); background: white;">
                    <i class="far fa-edit" style="margin-right: 5px;"></i> <?php echo __('edit'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($items)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3><?php echo sprintf(__('no_reports_reported'), __('type_' . $type)); ?></h3>
            <p><?php echo __('start_by_reporting_type'); ?></p>
            <a href="post-item.php?type=<?php echo $type; ?>" class="btn btn-primary mt-50" style="background: #1e293b; border-radius: 12px; margin-top: 20px;"><?php echo sprintf(__('report_type_item'), __('type_' . $type)); ?></a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/user_layout_footer.php'; ?>
