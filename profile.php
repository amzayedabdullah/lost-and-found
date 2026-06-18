<?php include 'includes/user_layout_header.php'; ?>

<?php
$user_id = $_SESSION['id'];
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;
$error = "";
$success = "";

// Handle Update Request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $username = sanitize($_POST['username']);
    $email    = sanitize($_POST['email']);
    $profile_pic = $_SESSION['profile_pic'] ?? '';

    if (isset($_FILES['profile_pic_file']) && $_FILES['profile_pic_file']['error'] == 0) {
        $target_dir = "uploads/profile_pics/";
        $file_name  = time() . "_" . basename($_FILES["profile_pic_file"]["name"]);
        $target_file = $target_dir . $file_name;
        $check = getimagesize($_FILES["profile_pic_file"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_pic_file"]["tmp_name"], $target_file)) {
                $profile_pic = $file_name;
                $_SESSION['profile_pic'] = $profile_pic;
            } else {
                $error = "Error uploading file. Please try again.";
            }
        } else {
            $error = "Please upload a valid image file (JPG, PNG, GIF).";
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $profile_pic, $user_id])) {
                $_SESSION['username'] = $username;
                $success = "Profile updated successfully!";
                $edit_mode = false;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            $error = $e->getCode() == 23000 ? "Username or Email is already taken." : "Error: " . $e->getMessage();
        }
    }
}

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$lost_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND type = 'lost'");
$lost_stmt->execute([$user_id]);
$total_lost = $lost_stmt->fetchColumn();

$found_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND type = 'found'");
$found_stmt->execute([$user_id]);
$total_found = $found_stmt->fetchColumn();

$resolved_stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE user_id = ? AND status = 'resolved'");
$resolved_stmt->execute([$user_id]);
$total_resolved = $resolved_stmt->fetchColumn();
?>

<div class="dash-page-header">
    <h1><?php echo __('profile_title'); ?></h1>
    <p><?php echo __('profile_subtitle'); ?></p>
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

