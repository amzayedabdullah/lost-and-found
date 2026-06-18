<?php include 'includes/admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? $_GET['id'] : 0;

$sql = "SELECT i.*, u.username, u.email FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);
$item = $stmt->fetch();

if(!$item){
    echo "<div class='page-header'><h1>Item Not Found</h1></div>";
    include 'includes/admin_footer.php';
    exit;
}

$typeBg = $item['type'] == 'lost' ? '#fee2e2' : '#dcfce7';
$typeColor = $item['type'] == 'lost' ? '#ef4444' : '#10b981';
$statusBg = $item['status'] == 'resolved' ? '#dcfce7' : ($item['status'] == 'pending' ? '#fef9c3' : '#f1f5f9');
$statusColor = $item['status'] == 'resolved' ? '#10b981' : ($item['status'] == 'pending' ? '#a16207' : '#64748b');
?>

<div class="page-header" style="margin-bottom: 20px;">
    <a href="javascript:history.back()" style="color: #64748b; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px;">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
        <span class="role-tag" style="background: <?php echo $typeBg; ?>; color: <?php echo $typeColor; ?>; font-size: 0.8rem; padding: 6px 15px;">
            <?php echo strtoupper($item['type']); ?>
        </span>
        <span class="role-tag" style="background: <?php echo $statusBg; ?>; color: <?php echo $statusColor; ?>; font-size: 0.8rem; padding: 6px 15px;">
            <?php echo strtoupper($item['status']); ?>
        </span>
    </div>
    <h1 style="font-size: 2.5rem; color: #1e293b; margin-bottom: 10px;"><?php echo htmlspecialchars($item['title']); ?></h1>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <div>
        <!-- Image Area: Admin always sees unblurred image -->
        <div style="background: white; border-radius: 24px; padding: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 30px;">
            <?php if(!empty($item['image_path'])): ?>
                <img src="../<?php echo $item['image_path']; ?>" style="width: 100%; height: auto; max-height: 500px; object-fit: cover; border-radius: 16px;">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: #f1f5f9; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <i class="fas fa-image" style="font-size: 4rem;"></i>
                </div>
            <?php endif; ?>
        </div>

        <div style="background: white; border-radius: 24px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h3 style="font-size: 1.3rem; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">Description</h3>
            <p style="color: #475569; line-height: 1.8; font-size: 1.05rem; white-space: pre-wrap;"><?php echo htmlspecialchars($item['description']); ?></p>
        </div>
    </div>

    <div>
        <div style="background: white; border-radius: 24px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); position: sticky; top: 100px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 25px;">Item Details</h3>
            
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div>
                    <p style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Category</p>
                    <p style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-tag" style="color: var(--primary);"></i> <?php echo htmlspecialchars($item['category']); ?>
                    </p>
                </div>
                <div>
                    <p style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Location Spotted</p>
                    <p style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> <?php echo htmlspecialchars($item['location']); ?>
                    </p>
                </div>
                <div>
                    <p style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Date Spotted</p>
                    <p style="font-weight: 600; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                        <i class="far fa-calendar-alt" style="color: #3b82f6;"></i> <?php echo date("F j, Y", strtotime($item['date_spotted'])); ?>
                    </p>
                </div>
                <?php if(!empty($item['keywords'])): ?>
                <div>
                    <p style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">System Keywords</p>
                    <p style="font-size: 0.9rem; color: #64748b;"><?php echo htmlspecialchars($item['keywords']); ?></p>
                </div>
                <?php endif; ?>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 10px 0;">
                <div>
                    <p style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 5px;">Reported By</p>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 10px;">
                        <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <p style="font-weight: 700; color: #1e293b; margin: 0;"><?php echo htmlspecialchars($item['username']); ?></p>
                            <p style="font-size: 0.8rem; color: #64748b; margin: 0;"><?php echo htmlspecialchars($item['email']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
