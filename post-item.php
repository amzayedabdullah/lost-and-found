<?php
require_once 'includes/config.php';
require_once 'includes/matching_algorithm.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$title = $description = $category = $location = $date_spotted = $type = $keywords = "";
$title_err = $description_err = $image_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // Validate description
    if (empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }

    $type = $_POST["type"];
    $category = $_POST["category"];
    $location = $_POST["location"];
    $date_spotted = $_POST["date_spotted"];
    $keywords = strtolower(trim($_POST["keywords"]));

    // Handle Image Upload
    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir))
            mkdir($target_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check file size and type
        if ($_FILES["image"]["size"] > 5000000) { // 5MB
            $image_err = "Sorry, your file is too large.";
        } elseif ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $image_err = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $image_err = "Sorry, there was an error uploading your file.";
            }
        }
    }
        if(empty($title_err) && empty($description_err) && empty($image_err)){
            $image_hash = null;
            if (!empty($image_path)) {
                $image_hash = generateImageHash($image_path);
            }

            $sql = "INSERT INTO items (user_id, type, title, description, category, location, date_spotted, image_path, image_hash, keywords) VALUES (:user_id, :type, :title, :description, :category, :location, :date_spotted, :image_path, :image_hash, :keywords)";
            
            if($stmt = $pdo->prepare($sql)){
                $stmt->bindParam(":user_id", $_SESSION["id"]);
                $stmt->bindParam(":type", $type);
                $stmt->bindParam(":title", $title);
                $stmt->bindParam(":description", $description);
                $stmt->bindParam(":category", $category);
                $stmt->bindParam(":location", $location);
                $stmt->bindParam(":date_spotted", $date_spotted);
                $stmt->bindParam(":image_path", $image_path);
                $stmt->bindParam(":image_hash", $image_hash);
                $stmt->bindParam(":keywords", $keywords);
                
                if($stmt->execute()){
                    $last_id = $pdo->lastInsertId();
                    
                    // Trigger Smart Matching in the background to populate system_matches
                    getPotentialMatches($pdo, $last_id, $keywords, $type);
                    
                    header("location: item-details.php?id=" . $last_id . "&posted=success");
                    exit;
                } else {
                    echo "Something went wrong. Please try again later.";
                }
                unset($stmt);
            }
        }
    }
?>

<?php include 'includes/user_layout_header.php'; ?>

<div class="page-header">
    <h1><?php echo __('report_item_title'); ?></h1>
    <p><?php echo __('report_item_subtitle'); ?></p>
</div>

<div class="quick-actions-box animate-fade">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div>
                <div class="form-group">
                    <label><?php echo __('item_type'); ?></label>
                    <?php $selected_type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : 'lost'); ?>
                    <select name="type" class="form-control" style="padding-left: 20px;">
                        <option value="lost" <?php echo ($selected_type == 'lost') ? 'selected' : ''; ?> <?php echo ($selected_type == 'found') ? 'disabled style="display:none;"' : ''; ?>><?php echo __('i_lost_something'); ?></option>
                        <option value="found" <?php echo ($selected_type == 'found') ? 'selected' : ''; ?> <?php echo ($selected_type == 'lost') ? 'disabled style="display:none;"' : ''; ?>><?php echo __('i_found_something'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo __('item_title'); ?></label>
                    <input type="text" name="title" class="form-control"
                        placeholder="<?php echo __('item_title_placeholder'); ?>" style="padding-left: 20px;" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('category'); ?></label>
                    <select name="category" class="form-control" style="padding-left: 20px;">
                        <option value="Electronics"><?php echo __('cat_electronics'); ?></option>
                        <option value="Personal Effects"><?php echo __('cat_personal_effects'); ?></option>
                        <option value="Documents"><?php echo __('cat_documents'); ?></option>
                        <option value="Stationery"><?php echo __('cat_stationery'); ?></option>
                        <option value="Other"><?php echo __('cat_other'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo __('location'); ?></label>
                    <input type="text" name="location" class="form-control"
                        placeholder="<?php echo __('location_placeholder'); ?>" style="padding-left: 20px;" required>
                </div>
            </div>
            <div>
                <div class="form-group">
                    <label><?php echo __('date'); ?></label>
                    <input type="date" name="date_spotted" class="form-control" style="padding-left: 20px;" required>
                </div>
                <div class="form-group">
                    <label><?php echo __('image_optional'); ?></label>
                    <input type="file" name="image" class="form-control" style="padding-left: 20px;">
                    <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $image_err; ?></span>
                </div>
                <div class="form-group">
                    <label><?php echo __('keywords_label'); ?></label>
                    <input type="text" name="keywords" class="form-control"
                        placeholder="<?php echo __('keywords_placeholder'); ?>" style="padding-left: 20px;">
                    <small style="color: var(--gray);"><?php echo __('keywords_help'); ?></small>
                </div>
            </div>
        </div>
        <div class="form-group" style="margin-top: 20px;">
            <label><?php echo __('description'); ?></label>
            <textarea name="description" class="form-control" rows="4"
                placeholder="<?php echo __('description_placeholder'); ?>" style="padding-left: 20px;"
                required></textarea>
        </div>
        <div class="form-group" style="margin-top: 30px;">
            <input type="submit" class="btn btn-primary" style="width: 100%; border-radius: 12px; background: #1e293b;"
                value="<?php echo __('btn_post_report'); ?>">
        </div>
    </form>
</div>

<?php include 'includes/user_layout_footer.php'; ?>