<div class="resp-grid-sidebar">
    <!-- Left: Personal Info -->
    <div>
        <div class="dash-stat-box" style="margin-bottom: 30px;">
            <form action="profile.php" method="post" enctype="multipart/form-data">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px;">
                    <h3 style="font-weight: 700; font-size: 1.2rem; margin: 0;"><?php echo __('personal_info'); ?></h3>
                    <?php if(!$edit_mode): ?>
                        <a href="profile.php?edit=1" class="btn btn-outline" style="padding: 10px 20px; text-decoration: none;">
                            <i class="fas fa-pencil-alt"></i> <?php echo __('edit_profile'); ?>
                        </a>
                    <?php else: ?>
                        <div style="display: flex; gap: 12px;">
                            <a href="profile.php" class="btn btn-outline" style="padding: 10px 20px; text-decoration: none;"><?php echo __('cancel'); ?></a>
                            <button type="submit" name="update_profile" class="btn btn-primary" style="padding: 10px 20px;"><?php echo __('save_changes'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Avatar -->
                <div style="display: flex; align-items: center; gap: 30px; margin-bottom: 35px;">
                    <div style="position: relative; flex-shrink: 0;">
                        <?php
                            $pic_path = !empty($user['profile_pic']) ? 'uploads/profile_pics/'.$user['profile_pic'] : 'assets/images/default_profile.png';
                            if(!file_exists($pic_path) && !empty($user['profile_pic'])) $pic_path = 'assets/images/'.$user['profile_pic'];
                        ?>
                        <img id="avatar-preview" src="<?php echo $pic_path; ?>"
                             style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #f1f5f9; box-shadow: 0 4px 12px rgba(0,0,0,0.08);"
                             onerror="this.src='assets/images/default_profile.png'">
                        <?php if($edit_mode): ?>
                            <label for="profile_pic_file" style="position: absolute; bottom: 4px; right: 4px; width: 34px; height: 34px; border-radius: 50%; background: var(--dash-accent); color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                <i class="fas fa-camera" style="font-size: 0.85rem;"></i>
                            </label>
                            <input type="file" id="profile_pic_file" name="profile_pic_file" style="display: none;" onchange="previewImage(this)">
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 5px;"><?php echo htmlspecialchars($user['username']); ?></h4>
                        <p style="color: var(--dash-text-muted); margin-bottom: 5px;"><?php echo htmlspecialchars($user['email']); ?></p>
                        <span class="dash-role-tag"><?php echo ucfirst($user['role']); ?></span>
                        <?php if($edit_mode): ?>
                            <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 10px;"><i class="fas fa-info-circle"></i> <?php echo __('Click the camera icon to change your photo'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Fields -->
                <div class="resp-grid-2col">
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('username'); ?></label>
                        <input type="text" name="username" class="form-control"
                               value="<?php echo htmlspecialchars($user['username']); ?>"
                               <?php echo !$edit_mode ? 'readonly' : 'required'; ?>
                               style="<?php echo !$edit_mode ? 'background: #f8fafc;' : ''; ?> margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('email_address'); ?></label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($user['email']); ?>"
                               <?php echo !$edit_mode ? 'readonly' : 'required'; ?>
                               style="<?php echo !$edit_mode ? 'background: #f8fafc;' : ''; ?> margin-top: 8px; border-radius: 14px; padding: 14px 18px;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('role'); ?></label>
                        <div style="background: #f8fafc; border-radius: 14px; padding: 14px 18px; margin-top: 8px; font-weight: 600; color: var(--dash-text-main);">
                            <?php echo ucfirst($user['role']); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('member_since'); ?></label>
                        <div style="background: #f8fafc; border-radius: 14px; padding: 14px 18px; margin-top: 8px; color: var(--dash-text-muted);">
                            <i class="far fa-calendar-alt"></i> <?php echo date("F j, Y", strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Security -->
        <div class="dash-stat-box">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 10px;"><?php echo __('security'); ?></h3>
            <p style="color: var(--dash-text-muted); font-size: 0.9rem; margin-bottom: 25px;"><?php echo __('security_desc'); ?></p>
            <a href="change-password.php" class="btn btn-outline" style="padding: 12px 25px; text-decoration: none;">
                <i class="fas fa-key"></i> <?php echo __('change_password'); ?>
            </a>
        </div>
    </div>

    <!-- Right: Stats -->
    <div>
        <div class="dash-stat-box" style="margin-bottom: 30px;">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 25px;"><?php echo __('account_stats'); ?></h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 20px; padding: 18px; background: #f8fafc; border-radius: 18px;">
                    <div class="dash-stat-icon" style="background: #fee2e2; color: #ef4444; width: 50px; height: 50px;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--dash-text-muted); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('total_lost'); ?></p>
                        <h4 style="margin: 4px 0 0; font-size: 1.4rem; font-weight: 700;"><?php echo $total_lost; ?></h4>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px; padding: 18px; background: #f8fafc; border-radius: 18px;">
                    <div class="dash-stat-icon" style="background: #dcfce7; color: #10b981; width: 50px; height: 50px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--dash-text-muted); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('total_found'); ?></p>
                        <h4 style="margin: 4px 0 0; font-size: 1.4rem; font-weight: 700;"><?php echo $total_found; ?></h4>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 20px; padding: 18px; background: #f8fafc; border-radius: 18px;">
                    <div class="dash-stat-icon" style="background: #dbeafe; color: #3b82f6; width: 50px; height: 50px;">
                        <i class="fas fa-link"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--dash-text-muted); margin: 0; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('resolved'); ?></p>
                        <h4 style="margin: 4px 0 0; font-size: 1.4rem; font-weight: 700;"><?php echo $total_resolved; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-stat-box">
            <h3 style="font-weight: 700; font-size: 1.2rem; margin-bottom: 20px;"><?php echo __('account_status'); ?></h3>
            <div style="display: flex; flex-direction: column; gap: 15px; font-size: 0.9rem;">
                <div style="display: flex; align-items: center; gap: 12px; color: #10b981;">
                    <i class="fas fa-check-circle" style="font-size: 1.1rem;"></i>
                    <span style="color: var(--dash-text-main); font-weight: 500;"><?php echo __('email_verified'); ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; color: #10b981;">
                    <i class="fas fa-shield-alt" style="font-size: 1.1rem;"></i>
                    <span style="color: var(--dash-text-main); font-weight: 500;"><?php echo __('account_secured'); ?></span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; color: #10b981;">
                    <i class="fas fa-user-check" style="font-size: 1.1rem;"></i>
                    <span style="color: var(--dash-text-main); font-weight: 500;"><?php echo __('profile_complete'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'includes/user_layout_footer.php'; ?>
