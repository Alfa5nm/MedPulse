<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($template_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Template ID.</div>";
    include_once 'includes/footer.php';
    exit;
}

// Fetch Template Info
$sql = "SELECT * FROM questionnaire_template WHERE template_id = $template_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Template not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$template = $result->fetch_assoc();

// Fetch Questions
$qSql = "SELECT * FROM question WHERE template_id = $template_id ORDER BY display_order ASC";
$qResult = $conn->query($qSql);
?>

<div class="row align-items-center mb-4 fade-in-up">
    <div class="col-md-8">
        <a href="questionnaire.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Templates</a>
        <h2 class="fw-bold mb-0 text-primary"><?= htmlspecialchars($template['template_name']) ?> <span class="badge bg-light text-dark fs-6 border align-middle ms-2">v<?= htmlspecialchars($template['version_no']) ?></span></h2>
        <p class="text-muted mt-1">Status: <span class="badge <?= $template['status'] == 'Active' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($template['status']) ?></span> | Created: <?= date('M d, Y', strtotime($template['created_at'])) ?></p>
    </div>
</div>

<div class="card glass-card fade-in-up" style="animation-delay: 0.1s;">
    <div class="card-header bg-transparent py-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-ol text-info me-2"></i> Questions List</h5>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            <?php if($qResult && $qResult->num_rows > 0): ?>
                <?php while($q = $qResult->fetch_assoc()): ?>
                    <li class="list-group-item bg-transparent py-3 d-flex align-items-start">
                        <div class="fw-bold text-muted me-3">#<?= $q['display_order'] ?></div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 text-dark"><?= htmlspecialchars($q['question_text']) ?></h6>
                            <span class="badge bg-light text-secondary border mt-1"><i class="fa-solid fa-tag me-1"></i> Type: <?= htmlspecialchars($q['question_type']) ?></span>
                        </div>
                    </li>
                <?php endwhile; ?>
            <?php else: ?>
                <li class="list-group-item bg-transparent text-center text-muted py-5">
                    No questions defined for this template.
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
