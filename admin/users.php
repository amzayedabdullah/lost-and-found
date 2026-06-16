<?php include 'includes/admin_header.php'; ?>

<?php
// Handle Actions
if(isset($_POST['action']) && isset($_POST['user_id'])){
    $user_id = $_POST['user_id'];
    
    if($_POST['action'] == 'delete' && $user_id != $_SESSION['id']){
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
    } elseif($_POST['action'] == 'toggle_role' && $user_id != $_SESSION['id']){
        $user = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $user->execute([$user_id]);
        $current_role = $user->fetchColumn();
        $new_role = ($current_role == 'admin') ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $user_id]);
    }
    header("Location: users.php?success=1");
    exit;
}

// Search Logic
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM users WHERE username LIKE :search OR email LIKE :search ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['search' => "%$search%"]);
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <h1><?php echo __('manage_users'); ?></h1>
    <p><?php echo __('manage_users_desc'); ?></p>
</div>

<div class="admin-actions-bar">
    <form action="users.php" method="GET" class="search-input-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" name="search" class="search-input" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
    </form>
</div>

<div class="admin-users-list">
    <p style="font-weight: 600; color: #1e293b; margin-bottom: 10px;"><?php echo __('all_users'); ?> (<?php echo count($users); ?>)</p>
    
    <?php foreach($users as $user): ?>
    <div class="admin-user-card">
        <div class="user-card-left">
            <div class="user-card-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-card-details">
                <h4>
                    <?php echo htmlspecialchars($user['username']); ?>
                    <span class="role-tag <?php echo $user['role'] == 'admin' ? 'role-admin' : ''; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                    <?php if($user['id'] == $_SESSION['id']): ?>
                        <span class="role-tag" style="background: #e0f2fe; color: #0ea5e9;">You</span>
                    <?php endif; ?>
                </h4>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="user-card-date"><?php echo __('joined_on'); ?> <?php echo date("n/j/Y", strtotime($user['created_at'])); ?></div>
            </div>
        </div>
        
        <div class="admin-item-actions">
            <?php if($user['id'] != $_SESSION['id']): ?>
                <form method="post">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="toggle_role">
                    <button type="submit" class="btn btn-outline btn-small" style="background: white; border-color: #e2e8f0; color: #1e293b;">
                        <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> 
                        <?php echo $user['role'] == 'admin' ? 'Make User' : 'Make Admin'; ?>
                    </button>
                </form>
                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-outline btn-small" style="background: white; border-color: #fee2e2; color: #ef4444;">
                        <i class="far fa-trash-alt" style="margin-right: 5px;"></i> Delete
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if(empty($users)): ?>
        <div class="stat-box" style="text-align: center; color: #94a3b8; padding: 50px;">
            No users found matching your search.
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
