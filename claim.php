<?php
require_once 'includes/config.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : 0;
$message = "";
$message_err = "";

// Fetch item details to verify
$sql = "SELECT i.*, u.username FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $item_id]);
$item = $stmt->fetch();

if(!$item || $item['user_id'] == $_SESSION['id']){
    header("location: index.php");
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(empty(trim($_POST["message"]))){
        $message_err = "Please enter a message for the owner.";
    } else {
        $message = trim($_POST["message"]);
    }

    if(empty($message_err)){
        $sql = "INSERT INTO claims (item_id, claimer_id, message) VALUES (:item_id, :claimer_id, :message)";
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":item_id", $item_id);
            $stmt->bindParam(":claimer_id", $_SESSION["id"]);
            $stmt->bindParam(":message", $message);
            
            if($stmt->execute()){
                // Update item status to pending
                $update_sql = "UPDATE items SET status = 'pending' WHERE id = :id";
                $update_stmt = $pdo->prepare($update_sql);
                $update_stmt->execute(['id' => $item_id]);

                // Create a notification for the item owner
                $owner_id = $item['user_id'];
                $item_title = $item['title'];
                $claimer_username = $_SESSION['username'];
                createNotification($pdo, $owner_id, "User '$claimer_username' has submitted a claim for your item '$item_title'.");

                header("location: dashboard.php?claim=success");
            } else {
                echo "Something went wrong. Please try again later.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container animate-fade mt-50">
    <div class="form-container" style="max-width: 600px;">
        <h2 class="text-center"><?php echo __('claim_item_title'); ?></h2>
        <p class="text-center" style="color: var(--gray); margin-bottom: 30px;"><?php echo __('claiming_desc'); ?> <strong><?php echo $item['title']; ?></strong></p>
        
        <div style="background: var(--light); padding: 15px; border-radius: 15px; margin-bottom: 20px; font-size: 0.9rem;">
            <p><strong><?php echo __('posted_by'); ?></strong> <?php echo $item['username']; ?></p>
            <p><strong><?php echo __('found_at'); ?></strong> <?php echo $item['location']; ?></p>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?item_id=" . $item_id; ?>" method="post">
            <div class="form-group">
                <label><?php echo __('proof_ownership_msg'); ?></label>
                <textarea name="message" class="form-control" rows="5" placeholder="Describe unique features, contents, or where you lost it to prove it's yours..." required></textarea>
                <span style="color: var(--secondary); font-size: 0.8rem;"><?php echo $message_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" style="width: 100%;" value="Submit Claim Request">
            </div>
            <a href="item-details.php?id=<?php echo $item_id; ?>" style="display: block; text-align: center; color: var(--gray); text-decoration: none;"><?php echo __('cancel'); ?></a>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
