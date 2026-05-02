<?php
require_once 'config/db.php';
include_once 'includes/header.php';

// Fetch all questionnaire templates
$sql = "SELECT qt.*, 
        (SELECT COUNT(*) FROM question q WHERE q.template_id = qt.template_id) as question_count,
        (SELECT COUNT(DISTINCT r.patient_id) FROM response r JOIN question q ON r.question_id = q.question_id WHERE q.template_id = qt.template_id) as patient_count
        FROM questionnaire_template qt
        ORDER BY qt.created_at DESC";
$result = $conn->query($sql);
?>

<div class="row align-items-center mb-4 fade-in-up">
    <div class="col-md-6 mb-3 mb-md-0">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-clipboard-question text-info me-2"></i> Questionnaire Templates</h2>
        <p class="text-muted">Manage dynamic survey templates for patient health monitoring.</p>
    </div>
</div>

<div class="card glass-card fade-in-up" style="animation-delay: 0.1s;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-premium table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Template Name</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Questions</th>
                        <th>Patients Responded</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($qt = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><i class="fa-solid fa-file-lines text-secondary me-2"></i> <?= htmlspecialchars($qt['template_name']) ?></td>
                                <td>v<?= htmlspecialchars($qt['version_no']) ?></td>
                                <td>
                                    <span class="badge <?= $qt['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($qt['status']) ?></span>
                                </td>
                                <td><?= $qt['question_count'] ?></td>
                                <td><?= $qt['patient_count'] ?></td>
                                <td class="pe-4 text-end">
                                    <a href="view_questionnaire.php?id=<?= $qt['template_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i> View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-clipboard fs-1 mb-3 opacity-50"></i><br>
                                No questionnaire templates found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
