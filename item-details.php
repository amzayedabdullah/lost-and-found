<?php
require_once 'includes/config.php';
require_once 'includes/matching_algorithm.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$sql = "SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if(!$item){
    header("location: index.php");
    exit;
}

$matches = [];
if($item['type'] == 'lost' && $item['status'] == 'open'){
    $matches = getPotentialMatches($pdo, $item['id'], $item['keywords'], $item['type']);
}

$is_logged_in = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Handle owner claim status update actions (Accept/Reject)
$owner_success = "";
$owner_error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['owner_action']) && isset($_POST['claim_id']) && $is_logged_in) {
    // Verify that the logged-in user is indeed the owner of the item
    if ($_SESSION['id'] == $item['user_id']) {
        $claim_id = (int)$_POST['claim_id'];
        $owner_action = $_POST['owner_action'];

        // Fetch the claim to confirm it exists and belongs to this item
        $c_stmt = $pdo->prepare("
            SELECT c.*, u.username as claimer_name, u.email as claimer_email 
            FROM claims c 
            JOIN users u ON c.claimer_id = u.id 
            WHERE c.id = ? AND c.item_id = ?
        ");
        $c_stmt->execute([$claim_id, $id]);
        $claim_details = $c_stmt->fetch();

        if ($claim_details) {
            $claimer_id = $claim_details['claimer_id'];
            $item_title = $item['title'];
            
            $owner_user_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $owner_user_stmt->execute([$_SESSION['id']]);
            $owner_email = $owner_user_stmt->fetchColumn();

            if ($owner_action === 'accept') {
                $pdo->beginTransaction();
                try {
                    // 1. Accept this claim
                    $pdo->prepare("UPDATE claims SET status = 'accepted' WHERE id = ?")->execute([$claim_id]);

                    // 2. Reject all other pending claims for this item
                    $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE item_id = ? AND id != ? AND status = 'pending'")->execute([$id, $claim_id]);

                    // 3. Mark the item as resolved
                    $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = ?")->execute([$id]);

                    // 4. Create notification for the claimer
                    createNotification($pdo, $claimer_id, "Your claim for '$item_title' has been accepted! Please contact the owner at $owner_email to coordinate recovery.");

                    $pdo->commit();
                    $owner_success = "Claim accepted successfully!";
                    
                    // Refresh item data
                    $stmt = $pdo->prepare("SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id");
                    $stmt->execute(['id' => $id]);
                    $item = $stmt->fetch();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $owner_error = "Error processing claim: " . $e->getMessage();
                }
            } elseif ($owner_action === 'reject') {
                $pdo->beginTransaction();
                try {
                    // 1. Reject this claim
                    $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE id = ?")->execute([$claim_id]);

                    // 2. Check if there are other pending claims for this item
                    $p_stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE item_id = ? AND status = 'pending'");
                    $p_stmt->execute([$id]);
                    $other_pendings = $p_stmt->fetchColumn();

                    // If no other pending claims, mark the item status back to open
                    if ($other_pendings == 0) {
                        $pdo->prepare("UPDATE items SET status = 'open' WHERE id = ?")->execute([$id]);
                    }

                    // 3. Create notification for the claimer
                    createNotification($pdo, $claimer_id, "Your claim for '$item_title' has been rejected.");

                    $pdo->commit();
                    $owner_success = "Claim rejected successfully.";
                    
                    // Refresh item data
                    $stmt = $pdo->prepare("SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id");
                    $stmt->execute(['id' => $id]);
                    $item = $stmt->fetch();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $owner_error = "Error processing claim: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all claims for this item (if owner)
$item_claims = [];
if ($is_logged_in && $_SESSION['id'] == $item['user_id']) {
    $c_stmt = $pdo->prepare("
        SELECT c.*, u.username as claimer_name, u.email as claimer_email 
        FROM claims c 
        JOIN users u ON c.claimer_id = u.id 
        WHERE c.item_id = ? 
        ORDER BY c.created_at DESC
    ");
    $c_stmt->execute([$id]);
    $item_claims = $c_stmt->fetchAll();
}

// Handle inline claim submission (AJAX-style form)
$claim_success = false;
$claim_error = "";
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['claim_message']) && $is_logged_in){
    $message = trim($_POST['claim_message']);
    $claim_keywords = trim($_POST['claim_keywords'] ?? '');
    
    // Handle Proof Image Upload
    $proof_image_path = "";
    if(isset($_FILES["proof_image"]) && $_FILES["proof_image"]["error"] == 0){
        $target_dir = "uploads/claims/";
        if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . "_" . basename($_FILES["proof_image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if($_FILES["proof_image"]["size"] > 5000000){ // 5MB
            $claim_error = "Sorry, your file is too large.";
        } elseif($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $claim_error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            if(move_uploaded_file($_FILES["proof_image"]["tmp_name"], $target_file)){
                $proof_image_path = $target_file;
            } else {
                $claim_error = "Sorry, there was an error uploading your file.";
            }
        }
    }

    if(empty($message)){
        $claim_error = "Please enter a message.";
    } elseif(empty($claim_error)) {
        // Calculate Match Percentage
        $match_percentage = 0;
        
        // 1. Keyword match
        $extract_words = function($text) {
            $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', strtolower($text));
            $words = explode(' ', $clean);
            return array_filter($words, function($w) { return strlen($w) > 2; });
        };
        
        $item_keywords = $extract_words($item['keywords'] . ' ' . $item['title']);
        $claim_kw_arr = $extract_words($claim_keywords);
        $intersect = array_intersect($item_keywords, $claim_kw_arr);
        $kw_matches = count($intersect);
        if ($kw_matches > 0) {
            $match_percentage += min(40, $kw_matches * 15);
        }
        
        // 2. Image similarity match
        if (!empty($proof_image_path) && !empty($item['image_hash'])) {
            $proof_hash = generateImageHash($proof_image_path);
            if ($proof_hash) {
                $distance = calculateHammingDistance($proof_hash, $item['image_hash']);
                $similarity = max(0, 100 - ($distance * 1.5625));
                if ($similarity >= 70) {
                    $match_percentage += 60;
                } elseif ($similarity >= 50) {
                    $match_percentage += 30;
                }
            }
        }
        
        $match_percentage = min(100, $match_percentage);

        $sql = "INSERT INTO claims (item_id, claimer_id, message, proof_image, claim_keywords, match_percentage) VALUES (:item_id, :claimer_id, :message, :proof_image, :claim_keywords, :match_percentage)";
        $cstmt = $pdo->prepare($sql);
        $cstmt->bindParam(":item_id", $id);
        $cstmt->bindParam(":claimer_id", $_SESSION["id"]);
        $cstmt->bindParam(":message", $message);
        $cstmt->bindParam(":proof_image", $proof_image_path);
        $cstmt->bindParam(":claim_keywords", $claim_keywords);
        $cstmt->bindParam(":match_percentage", $match_percentage);
        
        if($cstmt->execute()){
            $pdo->prepare("UPDATE items SET status = 'pending' WHERE id = ?")->execute([$id]);
            
            // Create a notification for the item owner
            $owner_id = $item['user_id'];
            $item_title = $item['title'];
            $claimer_username = $_SESSION['username'];
            createNotification($pdo, $owner_id, "User '$claimer_username' has submitted a claim for your item '$item_title'.");

            $claim_success = true;
            // Refresh item data
            $stmt = $pdo->prepare("SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id");
            $stmt->execute(['id' => $id]);
            $item = $stmt->fetch();
        } else {
            $claim_error = "Something went wrong. Please try again.";
        }
    }
}
?>

<?php 
if($is_logged_in) {
    include 'includes/user_layout_header.php';
} else {
    include 'includes/header.php';
}
?>

<?php if($is_logged_in): ?>
<!-- ===== LOGGED-IN VIEW (Dashboard Layout) ===== -->

<!-- Back Button -->
<a href="javascript:history.back()" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--dash-text-main); font-weight: 600; margin-bottom: 30px; font-size: 0.95rem;">
    <i class="fas fa-arrow-left"></i> <?php echo __('back'); ?>
</a>

<?php if(isset($_GET['posted']) && $_GET['posted'] == 'success'): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo __('posted_success'); ?>
    </div>
<?php endif; ?>

<?php if($claim_success): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo __('claim_submitted_success'); ?>
    </div>
<?php endif; ?>

<?php if(!empty($owner_success)): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> <?php echo htmlspecialchars($owner_success); ?>
    </div>
<?php endif; ?>

<?php if(!empty($owner_error)): ?>
    <div style="background: #fee2e2; color: #ef4444; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i> <?php echo htmlspecialchars($owner_error); ?>
    </div>
<?php endif; ?>

<div class="resp-grid-sidebar">
    <!-- Left: Item Details Card -->
    <div class="dash-stat-box" style="padding: 35px;">
        <!-- Tags -->
        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
            <span class="dash-role-tag" style="background: <?php echo ($item['type'] == 'lost') ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo ($item['type'] == 'lost') ? '#ef4444' : '#10b981'; ?>; padding: 5px 14px; font-size: 0.75rem;">
                <?php echo strtoupper(__('type_' . $item['type'])); ?>
            </span>
            <span class="dash-role-tag" style="background: <?php echo ($item['status'] == 'resolved') ? '#dcfce7' : ($item['status'] == 'pending' ? '#fef9c3' : '#dbeafe'); ?>; color: <?php echo ($item['status'] == 'resolved') ? '#10b981' : ($item['status'] == 'pending' ? '#a16207' : '#3b82f6'); ?>; padding: 5px 14px; font-size: 0.75rem;">
                <?php echo strtoupper(__($item['status'])); ?>
            </span>
        </div>

        <!-- Title -->
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 25px; color: var(--dash-text-main);">
            <?php echo htmlspecialchars($item['title']); ?>
        </h1>

        <!-- Image -->
        <div style="border-radius: 20px; overflow: hidden; margin-bottom: 30px; background: #f1f5f9; position: relative;">
            <?php 
            $is_owner = isset($_SESSION['id']) && $_SESSION['id'] == $item['user_id'];
            $has_image = !empty($item['image_path']);
            if ($has_image && !$is_owner): 
            ?>
                <div class="blur-container" style="height: 450px; max-height: 450px;">
                    <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="blurry-img" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.src='assets/images/placeholder.png'">
                    <div class="blur-overlay">
                        <div class="blur-overlay-icon"><i class="fas fa-eye-slash"></i></div>
                        <div class="blur-overlay-title" style="font-size: 1.4rem;"><?php echo __('verification_blur_protected'); ?></div>
                        <div class="blur-overlay-desc" style="font-size: 0.9rem; max-width: 80%; margin-top: 10px;"><?php echo __('blur_desc_owner'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <img src="<?php echo $has_image ? $item['image_path'] : 'assets/images/placeholder.png'; ?>"
                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                     style="width: 100%; max-height: 450px; object-fit: cover; display: block;"
                     onerror="this.src='assets/images/placeholder.png'">
            <?php endif; ?>
        </div>

        <!-- Description -->
        <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 12px;"><?php echo __('description'); ?></h3>
        <p style="color: var(--dash-text-muted); line-height: 1.8; margin-bottom: 30px; font-size: 0.95rem;">
            <?php echo nl2br(htmlspecialchars($item['description'])); ?>
        </p>

        <!-- Metadata Grid -->
        <div class="resp-grid-2col" style="background: #f8fafc; padding: 25px; border-radius: 20px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-tag"></i>
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('category'); ?></p>
                    <p style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($item['category']); ?></p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dbeafe; color: #3b82f6; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('location'); ?></p>
                    <p style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($item['location']); ?></p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #fef9c3; color: #a16207; display: flex; align-items: center; justify-content: center;">
                    <i class="far fa-calendar-alt"></i>
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('date'); ?></p>
                    <p style="font-weight: 700; font-size: 0.95rem;"><?php echo date("F d, Y", strtotime($item['date_spotted'])); ?></p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #dcfce7; color: #10b981; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('reported_by'); ?></p>
                    <p style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($item['username']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div>
        <!-- Claim Action Card -->
        <?php if($_SESSION['id'] != $item['user_id'] && $item['status'] != 'resolved'): ?>
        <div class="dash-stat-box" style="padding: 30px; margin-bottom: 20px;">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 20px;">
                <?php echo ($item['type'] == 'found') ? __('is_this_yours') : __('did_you_find_this'); ?>
            </h3>
            <button onclick="document.getElementById('claimModal').style.display='flex'" class="btn btn-primary" style="width: 100%; padding: 14px; border-radius: 14px; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="far fa-comment-dots"></i>
                <?php echo ($item['type'] == 'found') ? __('thats_mine') : __('i_found_this'); ?>
            </button>
        </div>
        <?php elseif($_SESSION['id'] == $item['user_id']): ?>
        <div class="dash-stat-box" style="padding: 30px; margin-bottom: 20px;">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 10px;"><?php echo __('your_item'); ?></h3>
            <p style="color: var(--dash-text-muted); font-size: 0.9rem;"><?php echo __('your_item_desc'); ?></p>
        </div>
        <?php endif; ?>

        <!-- Share Card -->
        <div class="dash-stat-box" style="padding: 30px;">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 20px;"><?php echo __('share'); ?></h3>
            <button onclick="copyLink()" class="btn btn-outline" style="width: 100%; padding: 12px; border-radius: 14px; display: flex; align-items: center; justify-content: center; gap: 10px;" id="copyBtn">
                <i class="far fa-copy"></i> <?php echo __('copy_link'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Claim Modal -->
<?php if($is_logged_in && $_SESSION['id'] != $item['user_id']): ?>
<div id="claimModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: white; border-radius: 28px; width: 100%; max-width: 500px; padding: 40px; position: relative; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);">
        <button onclick="document.getElementById('claimModal').style.display='none'" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 1.3rem; cursor: pointer; color: #94a3b8; padding: 5px;">
            <i class="fas fa-times"></i>
        </button>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px;"><?php echo __('claim_item'); ?></h2>
        <p style="color: var(--dash-text-muted); margin-bottom: 30px;"><?php echo __('claim_item_desc'); ?></p>
        
        <?php if($claim_error): ?>
            <div style="background: #fee2e2; color: #ef4444; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.9rem; font-weight: 600;">
                <?php echo $claim_error; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="item-details.php?id=<?php echo $item['id']; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('proof_image_optional'); ?></label>
                <input type="file" name="proof_image" class="form-control" style="margin-top: 8px; border-radius: 14px; padding: 10px 18px;">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('keywords_comma_separated'); ?></label>
                <input type="text" name="claim_keywords" class="form-control" placeholder="e.g. silver, keychain" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('message'); ?></label>
                <textarea name="claim_message" class="form-control" rows="4" placeholder="<?php echo __('message_placeholder'); ?>" style="margin-top: 8px; border-radius: 14px; padding: 14px 18px; resize: vertical;" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; border-radius: 14px; font-size: 1rem; margin-top: 10px;">
                <?php echo __('submit_claim'); ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($is_logged_in && $_SESSION['id'] == $item['user_id']): ?>
<!-- Claim Requests for Item Owner -->
<div style="margin-top: 40px; margin-bottom: 40px;">
    <h2 style="font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-clipboard-list" style="color: var(--dash-accent);"></i> <?php echo sprintf(__('claim_requests'), count($item_claims)); ?>
    </h2>
    <p style="color: var(--dash-text-muted); margin-bottom: 25px;"><?php echo __('claim_requests_desc'); ?></p>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($item_claims as $claim): 
            $statusColor = $claim['status'] == 'accepted' ? '#10b981' : ($claim['status'] == 'rejected' ? '#ef4444' : '#f59e0b');
            $statusBg = $claim['status'] == 'accepted' ? '#dcfce7' : ($claim['status'] == 'rejected' ? '#fee2e2' : '#fef9c3');
        ?>
        <div class="dash-stat-box" style="padding: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 5px 0; color: var(--dash-text-main);">
                        <?php echo __('claim_from'); ?> <span style="color: var(--dash-accent);"><?php echo htmlspecialchars($claim['claimer_name']); ?></span>
                    </h4>
                    <p style="margin: 0; font-size: 0.85rem; color: var(--dash-text-muted);"><?php echo htmlspecialchars($claim['claimer_email']); ?></p>
                </div>
                <span class="dash-role-tag" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; padding: 6px 14px; font-size: 0.75rem;">
                    <?php echo strtoupper(__($claim['status'])); ?>
                </span>
            </div>

            <div style="background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 15px; border-left: 4px solid var(--dash-accent);">
                <?php if ($claim['match_percentage'] > 0): ?>
                    <div style="margin-bottom: 15px; display: inline-block;">
                        <span style="background: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; border: 1px solid #fcd34d;">
                            <i class="fas fa-magic"></i> <?php echo $claim['match_percentage']; ?>% System Match Score
                        </span>
                    </div>
                <?php endif; ?>
                
                <p style="font-size: 0.8rem; font-weight: 700; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;"><?php echo __('claim_proof'); ?></p>
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--dash-text-main); margin: 0 0 15px 0;"><?php echo nl2br(htmlspecialchars($claim['message'])); ?></p>
                
                <?php if(!empty($claim['claim_keywords'])): ?>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;"><i class="fas fa-tags"></i> Keywords: <?php echo htmlspecialchars($claim['claim_keywords']); ?></p>
                <?php endif; ?>
                
                <?php if(!empty($claim['proof_image'])): ?>
                    <div style="margin-top: 10px;">
                        <img src="<?php echo $claim['proof_image']; ?>" style="max-width: 200px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" alt="Proof Image">
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <p style="font-size: 0.8rem; color: #94a3b8; margin: 0;"><i class="far fa-clock"></i> <?php echo __('submitted'); ?> <?php echo date("F j, Y, g:i a", strtotime($claim['created_at'])); ?></p>
                
                <?php if ($claim['status'] == 'pending'): ?>
                <div style="display: flex; gap: 10px;">
                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to ACCEPT this claim? This will resolve the item and reject all other claims.');">
                        <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                        <input type="hidden" name="owner_action" value="accept">
                        <button type="submit" class="btn btn-primary" style="background: #10b981; border: none; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; color: white; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                            <i class="fas fa-check"></i> <?php echo __('accept'); ?>
                        </button>
                    </form>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to REJECT this claim?');">
                        <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                        <input type="hidden" name="owner_action" value="reject">
                        <button type="submit" class="btn btn-outline" style="border: 1px solid #ef4444; color: #ef4444; padding: 8px 16px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 5px; cursor: pointer; background: white;">
                            <i class="fas fa-times"></i> <?php echo __('reject'); ?>
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($item_claims)): ?>
            <div class="dash-stat-box" style="text-align: center; padding: 40px; color: var(--dash-text-muted);">
                <i class="far fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                <?php echo __('no_claims_yet'); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if(!empty($matches)): ?>
<!-- Potential Matches Section -->
<div style="margin-top: 40px; margin-bottom: 40px;">
    <h2 style="font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-magic" style="color: #f59e0b;"></i> <?php echo __('potential_matches'); ?>
    </h2>
    <p style="color: var(--dash-text-muted); margin-bottom: 25px;"><?php echo __('potential_matches_desc'); ?></p>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
        <?php foreach($matches as $match): ?>
        <div class="dash-stat-box" style="padding: 0; overflow: hidden; border: 2px solid #f59e0b; display: flex; flex-direction: column;">
            <!-- Blurred Match Image -->
            <div style="height: 180px; overflow: hidden; position: relative; background: #f1f5f9;">
                <?php if(!empty($match['image_path'])): ?>
                    <div class="blur-container" style="height: 180px;">
                        <img src="<?php echo $match['image_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;" class="blurry-img" onerror="this.src='assets/images/placeholder.png'">
                        <div class="blur-overlay" style="padding: 10px;">
                            <div class="blur-overlay-icon" style="width: 36px; height: 36px; font-size: 1.1rem; margin-bottom: 6px;"><i class="fas fa-eye-slash"></i></div>
                            <div class="blur-overlay-title" style="font-size: 0.8rem; margin-bottom: 0;"><?php echo __('verification_blur'); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <img src="assets/images/placeholder.png" style="width: 100%; height: 100%; object-fit: cover;">
                <?php endif; ?>
            </div>
            
            <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Dynamic Match Percentage Badge -->
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                        <span class="dash-role-tag" style="background: #fef3c7; color: #d97706; font-weight: 700; border: 1px solid #fcd34d; padding: 4px 10px; border-radius: 8px;">
                            <i class="fas fa-chart-pie"></i> <?php echo $match['match_percentage']; ?>% Match
                        </span>
                    </div>
                    
                    <h3 style="font-weight: 700; margin-bottom: 8px; font-size: 1.1rem; color: var(--dash-text-main);"><?php echo htmlspecialchars($match['title']); ?></h3>
                    
                    <!-- Match Heuristics List -->
                    <ul style="list-style: none; padding: 0; margin: 12px 0 20px; font-size: 0.75rem; color: var(--dash-text-muted); display: flex; flex-direction: column; gap: 6px;">
                        <?php foreach($match['match_reasons'] as $reason): ?>
                            <li style="display: flex; align-items: center; gap: 6px; text-align: left;">
                                <i class="fas fa-check" style="color: #10b981;"></i> <?php echo $reason; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <a href="item-details.php?id=<?php echo $match['id']; ?>" class="btn btn-outline" style="width: 100%; padding: 10px; text-decoration: none; text-align: center; display: block; font-size: 0.85rem; border-radius: 12px;"><?php echo __('check_it_out'); ?></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        const btn = document.getElementById('copyBtn');
        btn.innerHTML = '<i class="fas fa-check"></i> <?php echo __('copied'); ?>';
        btn.style.color = '#10b981';
        btn.style.borderColor = '#10b981';
        setTimeout(() => { btn.innerHTML = '<i class="far fa-copy"></i> <?php echo __('copy_link'); ?>'; btn.style.color = ''; btn.style.borderColor = ''; }, 2000);
    });
}
<?php if($claim_error): ?>
document.getElementById('claimModal').style.display = 'flex';
<?php endif; ?>
</script>

<?php else: ?>
<!-- ===== PUBLIC VIEW (No Sidebar) ===== -->
<div class="container animate-fade mt-50">
    <?php if(isset($_GET['posted']) && $_GET['posted'] == 'success'): ?>
        <div style="background: rgba(46, 204, 113, 0.1); color: var(--primary); padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; border: 1px solid var(--primary);">
            <i class="fas fa-check-circle"></i> <?php echo __('posted_success'); ?>
        </div>
    <?php endif; ?>

    <div class="resp-grid-2col" style="background: var(--white); padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
        <div>
            <?php if (!empty($item['image_path'])): ?>
                <div class="blur-container" style="border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); height: 350px;">
                    <img src="<?php echo $item['image_path']; ?>" alt="Item" class="blurry-img" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='assets/images/placeholder.png'">
                    <div class="blur-overlay">
                        <div class="blur-overlay-icon"><i class="fas fa-eye-slash"></i></div>
                        <div class="blur-overlay-title"><?php echo __('verification_blur_protected'); ?></div>
                        <div class="blur-overlay-desc" style="font-size: 0.8rem; max-width: 80%; margin-top: 5px;"><?php echo __('blur_desc_login'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <img src="assets/images/placeholder.png" alt="Item" style="width: 100%; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
            <?php endif; ?>
        </div>
        <div>
            <span class="card-tag <?php echo ($item['type'] == 'lost') ? 'tag-lost' : 'tag-found'; ?>"><?php echo __('type_' . $item['type']); ?></span>
            <h1 style="font-size: 2.5rem; margin: 10px 0;"><?php echo htmlspecialchars($item['title']); ?></h1>
            <p style="color: var(--gray); font-size: 1.1rem; margin-bottom: 20px;"><i class="fas fa-user"></i> <?php echo __('reported_by'); ?> <?php echo htmlspecialchars($item['username']); ?></p>
            
            <div style="background: var(--light); padding: 20px; border-radius: 15px; margin-bottom: 30px;">
                <p><strong><?php echo __('category'); ?>:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                <p><strong><?php echo __('location'); ?>:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                <p><strong><?php echo __('date'); ?>:</strong> <?php echo date("F d, Y", strtotime($item['date_spotted'])); ?></p>
                <p><strong><?php echo __('status'); ?>:</strong> <span class="badge badge-<?php echo $item['status']; ?>"><?php echo __($item['status']); ?></span></p>
            </div>

            <h3 style="margin-bottom: 15px;"><?php echo __('description'); ?></h3>
            <p style="color: var(--dark); line-height: 1.8; margin-bottom: 30px;"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>

            <a href="login.php" class="btn btn-primary" style="width: 100%;"><?php echo __('login_to_claim'); ?></a>
        </div>
    </div>

    <?php if(!empty($matches)): ?>
        <div class="mt-50 animate-fade">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-magic" style="color: var(--accent);"></i> <?php echo __('potential_matches'); ?></h2>
            <p style="color: var(--gray); margin-bottom: 30px;"><?php echo __('potential_matches_desc'); ?></p>
            <div class="card-grid">
                <?php foreach($matches as $match): ?>
                    <div class="card" style="border: 2px solid var(--accent); display: flex; flex-direction: column; overflow: hidden;">
                        <div style="height: 180px; overflow: hidden; position: relative; background: #f1f5f9;">
                            <?php if(!empty($match['image_path'])): ?>
                                <div class="blur-container" style="height: 180px;">
                                    <img src="<?php echo $match['image_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;" class="blurry-img" onerror="this.src='assets/images/placeholder.png'">
                                    <div class="blur-overlay" style="padding: 10px;">
                                        <div class="blur-overlay-icon" style="width: 36px; height: 36px; font-size: 1.1rem; margin-bottom: 6px;"><i class="fas fa-eye-slash"></i></div>
                                        <div class="blur-overlay-title" style="font-size: 0.8rem; margin-bottom: 0;"><?php echo __('verification_blur'); ?></div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <img src="assets/images/placeholder.png" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                        </div>
                        <div class="card-content" style="flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 20px;">
                            <div>
                                <span class="badge" style="background: var(--accent); color: #fff; margin-bottom: 10px; font-weight: 700;"><?php echo $match['match_percentage']; ?>% Match</span>
                                <h3 style="font-size: 1.1rem; margin-bottom: 10px; font-weight: 700;"><?php echo htmlspecialchars($match['title']); ?></h3>
                                <ul style="list-style: none; padding: 0; margin: 10px 0 20px; font-size: 0.75rem; color: var(--gray); display: flex; flex-direction: column; gap: 5px;">
                                    <?php foreach($match['match_reasons'] as $reason): ?>
                                        <li style="display: flex; align-items: center; gap: 6px; text-align: left;">
                                            <i class="fas fa-check" style="color: #10b981;"></i> <?php echo $reason; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <a href="item-details.php?id=<?php echo $match['id']; ?>" class="btn btn-outline" style="width: 100%; margin-top: 10px; font-size: 0.8rem; border-radius: 10px;"><?php echo __('check_it_out'); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php 
if($is_logged_in) {
    include 'includes/user_layout_footer.php';
} else {
    include 'includes/footer.php';
}
?>
