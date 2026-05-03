<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Only Admin can manage templates
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

include_once 'includes/header.php';

// Fetch all templates
$sql = "SELECT qt.*, (SELECT COUNT(*) FROM question WHERE template_id = qt.template_id) as question_count 
        FROM questionnaire_template qt 
        ORDER BY qt.created_at DESC";
$result = $conn->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h2 class="fw-bold mb-0 text-primary">Questionnaire Management</h2>
        <p class="text-muted">Create and manage bi-daily health screening templates.</p>
    </div>
    <a href="add_questionnaire.php" class="btn btn-premium">
        <i class="fa-solid fa-plus me-1"></i> Create New Template
    </a>
</div>

<div class="row fade-in-up">
    <div class="col-12">
        <div class="card glass-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-premium table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Template Name</th>
                                <th>Version</th>
                                <th>Questions</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="pe-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['template_name']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['version_no']) ?></td>
                                        <td><span class="badge bg-info"><?= $row['question_count'] ?> Questions</span></td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] == 'Active' ? 'success' : 'secondary' ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        <td class="pe-3 text-end">
                                            <a href="view_questionnaire.php?id=<?= $row['template_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">No questionnaire templates found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
