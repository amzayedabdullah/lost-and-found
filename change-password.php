<?php include 'includes/user_layout_header.php'; ?>

<?php
$user_id = $_SESSION['id'];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate
    if (empty($current_password)) {
        $error = "Please enter your current password.";
    } elseif (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (password_verify($current_password, $user['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new_hash, $user_id])) {
                $success = "Password changed successfully!";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>

<div class="dash-page-header">
    <h1><?php echo __('change_password'); ?></h1>
    <p><?php echo __('update_security_creds'); ?></p>
</div>

<div style="max-width: 600px;">
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
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 35px;">
            <div style="width: 60px; height: 60px; border-radius: 18px; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div>
                <h3 style="font-weight: 700; margin-bottom: 4px;"><?php echo __('security_settings'); ?></h3>
                <p style="color: var(--dash-text-muted); font-size: 0.9rem;"><?php echo __('choose_strong_password'); ?></p>
            </div>
        </div>

        <form action="change-password.php" method="post">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('current_password'); ?></label>
                <div style="position: relative; margin-top: 8px;">
                    <input type="password" name="current_password" id="current_pw" class="form-control" placeholder="<?php echo __('enter_current_pw'); ?>" style="border-radius: 14px; padding: 14px 50px 14px 18px;" required>
                    <i class="fas fa-eye-slash" onclick="togglePw('current_pw', this)" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('new_password'); ?></label>
                <div style="position: relative; margin-top: 8px;">
                    <input type="password" name="new_password" id="new_pw" class="form-control" placeholder="<?php echo __('min_characters'); ?>" style="border-radius: 14px; padding: 14px 50px 14px 18px;" required minlength="8">
                    <i class="fas fa-eye-slash" onclick="togglePw('new_pw', this)" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                <div id="pw-strength" style="margin-top: 10px; display: flex; gap: 6px;">
                    <div style="height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0;" id="str1"></div>
                    <div style="height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0;" id="str2"></div>
                    <div style="height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0;" id="str3"></div>
                    <div style="height: 4px; flex: 1; border-radius: 4px; background: #e2e8f0;" id="str4"></div>
                </div>
                <p id="pw-text" style="font-size: 0.8rem; color: #94a3b8; margin-top: 6px;"></p>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 0.85rem; color: var(--dash-text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo __('confirm_new_password'); ?></label>
                <div style="position: relative; margin-top: 8px;">
                    <input type="password" name="confirm_password" id="confirm_pw" class="form-control" placeholder="<?php echo __('reenter_new_pw'); ?>" style="border-radius: 14px; padding: 14px 50px 14px 18px;" required minlength="8">
                    <i class="fas fa-eye-slash" onclick="togglePw('confirm_pw', this)" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                <p id="match-text" style="font-size: 0.8rem; margin-top: 6px;"></p>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 35px;">
                <button type="submit" class="btn btn-primary" style="padding: 14px 35px; border-radius: 14px; font-size: 1rem;">
                    <i class="fas fa-lock"></i> <?php echo __('update_password'); ?>
                </button>
                <a href="profile.php" class="btn btn-outline" style="padding: 14px 35px; border-radius: 14px; text-decoration: none;"><?php echo __('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePw(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
}

// Password strength meter
document.getElementById('new_pw').addEventListener('input', function() {
    const pw = this.value;
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;

    const colors = ['#ef4444', '#f59e0b', '#10b981', '#10b981'];
    const texts = ['Weak', 'Fair', 'Good', 'Strong'];
    for (let i = 1; i <= 4; i++) {
        document.getElementById('str' + i).style.background = i <= score ? colors[score - 1] : '#e2e8f0';
    }
    document.getElementById('pw-text').textContent = pw.length > 0 ? texts[score - 1] || '' : '';
    document.getElementById('pw-text').style.color = pw.length > 0 ? colors[score - 1] : '#94a3b8';
});

// Confirm match check
document.getElementById('confirm_pw').addEventListener('input', function() {
    const match = this.value === document.getElementById('new_pw').value;
    const el = document.getElementById('match-text');
    if (this.value.length > 0) {
        el.textContent = match ? '✓ Passwords match' : '✗ Passwords do not match';
        el.style.color = match ? '#10b981' : '#ef4444';
    } else {
        el.textContent = '';
    }
});
</script>

<?php include 'includes/user_layout_footer.php'; ?>
