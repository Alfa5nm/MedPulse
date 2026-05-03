<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;
$error = '';
$success = '';

if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Patient ID.</div>";
    include_once 'includes/footer.php';
    exit;
}


$patientSql = "SELECT full_name FROM patient WHERE patient_id = $patient_id";
$patientResult = $conn->query($patientSql);
if ($patientResult->num_rows == 0) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$patient = $patientResult->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $template_id > 0) {
    $responses = $_POST['responses'] ?? [];
    
    if (!empty($responses)) {
        
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO response (patient_id, question_id, response_value) VALUES (?, ?, ?)");
            foreach ($responses as $question_id => $answer) {
                $q_id = intval($question_id);
                $val = is_array($answer) ? implode(', ', $answer) : $answer;
                $stmt->bind_param("iis", $patient_id, $q_id, $val);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
            header("Location: patient_details.php?id=$patient_id&success=questionnaire_completed");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error saving responses: " . $e->getMessage();
        }
    } else {
        $error = "No responses provided.";
    }
}


$templatesSql = "SELECT * FROM questionnaire_template WHERE status = 'Active' ORDER BY template_name ASC";
$templatesResult = $conn->query($templatesSql);


$questions = [];
$template_name = '';
if ($template_id > 0) {
    $tSql = "SELECT template_name FROM questionnaire_template WHERE template_id = $template_id";
    $tRes = $conn->query($tSql);
    if ($tRes && $tRes->num_rows > 0) {
        $template_name = $tRes->fetch_assoc()['template_name'];
        
        $qSql = "SELECT * FROM question WHERE template_id = $template_id ORDER BY display_order ASC";
        $qRes = $conn->query($qSql);
        while ($q = $qRes->fetch_assoc()) {
            $questions[] = $q;
        }
    } else {
        $error = "Invalid template selected.";
        $template_id = 0;
    }
}
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <h2 class="fw-bold mb-0">Patient Questionnaire</h2>
        <p class="text-muted">Collecting responses for <span class="fw-bold text-dark"><?= htmlspecialchars($patient['full_name']) ?></span></p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?></div>
<?php endif; ?>

<div class="row fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8 mx-auto">
        
        <?php if ($template_id == 0): ?>
            
            <div class="card glass-card">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-4">Select a Questionnaire Template</h5>
                    <form method="GET" action="take_questionnaire.php" class="d-flex justify-content-center">
                        <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                        <div class="input-group w-75">
                            <select name="template_id" class="form-select" required>
                                <option value="">-- Select Template --</option>
                                <?php if($templatesResult && $templatesResult->num_rows > 0): ?>
                                    <?php while($t = $templatesResult->fetch_assoc()): ?>
                                        <option value="<?= $t['template_id'] ?>"><?= htmlspecialchars($t['template_name']) ?> (v<?= $t['version_no'] ?>)</option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <button class="btn btn-premium" type="submit">Start</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            
            <div class="card glass-card border-top border-4 border-info">
                <div class="card-header bg-transparent py-3">
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($template_name) ?></h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($questions)): ?>
                        <div class="alert alert-warning">This template has no questions.</div>
                    <?php else: ?>
                        <form method="POST" action="take_questionnaire.php?patient_id=<?= $patient_id ?>&template_id=<?= $template_id ?>">
                            
                            <?php foreach ($questions as $index => $q): ?>
                                <div class="mb-4">
                                    <label class="form-label fw-bold"><?= ($index+1) . ". " . htmlspecialchars($q['question_text']) ?></label>
                                    
                                    <?php if ($q['question_type'] == 'YesNo'): ?>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="responses[<?= $q['question_id'] ?>]" value="Yes" required>
                                                <label class="form-check-label">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="responses[<?= $q['question_id'] ?>]" value="No" required>
                                                <label class="form-check-label">No</label>
                                            </div>
                                        </div>
                                    <?php elseif ($q['question_type'] == 'Numeric'): ?>
                                        <input type="number" step="0.1" name="responses[<?= $q['question_id'] ?>]" class="form-control" style="max-width: 200px;" required>
                                    <?php else: ?>
                                        <input type="text" name="responses[<?= $q['question_id'] ?>]" class="form-control" required>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <div class="text-end mt-4 pt-3 border-top">
                                <a href="take_questionnaire.php?patient_id=<?= $patient_id ?>" class="btn btn-light me-2">Cancel</a>
                                <button type="submit" class="btn btn-premium px-4 shadow-sm"><i class="fa-solid fa-check me-2"></i> Submit Responses</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
