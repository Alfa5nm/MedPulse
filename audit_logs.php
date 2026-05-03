<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isAdmin()) {
    if (isPatient()) {
        header("Location: my_health.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

include_once 'includes/header.php';

$sql = "SELECT a.*, u.username, u.role 
        FROM audit_log a 
        LEFT JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.created_at DESC LIMIT 100";
$result = $conn->query($sql);
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-shield-halved me-2"></i> System Audit Trail</h2>
        <p class="text-muted">Forensic record of all clinician and patient actions.</p>
    </div>
</div>

<div class="card glass-card fade-in-up">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-premium table-hover mb-0 text-sm">
                <thead>
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>Details</th>
                        <th class="pe-4">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($log = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= date('M d, H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($log['username'] ?? 'System') ?></span>
                                    <br><small class="badge bg-light text-dark border"><?= $log['role'] ?? 'N/A' ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $action = $log['action_type'];
                                    $class = 'bg-primary';
                                    if($action == 'Delete') $class = 'bg-danger';
                                    if($action == 'Login') $class = 'bg-success';
                                    if($action == 'Edit') $class = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $class ?>"><?= $action ?></span>
                                </td>
                                <td><span class="text-uppercase fw-medium small"><?= $log['target_entity'] ?></span> #<?= $log['target_id'] ?></td>
                                <td class="text-muted" style="max-width: 300px;"><?= htmlspecialchars($log['details']) ?></td>
                                <td class="pe-4 small font-monospace"><?= $log['ip_address'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No audit logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
