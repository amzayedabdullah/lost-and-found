<?php 
require_once 'includes/config.php'; 

$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Validate username
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT id FROM users WHERE username = :username";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $param_username = trim($_POST["username"]);
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = trim($_POST["email"]);
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            unset($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 8){
        $password_err = "Password must have at least 8 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)){
        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
         
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            
            if($stmt->execute()){
                header("location: login.php?registered=success");
            } else {
                echo "Something went wrong. Please try again later.";
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
    <title><?php echo __('register_title'); ?></title>
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
        <h2><?php echo __('create_account'); ?></h2>
        <p><?php echo __('register_subtitle'); ?></p>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label><?php echo __('username'); ?></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control" placeholder="Enter Username" value="<?php echo $username; ?>" required>
                </div>
                <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $username_err; ?></span>
            </div>    
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
            <div class="form-group">
                <label><?php echo __('confirm_password'); ?></label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="8 characters minimum" required>
                    <i class="fas fa-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                </div>
                <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $confirm_password_err; ?></span>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo __('register'); ?></button>
            <div class="auth-footer">
                <?php echo __('have_account'); ?> <a href="login.php"><?php echo __('login_here'); ?></a>
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
