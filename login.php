<?php
require_once 'includes/config.php';

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if($_SESSION["role"] === 'admin') {
        header("location: admin/dashboard.php");
    } else {
        header("location: index.php");
    }
    exit;
}

$email = $password = "";
$email_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter your email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($email_err) && empty($password_err)){
        // We'll allow login by email as per the screenshot
        $sql = "SELECT id, username, email, password, role FROM users WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = $email;
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["id"];
                        $username = $row["username"];
                        $hashed_password = $row["password"];
                        $role = $row["role"];
                        if(password_verify($password, $hashed_password)){
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            $_SESSION["role"] = $role;                            
                            
                            if($role === 'admin') {
                                header("location: admin/dashboard.php");
                            } else {
                                header("location: index.php");
                            }
                        } else{
                            $login_err = "Invalid email or password.";
                        }
                    }
                } else{
                    $login_err = "Invalid email or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-header-actions">
        <a href="index.php" class="close-btn"><i class="fas fa-times"></i></a>
        <?php if (isset($_SESSION['lang']) && $_SESSION['lang'] === 'bn'): ?>
            <a href="change_lang.php?lang=en" class="lang-btn"><i class="fas fa-language"></i> English</a>
        <?php else: ?>
            <a href="change_lang.php?lang=bn" class="lang-btn"><i class="fas fa-language"></i> বাংলা</a>
        <?php endif; ?>
    </div>
    <div class="auth-card animate-fade">
        <img src="assets/images/logo.png" alt="Logo" class="auth-logo">
        <h2><?php echo __('welcome_back_title'); ?></h2>
        <p><?php echo __('login_subtitle'); ?></p>
        
        <?php 
        if(!empty($login_err)){
            echo '<div style="background: rgba(231, 76, 60, 0.1); color: var(--secondary); padding: 10px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 0.9rem;">' . $login_err . '</div>';
        }        
        if(isset($_GET['registered']) && $_GET['registered'] == 'success'){
            echo '<div style="background: rgba(46, 204, 113, 0.1); color: var(--primary); padding: 10px; border-radius: 10px; margin-bottom: 20px; text-align: center; font-size: 0.9rem;">' . __('registration_successful') . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label><?php echo __('email'); ?></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" class="form-control" placeholder="Enter Your Mail Address" value="<?php echo $email; ?>" required>
                </div>
                <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $email_err; ?></span>
            </div>    
            <div class="form-group">
                <label><?php echo __('password'); ?></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="8 characters minimum" required>
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
                </div>
                <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $password_err; ?></span>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo __('login'); ?></button>
            <div class="auth-footer">
                <?php echo __('no_account'); ?> <a href="register.php"><?php echo __('register_here'); ?></a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(id, icon) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>
