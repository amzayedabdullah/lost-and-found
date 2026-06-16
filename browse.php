<?php
require_once 'includes/config.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : "";
$category = isset($_GET['category']) ? $_GET['category'] : "";
$type = isset($_GET['type']) ? $_GET['type'] : "";

$query = "SELECT * FROM items WHERE status != 'resolved'";
$params = [];

if(!empty($search)){
    $query .= " AND (title LIKE :search OR description LIKE :search OR keywords LIKE :search)";
    $params['search'] = "%$search%";
}

if(!empty($category)){
    $query .= " AND category = :category";
    $params['category'] = $category;
}

if(!empty($type)){
    $query .= " AND type = :type";
    $params['type'] = $type;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<?php 
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    include 'includes/user_layout_header.php';
} else {
    include 'includes/header.php';
}
?>

<div class="<?php echo isset($_SESSION["loggedin"]) ? '' : 'container mt-50'; ?>">
    <div class="page-header <?php echo isset($_SESSION["loggedin"]) ? '' : 'text-center'; ?>">
        <h1><?php echo __('browse_title'); ?></h1>
        <p><?php echo __('browse_subtitle'); ?></p>
    </div>

    <div style="background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 40px;" class="animate-fade">
        <form action="browse.php" method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end; width: 100%;">
            <div class="form-group" style="margin-bottom: 0;">
                <label><?php echo __('search_keyword'); ?></label>
                <input type="text" name="search" class="form-control" style="padding-left: 20px;" value="<?php echo $search; ?>" placeholder="<?php echo __('search_placeholder'); ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><?php echo __('category'); ?></label>
                <select name="category" class="form-control" style="padding-left: 20px;">
                    <option value=""><?php echo __('all_categories'); ?></option>
                    <option value="Electronics" <?php echo ($category == 'Electronics') ? 'selected' : ''; ?>><?php echo __('cat_electronics'); ?></option>
                    <option value="Personal Effects" <?php echo ($category == 'Personal Effects') ? 'selected' : ''; ?>><?php echo __('cat_personal_effects'); ?></option>
                    <option value="Documents" <?php echo ($category == 'Documents') ? 'selected' : ''; ?>><?php echo __('cat_documents'); ?></option>
                    <option value="Stationery" <?php echo ($category == 'Stationery') ? 'selected' : ''; ?>><?php echo __('cat_stationery'); ?></option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><?php echo __('type'); ?></label>
                <select name="type" class="form-control" style="padding-left: 20px;">
                    <option value=""><?php echo __('all_types'); ?></option>
                    <option value="lost" <?php echo ($type == 'lost') ? 'selected' : ''; ?>><?php echo __('type_lost'); ?></option>
                    <option value="found" <?php echo ($type == 'found') ? 'selected' : ''; ?>><?php echo __('type_found'); ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 12px; background: #1e293b; border-radius: 12px;"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <div class="card-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
        <?php foreach($items as $row): 
            $tagClass = ($row['type'] == 'lost') ? 'tag-lost' : 'tag-found';
        ?>
        <div class="card animate-fade">
            <?php 
            $is_owner = isset($_SESSION['id']) && $_SESSION['id'] == $row['user_id'];
            $has_image = !empty($row['image_path']);
            if ($has_image && !$is_owner): 
            ?>
                <div class="blur-container" style="height: 200px;">
                    <img src="<?php echo $row['image_path']; ?>" alt="Item Image" class="card-img blurry-img" style="height: 100%; width: 100%; object-fit: cover;" onerror="this.src='assets/images/placeholder.png'">
                    <div class="blur-overlay">
                        <div class="blur-overlay-icon"><i class="fas fa-eye-slash"></i></div>
                        <div class="blur-overlay-title"><?php echo __('verification_blur'); ?></div>
                        <div class="blur-overlay-desc"><?php echo __('blur_desc_protected'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <img src="<?php echo $has_image ? $row['image_path'] : 'assets/images/placeholder.png'; ?>" alt="Item Image" class="card-img" style="height: 200px; object-fit: cover;" onerror="this.src='assets/images/placeholder.png'">
            <?php endif; ?>
            <div class="card-content">
                <span class="card-tag <?php echo $tagClass; ?>"><?php echo __('type_' . $row['type']); ?></span>
                <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p style="color: var(--gray); font-size: 0.85rem; margin: 10px 0;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($row['location']); ?>
                </p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <span style="font-size: 0.75rem; color: var(--gray);"><?php echo date("M d, Y", strtotime($row['created_at'])); ?></span>
                    <a href="item-details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary" style="padding: 6px 15px; font-size: 0.75rem; border-radius: 10px; background: #1e293b;"><?php echo __('view_details'); ?></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($items)): ?>
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-search-minus"></i>
                <p><?php echo __('no_items_found'); ?></p>
                <a href="browse.php" style="color: var(--primary);"><?php echo __('clear_filters'); ?></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    include 'includes/user_layout_footer.php';
} else {
    include 'includes/footer.php';
}
?>
