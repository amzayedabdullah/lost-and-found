<?php include 'includes/admin_header.php'; ?>

<?php
// Handle claim status update actions (Accept/Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['claim_id'])) {
    $claim_id = (int)$_POST['claim_id'];
    $action = $_POST['action'];

    // Fetch the claim to confirm it exists and get details
    $c_stmt = $pdo->prepare("
        SELECT c.*, i.title as item_title, i.user_id as owner_id, u1.username as claimer_name, u1.email as claimer_email 
        FROM claims c 
        JOIN items i ON c.item_id = i.id 
        JOIN users u1 ON c.claimer_id = u1.id
        WHERE c.id = ?
    ");
    $c_stmt->execute([$claim_id]);
    $claim_details = $c_stmt->fetch();

    if ($claim_details) {
        $item_id = $claim_details['item_id'];
        $claimer_id = $claim_details['claimer_id'];
        $owner_id = $claim_details['owner_id'];
        $item_title = $claim_details['item_title'];

        if ($action === 'accept') {
            // Start transaction
            $pdo->beginTransaction();
            try {
                // 1. Accept this claim
                $pdo->prepare("UPDATE claims SET status = 'accepted' WHERE id = ?")->execute([$claim_id]);

                // 2. Reject all other pending claims for this item
                $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE item_id = ? AND id != ? AND status = 'pending'")->execute([$item_id, $claim_id]);

                // 3. Mark the item as resolved
                $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = ?")->execute([$item_id]);

                // 4. Create notification for the claimer
                createNotification($pdo, $claimer_id, "Your claim for '$item_title' has been accepted! Please contact the owner or administrator to proceed with recovery.");

                // 5. Create notification for the owner
                createNotification($pdo, $owner_id, "The claim by user '$claim_details[claimer_name]' for your item '$item_title' has been accepted.");

                $pdo->commit();
                header("Location: claims.php?success=accepted");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Error processing acceptance: " . $e->getMessage();
            }
        } elseif ($action === 'reject') {
            $pdo->beginTransaction();
            try {
                // 1. Reject this claim
                $pdo->prepare("UPDATE claims SET status = 'rejected' WHERE id = ?")->execute([$claim_id]);

                // 2. Check if there are other pending claims for this item
                $p_stmt = $pdo->prepare("SELECT COUNT(*) FROM claims WHERE item_id = ? AND status = 'pending'");
                $p_stmt->execute([$item_id]);
                $other_pendings = $p_stmt->fetchColumn();

                // If no other pending claims, mark the item status back to open
                if ($other_pendings == 0) {
                    $pdo->prepare("UPDATE items SET status = 'open' WHERE id = ?")->execute([$item_id]);
                }

                // 3. Create notification for the claimer
                createNotification($pdo, $claimer_id, "Your claim for '$item_title' has been rejected.");

                $pdo->commit();
                header("Location: claims.php?success=rejected");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Error processing rejection: " . $e->getMessage();
            }
        }
    }
}

// Search Logic
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

$query = "
    SELECT c.*, 
           i.title as item_title, i.type as item_type,
           u1.username as claimer_name, 
           u2.username as owner_name
    FROM claims c 
    JOIN items i ON c.item_id = i.id 
    JOIN users u1 ON c.claimer_id = u1.id
    JOIN users u2 ON i.user_id = u2.id
    WHERE (i.title LIKE :search OR u1.username LIKE :search OR u2.username LIKE :search)
";
$params = ['search' => "%$search%"];

if (in_array($status_filter, ['pending', 'accepted', 'rejected'])) {
    $query .= " AND c.status = :status";
    $params['status'] = $status_filter;
}
$query .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$claims = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><?php echo __('manage_claims'); ?></h1>
    <p><?php echo __('manage_claims_desc'); ?></p>
</div>

<?php if(isset($_GET['success'])): ?>
    <div style="background: #dcfce7; color: #10b981; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-check-circle" style="font-size: 1.2rem;"></i> Claim successfully <?php echo htmlspecialchars($_GET['success']); ?>!
    </div>
<?php endif; ?>
<?php if(isset($error_msg)): ?>
    <div style="background: #fee2e2; color: #ef4444; padding: 18px 25px; border-radius: 16px; margin-bottom: 30px; font-weight: 600; display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem;"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>

<div class="admin-actions-bar">
    <form action="claims.php" method="GET" style="display: flex; gap: 20px; width: 100%;">
        <div class="search-input-wrapper" style="flex: 1;">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="Search by item title or username..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="all"><?php echo __('all_statuses'); ?></option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>><?php echo __('pending'); ?></option>
            <option value="accepted" <?php echo $status_filter == 'accepted' ? 'selected' : ''; ?>><?php echo __('accepted'); ?></option>
            <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>><?php echo __('rejected'); ?></option>
        </select>
    </form>
