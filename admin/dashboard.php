<?php include 'includes/admin_header.php'; ?>

<?php
// Fetch Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$total_items = $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();
$resolved_items = $pdo->query("SELECT COUNT(*) FROM items WHERE status = 'resolved'")->fetchColumn();
$total_claims = $pdo->query("SELECT COUNT(*) FROM claims")->fetchColumn();
$pending_claims = $pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'pending'")->fetchColumn();
$pending_matches = $pdo->query("SELECT COUNT(*) FROM system_matches WHERE status = 'pending'")->fetchColumn();

$success_rate = $total_items > 0 ? round(($resolved_items / $total_items) * 100) : 0;

// Data for Charts
$type_dist = $pdo->query("SELECT type, COUNT(*) as count FROM items GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);
$status_dist = $pdo->query("SELECT status, COUNT(*) as count FROM items GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$claim_dist = $pdo->query("SELECT status, COUNT(*) as count FROM claims GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$cat_dist = $pdo->query("SELECT category, COUNT(*) as count FROM items GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <h1><?php echo __('admin_panel_title'); ?></h1>
    <p><?php echo __('admin_panel_desc'); ?></p>
</div>

<div class="admin-stats-grid">
    <div class="stat-box">
        <div class="stat-box-top">
            <h4><?php echo __('total_users'); ?></h4>
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-box-value">
            <h2><?php echo $total_users; ?></h2>
            <p><?php echo $total_admins; ?> admins</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-top">
            <h4><?php echo __('total_items'); ?></h4>
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-box-value">
            <h2><?php echo $total_items; ?></h2>
            <p><?php echo $resolved_items; ?> Resolved</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-top">
            <h4><?php echo __('total_claims'); ?></h4>
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-box-value">
            <h2><?php echo $total_claims; ?></h2>
            <p><?php echo $pending_claims; ?> Pending</p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-top">
            <h4><?php echo __('system_matches'); ?></h4>
            <i class="fas fa-magic"></i>
        </div>
        <div class="stat-box-value">
            <h2><?php echo $pending_matches; ?></h2>
            <p><?php echo __('pending_verification'); ?></p>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-top">
            <h4><?php echo __('success_rate'); ?></h4>
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-box-value">
            <h2><?php echo $success_rate; ?>%</h2>
            <p><?php echo $resolved_items; ?> / <?php echo $total_items; ?> items</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <div class="stat-box">
        <h4 style="margin-bottom: 20px;"><?php echo __('item_type_dist'); ?></h4>
        <div style="height: 300px; display: flex; align-items: center; justify-content: center;">
            <canvas id="typeChart"></canvas>
        </div>
    </div>
    <div class="stat-box">
        <h4 style="margin-bottom: 20px;"><?php echo __('item_status_dist'); ?></h4>
        <div style="height: 300px;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <div class="stat-box">
        <h4 style="margin-bottom: 20px;"><?php echo __('claim_status_dist'); ?></h4>
        <div style="height: 300px;">
            <canvas id="claimChart"></canvas>
        </div>
    </div>
    <div class="stat-box">
        <h4 style="margin-bottom: 20px;"><?php echo __('category_dist'); ?></h4>
        <div style="height: 300px;">
            <canvas id="catChart"></canvas>
        </div>
    </div>
</div>

<div class="stat-box">
    <h4><?php echo __('recent_activity'); ?></h4>
    <div style="padding: 40px; text-align: center; color: #94a3b8;">
        No recent activity found.
    </div>
</div>

<script>
    // Item Type Chart
    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: ['Lost Items', 'Found Items'],
            datasets: [{
                data: [<?php echo $type_dist['lost'] ?? 0; ?>, <?php echo $type_dist['found'] ?? 0; ?>],
                backgroundColor: ['#10b981', '#3b82f6'],
                borderWidth: 0
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: ['Open', 'Pending', 'Resolved'],
            datasets: [{
                label: 'Items',
                data: [<?php echo $status_dist['open'] ?? 0; ?>, <?php echo $status_dist['pending'] ?? 0; ?>, <?php echo $status_dist['resolved'] ?? 0; ?>],
                backgroundColor: '#3b82f6',
                borderRadius: 8
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Claim Chart
    new Chart(document.getElementById('claimChart'), {
        type: 'line',
        data: {
            labels: ['Pending', 'Accepted', 'Rejected'],
            datasets: [{
                label: 'Claims',
                data: [<?php echo $claim_dist['pending'] ?? 0; ?>, <?php echo $claim_dist['accepted'] ?? 0; ?>, <?php echo $claim_dist['rejected'] ?? 0; ?>],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    // Category Chart
    new Chart(document.getElementById('catChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($cat_dist)); ?>,
            datasets: [{
                label: 'Items',
                data: <?php echo json_encode(array_values($cat_dist)); ?>,
                backgroundColor: '#f59e0b',
                borderRadius: 8
            }]
        },
        options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>

<?php include 'includes/admin_footer.php'; ?>
