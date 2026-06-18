<?php
require_once '../includes/config.php';
if(!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin'){
    header("location: ../login.php");
    exit;
}

// Handle Actions
if(isset($_POST['action']) && isset($_POST['item_id'])){
    $item_id = $_POST['item_id'];
    
    if($_POST['action'] == 'delete'){
        $stmt = $pdo->prepare("SELECT image_path FROM items WHERE id = ?");
        $stmt->execute([$item_id]);
        $img = $stmt->fetchColumn();
        if($img && file_exists("../".$img)) unlink("../".$img);
        $pdo->prepare("DELETE FROM items WHERE id = ?")->execute([$item_id]);
    } elseif($_POST['action'] == 'update_status'){
        $new_status = $_POST['status'];
        $pdo->prepare("UPDATE items SET status = ? WHERE id = ?")->execute([$new_status, $item_id]);
    }
    header("Location: items.php?success=1");
    exit;
}

include 'includes/admin_header.php';

// Search and Filter Logic
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$query = "SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE (i.title LIKE :search OR i.description LIKE :search)";
$params = ['search' => "%$search%"];

if(in_array($type_filter, ['lost', 'found'])) {
    $query .= " AND i.type = :type";
    $params['type'] = $type_filter;
}
if(in_array($status_filter, ['open', 'pending', 'resolved'])) {
    $query .= " AND i.status = :status";
    $params['status'] = $status_filter;
}
$query .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><?php echo __('manage_items'); ?></h1>
    <p><?php echo __('manage_items_desc'); ?></p>
</div>

<div class="admin-actions-bar">
    <form action="items.php" method="GET" style="display: flex; gap: 20px; width: 100%;">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="type" class="filter-select" onchange="this.form.submit()">
            <option value="all">All Types</option>
            <option value="lost" <?php echo $type_filter == 'lost' ? 'selected' : ''; ?>>Lost Items</option>
            <option value="found" <?php echo $type_filter == 'found' ? 'selected' : ''; ?>>Found Items</option>
        </select>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="all">All Statuses</option>
            <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Open</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
        </select>
    </form>
</div>

<div class="admin-items-grid">
    <p style="font-weight: 600; color: #1e293b; margin-bottom: 10px;"><?php echo __('all_items'); ?> (<?php echo count($items); ?>)</p>

    <?php foreach($items as $item): 
        $typeBg = $item['type'] == 'lost' ? '#fee2e2' : '#dcfce7';
        $typeColor = $item['type'] == 'lost' ? '#ef4444' : '#10b981';
        $statusBg = $item['status'] == 'resolved' ? '#dcfce7' : ($item['status'] == 'pending' ? '#fef9c3' : '#f1f5f9');
        $statusColor = $item['status'] == 'resolved' ? '#10b981' : ($item['status'] == 'pending' ? '#a16207' : '#64748b');
    ?>
    <div class="admin-item-card">
        <img src="../<?php echo !empty($item['image_path']) ? $item['image_path'] : 'assets/images/placeholder.png'; ?>" class="admin-item-img" onerror="this.src='../assets/images/placeholder.png'">
        <div class="admin-item-info">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px;">
                <h3 style="margin: 0;"><?php echo htmlspecialchars($item['title']); ?></h3>
                <div style="display: flex; gap: 8px;">
                    <span class="role-tag" style="background: <?php echo $typeBg; ?>; color: <?php echo $typeColor; ?>; font-size: 0.65rem; padding: 4px 10px;"><?php echo strtoupper($item['type']); ?></span>
                    <span class="role-tag" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-size: 0.65rem; padding: 4px 10px;"><?php echo strtoupper($item['status']); ?></span>
                </div>
            </div>
            <p><?php echo htmlspecialchars(substr($item['description'], 0, 150)) . (strlen($item['description']) > 150 ? '...' : ''); ?></p>
            <div class="admin-item-meta">
                <span>Category: <strong><?php echo htmlspecialchars($item['category']); ?></strong></span>
                <span>Location: <strong><?php echo htmlspecialchars($item['location']); ?></strong></span>
                <span>Reported by: <strong><?php echo htmlspecialchars($item['username']); ?></strong></span>
                <span>Date: <strong><?php echo date("n/j/Y", strtotime($item['created_at'])); ?></strong></span>
            </div>
            
            <div class="admin-item-actions" style="margin-top: 20px;">
                <a href="item-details.php?id=<?php echo $item['id']; ?>" class="btn btn-outline btn-small" style="background: white; border-color: #e2e8f0; color: #1e293b;">
                    <i class="far fa-eye" style="margin-right: 5px;"></i> View
                </a>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <input type="hidden" name="action" value="update_status">
                    <?php if($item['status'] == 'resolved'): ?>
                        <input type="hidden" name="status" value="open">
                        <button type="submit" class="btn btn-outline btn-small" style="background: white; border-color: #e2e8f0; color: #1e293b;">
                            Reopen Item
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="status" value="resolved">
                        <button type="submit" class="btn btn-outline btn-small" style="background: white; border-color: #10b981; color: #10b981;">
                            <i class="far fa-check-circle" style="margin-right: 5px;"></i> Mark as Resolved
                        </button>
                    <?php endif; ?>
                </form>

                <form method="post" onsubmit="return confirm('Are you sure you want to delete this item?');" style="display: inline;">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-outline btn-small" style="background: white; border-color: #fee2e2; color: #ef4444;">
                        <i class="far fa-trash-alt" style="margin-right: 5px;"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($items)): ?>
        <div class="stat-box" style="text-align: center; color: #94a3b8; padding: 50px;">
            No items found matching your search and filters.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
