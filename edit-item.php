<?php
require_once 'includes/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['id']]);
$item = $stmt->fetch();

if(!$item){
    header("location: my-reports.php");
    exit;
}

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category    = $_POST['category'];
    $location    = trim($_POST['location']);
    $date_spotted = $_POST['date_spotted'];
    $keywords    = strtolower(trim($_POST['keywords']));

    if(empty($title) || empty($description)){
        $error = "Title and description are required.";
    } else {
        $image_path = $item['image_path']; // keep existing

        // Handle new image upload
        if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
            $target_dir = "uploads/";
            if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $file_name = time() . "_" . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $file_name;
            $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if($_FILES["image"]["size"] > 5000000){
                $error = "File is too large (max 5MB).";
            } elseif(!in_array($ext, ['jpg','jpeg','png','gif'])){
                $error = "Only JPG, PNG & GIF allowed.";
            } else {
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)){
                    // Delete old image
                    if(!empty($item['image_path']) && file_exists($item['image_path'])){
                        unlink($item['image_path']);
                    }
                    $image_path = $target_file;
                }
            }
        }

        if(empty($error)){
            $sql = "UPDATE items SET title=?, description=?, category=?, location=?, date_spotted=?, keywords=?, image_path=? WHERE id=? AND user_id=?";
            $stmt = $pdo->prepare($sql);
            if($stmt->execute([$title, $description, $category, $location, $date_spotted, $keywords, $image_path, $id, $_SESSION['id']])){
                $success = "Item updated successfully!";
                // Refresh
                $stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();
            } else {
                $error = "Failed to update. Please try again.";
            }
        }
    }
}
?>

<?php include 'includes/user_layout_header.php'; ?>

<a href="my-reports.php" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--dash-text-main); font-weight: 600; margin-bottom: 30px; font-size: 0.95rem;">
    <i class="fas fa-arrow-left"></i> <?php echo __('back_to_reports'); ?>
</a>

<div class="dash-page-header">
    <h1><?php echo __('edit_item'); ?></h1>
    <p><?php echo __('edit_item_desc'); ?></p>
</div>

<?php if($success): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>
<?php if($error): ?>
    <div style="background: #fee2e2; color: #ef4444; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dash-stat-box">
    <form action="edit-item.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
        <div class="resp-grid-2col">
            <!-- Left Column -->
            <div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('item_type'); ?></label>
                    <div style="background: #f8fafc; border-radius: 14px; padding: 14px 18px; margin-top: 8px; font-weight: 600; color: var(--dash-text-main);">
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <?php echo ($item['type'] == 'lost') ? '🔴 ' . __('type_lost') : '🟢 ' . __('type_found'); ?>
                        </span>
                        <small style="display: block; color: #94a3b8; margin-top: 4px;"><?php echo __('type_fixed_help'); ?></small>
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('item_title'); ?></label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($item['title']); ?>" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;" required>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('category'); ?></label>
                    <select name="category" class="form-control" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
                        <?php $cats = ['Electronics' => 'cat_electronics', 'Personal Effects' => 'cat_personal_effects', 'Documents' => 'cat_documents', 'Stationery' => 'cat_stationery', 'Other' => 'cat_other']; foreach($cats as $db_val => $lang_key): ?>
                            <option value="<?php echo $db_val; ?>" <?php echo ($item['category'] == $db_val) ? 'selected' : ''; ?>><?php echo __($lang_key); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('location'); ?></label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($item['location']); ?>" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;" required>
                </div>
            </div>
            <!-- Right Column -->
            <div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('date_spotted'); ?></label>
                    <input type="date" name="date_spotted" class="form-control" value="<?php echo $item['date_spotted']; ?>" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;" required>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('current_image'); ?></label>
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="<?php echo $item['image_path']; ?>" style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 14px; margin-top: 8px;" onerror="this.style.display='none'">
                    <?php else: ?>
                        <p style="margin-top: 8px; color: #94a3b8;"><?php echo __('no_image_uploaded'); ?></p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('replace_image'); ?></label>
                    <input type="file" name="image" class="form-control" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('keywords_label'); ?></label>
                    <input type="text" name="keywords" class="form-control" value="<?php echo htmlspecialchars($item['keywords']); ?>" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
                    <small style="color: #94a3b8; display: block; margin-top: 8px;"><i class="fas fa-info-circle"></i> <?php echo __('keywords_help_edit'); ?></small>
                </div>
            </div>
        </div>
        <div class="form-group" style="margin-top: 25px;">
            <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('description'); ?></label>
            <textarea name="description" class="form-control" rows="4" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px; resize: vertical;" required><?php echo htmlspecialchars($item['description']); ?></textarea>
        </div>
        <div style="display: flex; gap: 15px; margin-top: 35px; flex-wrap: wrap;">
            <button type="submit" class="btn btn-primary" style="padding: 16px 40px; border-radius: 16px; font-size: 1.05rem;">
                <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
            </button>
            <a href="item-details.php?id=<?php echo $id; ?>" class="btn btn-outline" style="padding: 16px 40px; border-radius: 16px; text-decoration: none;"><?php echo __('cancel'); ?></a>
        </div>
    </form>
</div>

<?php include 'includes/user_layout_footer.php'; ?>
