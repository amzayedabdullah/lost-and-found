<?php include 'includes/admin_header.php'; ?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'rescan') {
        require_once '../includes/matching_algorithm.php';
        $open_items_stmt = $pdo->query("SELECT id, keywords, type FROM items WHERE status = 'open'");
        $count = 0;
        while ($item = $open_items_stmt->fetch()) {
            getPotentialMatches($pdo, $item['id'], $item['keywords'], $item['type']);
            $count++;
        }
        $success_msg = "Successfully rescanned $count open items for new matches!";
    } elseif (isset($_POST['match_id'])) {
        $match_id = $_POST['match_id'];
        $action = $_POST['action'];

    // Fetch match details
    $stmt = $pdo->prepare("SELECT * FROM system_matches WHERE id = ?");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();

    if ($match && $match['status'] == 'pending') {
        if ($action == 'verify') {
            $pdo->beginTransaction();
            try {
                // Set match as verified
                $pdo->prepare("UPDATE system_matches SET status = 'verified' WHERE id = ?")->execute([$match_id]);
                
                // Get lost item owner
                $lost_stmt = $pdo->prepare("SELECT user_id, title FROM items WHERE id = ?");
                $lost_stmt->execute([$match['lost_item_id']]);
                $lost_item = $lost_stmt->fetch();
                
                // Create a system claim on the Found item on behalf of the Lost item owner
                $claim_msg = "SYSTEM GENERATED SMART MATCH. An admin has verified that this found item matches your lost item: '" . $lost_item['title'] . "'. Please review and accept.";
                $claim_stmt = $pdo->prepare("INSERT INTO claims (item_id, claimer_id, message, status) VALUES (?, ?, ?, 'pending')");
                $claim_stmt->execute([$match['found_item_id'], $lost_item['user_id'], $claim_msg]);
                
                // Set found item to pending
                $pdo->prepare("UPDATE items SET status = 'pending' WHERE id = ?")->execute([$match['found_item_id']]);
                
                // Notify the Lost item owner
                createNotification($pdo, $lost_item['user_id'], "A Smart System Match for your lost item '" . $lost_item['title'] . "' has been verified by an admin! Check your reports.");
                
                $pdo->commit();
                $success_msg = "Match verified successfully! Users have been notified.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Error verifying match: " . $e->getMessage();
            }
        } elseif ($action == 'reject') {
            $pdo->prepare("UPDATE system_matches SET status = 'rejected' WHERE id = ?")->execute([$match_id]);
            $success_msg = "Match rejected.";
        }
    }
    }
}

// Fetch pending matches
$matches_query = $pdo->query("
    SELECT sm.*, 
           l.title as lost_title, l.image_path as lost_img, l.keywords as lost_keywords, l.location as lost_loc,
           f.title as found_title, f.image_path as found_img, f.keywords as found_keywords, f.location as found_loc
    FROM system_matches sm
    JOIN items l ON sm.lost_item_id = l.id
    JOIN items f ON sm.found_item_id = f.id
    WHERE sm.status = 'pending'
    ORDER BY sm.match_percentage DESC, sm.created_at DESC
");
$pending_matches = $matches_query->fetchAll();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1><?php echo __('verify_matches'); ?></h1>
        <p><?php echo __('verify_matches_desc'); ?></p>
    </div>
    <form method="post" style="margin: 0;">
        <input type="hidden" name="action" value="rescan">
        <button type="submit" class="btn btn-outline" style="border-color: #3b82f6; color: #3b82f6; padding: 10px 20px; border-radius: 12px; background: white; cursor: pointer;">
            <i class="fas fa-sync-alt"></i> Rescan All Items
        </button>
    </form>
</div>

<?php if(isset($success_msg)): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<?php if(isset($error_msg)): ?>
    <div style="background: #fee2e2; color: #ef4444; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<div class="admin-items-grid">
    <?php foreach($pending_matches as $match): ?>
        <div class="admin-item-card" style="flex-direction: column; align-items: stretch;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                <h3 style="margin: 0; color: #f59e0b;"><i class="fas fa-magic"></i> <?php echo $match['match_percentage']; ?>% Match</h3>
                <span style="font-size: 0.8rem; color: #64748b;"><?php echo date("F j, Y, g:i a", strtotime($match['created_at'])); ?></span>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                <!-- Lost Item -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; gap: 15px;">
                        <img src="../<?php echo !empty($match['lost_img']) ? $match['lost_img'] : 'assets/images/placeholder.png'; ?>" style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover;">
                        <div>
                            <span class="role-tag" style="background: #fee2e2; color: #ef4444; margin-bottom: 8px; display: inline-block;">LOST ITEM</span>
                            <h4 style="margin: 0 0 8px 0; font-size: 1.1rem;"><a href="item-details.php?id=<?php echo $match['lost_item_id']; ?>" target="_blank" style="color: #1e293b; text-decoration: none;"><?php echo htmlspecialchars($match['lost_title']); ?> <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i></a></h4>
                            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #64748b;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['lost_loc']); ?></p>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($match['lost_keywords']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Found Item -->
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; gap: 15px;">
                        <img src="../<?php echo !empty($match['found_img']) ? $match['found_img'] : 'assets/images/placeholder.png'; ?>" style="width: 100px; height: 100px; border-radius: 12px; object-fit: cover;">
                        <div>
                            <span class="role-tag" style="background: #dcfce7; color: #10b981; margin-bottom: 8px; display: inline-block;">FOUND ITEM</span>
                            <h4 style="margin: 0 0 8px 0; font-size: 1.1rem;"><a href="item-details.php?id=<?php echo $match['found_item_id']; ?>" target="_blank" style="color: #1e293b; text-decoration: none;"><?php echo htmlspecialchars($match['found_title']); ?> <i class="fas fa-external-link-alt" style="font-size: 0.8rem;"></i></a></h4>
                            <p style="margin: 0 0 5px 0; font-size: 0.85rem; color: #64748b;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['found_loc']); ?></p>
                            <p style="margin: 0; font-size: 0.85rem; color: #64748b;"><i class="fas fa-tags"></i> <?php echo htmlspecialchars($match['found_keywords']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px;">
                <h5 style="margin: 0 0 10px 0; color: #d97706; font-size: 0.9rem;"><?php echo __('match_heuristics'); ?></h5>
                <ul style="margin: 0; padding-left: 20px; font-size: 0.85rem; color: #92400e;">
                    <?php 
                    $reasons = json_decode($match['match_reasons'], true) ?? [];
                    foreach($reasons as $reason): 
                    ?>
                        <li><?php echo htmlspecialchars($reason); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <form method="post" style="margin: 0;" onsubmit="return confirm('Reject this match?');">
                    <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-outline" style="border-color: #ef4444; color: #ef4444; padding: 10px 20px; border-radius: 12px;">
                        <i class="fas fa-times"></i> Reject Match
                    </button>
                </form>
                <form method="post" style="margin: 0;" onsubmit="return confirm('Verify this match? This will notify the owner and auto-create a claim.');">
                    <input type="hidden" name="match_id" value="<?php echo $match['id']; ?>">
                    <input type="hidden" name="action" value="verify">
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981; padding: 10px 20px; border-radius: 12px;">
                        <i class="fas fa-check-double"></i> Verify & Notify Users
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if(empty($pending_matches)): ?>
        <div style="background: white; padding: 50px; text-align: center; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981; margin-bottom: 15px;"></i>
            <h3 style="color: #1e293b; margin-bottom: 10px;"><?php echo __('all_caught_up'); ?></h3>
            <p style="color: #64748b;"><?php echo __('no_pending_matches'); ?></p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