</div>

<div style="display: flex; flex-direction: column; gap: 20px;">
    <p style="font-weight: 700; color: var(--dash-text-main);"><?php echo __('all_claims'); ?> (<?php echo count($claims); ?>)</p>

    <?php foreach($claims as $claim): 
        $statusColor = $claim['status'] == 'accepted' ? '#10b981' : ($claim['status'] == 'rejected' ? '#ef4444' : '#f59e0b');
        $statusBg = $claim['status'] == 'accepted' ? '#dcfce7' : ($claim['status'] == 'rejected' ? '#fee2e2' : '#fef9c3');
        $typeBg = $claim['item_type'] == 'lost' ? '#fee2e2' : '#dcfce7';
        $typeColor = $claim['item_type'] == 'lost' ? '#ef4444' : '#10b981';
    ?>
    <div style="background: white; padding: 30px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 8px;">
                    Claim for: <span style="color: var(--primary);"><?php echo htmlspecialchars($claim['item_title']); ?></span>
                </h3>
                <span class="role-tag" style="background: <?php echo $typeBg; ?>; color: <?php echo $typeColor; ?>;">
                    <?php echo strtoupper($claim['item_type']); ?> ITEM
                </span>
            </div>
            <span class="role-tag" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-size: 0.8rem; padding: 8px 16px;">
                <?php echo strtoupper($claim['status']); ?>
            </span>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div style="background: #f8fafc; padding: 15px; border-radius: 16px;">
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('claimed_by'); ?></p>
                <p style="font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-user" style="color: var(--primary);"></i> <?php echo htmlspecialchars($claim['claimer_name']); ?></p>
            </div>
            <div style="background: #f8fafc; padding: 15px; border-radius: 16px;">
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('item_owner'); ?></p>
                <p style="font-weight: 700; display: flex; align-items: center; gap: 8px;"><i class="fas fa-user-shield" style="color: #3b82f6;"></i> <?php echo htmlspecialchars($claim['owner_name']); ?></p>
            </div>
        </div>

        <div style="background: #f8fafc; padding: 20px; border-radius: 16px; margin-bottom: 15px; border-left: 4px solid var(--primary);">
            <?php if ($claim['match_percentage'] > 0): ?>
                <div style="margin-bottom: 15px; display: inline-block;">
                    <span style="background: #fef3c7; color: #d97706; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; border: 1px solid #fcd34d;">
                        <i class="fas fa-magic"></i> <?php echo $claim['match_percentage']; ?>% System Match Score
                    </span>
                </div>
            <?php endif; ?>
            
            <p style="font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;"><?php echo __('claim_proof'); ?></p>
            <p style="font-size: 0.95rem; line-height: 1.7; color: #1e293b; margin: 0 0 15px 0;"><?php echo nl2br(htmlspecialchars($claim['message'])); ?></p>
            
            <?php if(!empty($claim['claim_keywords'])): ?>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px;"><i class="fas fa-tags"></i> Keywords: <?php echo htmlspecialchars($claim['claim_keywords']); ?></p>
            <?php endif; ?>
            
            <?php if(!empty($claim['proof_image'])): ?>
                <div style="margin-top: 10px;">
                    <img src="../<?php echo $claim['proof_image']; ?>" style="max-width: 200px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" alt="Proof Image">
                </div>
            <?php endif; ?>
        </div>
        
        <p style="font-size: 0.8rem; color: #94a3b8;"><i class="far fa-clock"></i> Submitted <?php echo date("F j, Y, g:i a", strtotime($claim['created_at'])); ?></p>
        
        <?php if ($claim['status'] == 'pending'): ?>
        <div style="margin-top: 20px; display: flex; gap: 15px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to ACCEPT this claim? This will mark the item as resolved.');">
                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                <input type="hidden" name="action" value="accept">
                <button type="submit" style="background: #10b981; border: none; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; color: white; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <i class="fas fa-check"></i> Accept Claim
                </button>
            </form>
            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to REJECT this claim?');">
                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" style="border: 1px solid #ef4444; color: #ef4444; padding: 10px 20px; border-radius: 10px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; cursor: pointer; background: white;">
                    <i class="fas fa-times"></i> Reject Claim
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if(empty($claims)): ?>
        <div style="background: white; padding: 50px; text-align: center; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <i class="fas fa-clipboard-list" style="font-size: 3rem; color: #94a3b8; margin-bottom: 15px;"></i>
            <h3 style="color: #1e293b; margin-bottom: 10px;">No claims found</h3>
            <p style="color: #64748b;">No claim requests match your current filters</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
