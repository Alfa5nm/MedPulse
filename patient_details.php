<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Patient ID.</div>";
    include_once 'includes/footer.php';
    exit;
}


$user_id = $_SESSION['user_id'] ?? null;
$action = 'View';
$entity = 'Patient';
$ip = $_SERVER['REMOTE_ADDR'];
$details = "Full clinical record access for Patient ID: $patient_id";

$auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, target_entity, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
$auditStmt->bind_param("ississ", $user_id, $action, $entity, $patient_id, $details, $ip);
$auditStmt->execute();
$auditStmt->close();


$sql = "SELECT p.*, CONCAT_WS(' > ', divi.region_name, d.region_name, r.region_name) as full_region_name
        FROM patient p 
        LEFT JOIN region r ON p.region_id = r.region_id
        LEFT JOIN region d ON r.parent_region_id = d.region_id
        LEFT JOIN region divi ON d.parent_region_id = divi.region_id
        WHERE p.patient_id = $patient_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$patient = $result->fetch_assoc();


$scoreSql = "SELECT * FROM healthscore WHERE patient_id = $patient_id ORDER BY score_datetime DESC LIMIT 1";
$scoreResult = $conn->query($scoreSql);
$latestScore = $scoreResult->fetch_assoc();


$obsSql = "SELECT o.*, l.test_name, l.unit_name 
           FROM observation o 
           JOIN loinc_code l ON o.loinc_code_id = l.loinc_code_id 
           WHERE o.patient_id = $patient_id 
           ORDER BY o.observation_datetime DESC LIMIT 10";
$obsResult = $conn->query($obsSql);


$diagSql = "SELECT d.*, i.disease_name, i.icd_code 
            FROM diagnosis d 
            JOIN icd_code i ON d.icd_code_id = i.icd_code_id 
            WHERE d.patient_id = $patient_id 
            ORDER BY d.diagnosis_date DESC";
$diagResult = $conn->query($diagSql);


$rxSql = "SELECT p.*, m.medication_name 
          FROM prescription p 
          JOIN medication_code m ON p.medication_code_id = m.medication_code_id 
          WHERE p.patient_id = $patient_id 
          ORDER BY p.prescribed_date DESC LIMIT 10";
$rxResult = $conn->query($rxSql);


$respSql = "SELECT r.*, q.question_text, qt.template_name 
            FROM response r 
            JOIN question q ON r.question_id = q.question_id 
            JOIN questionnaire_template qt ON q.template_id = qt.template_id 
            WHERE r.patient_id = $patient_id 
            ORDER BY r.response_datetime DESC LIMIT 20";
$respResult = $conn->query($respSql);


$intakeSql = "SELECT i.*, m.medication_name 
              FROM intake_log i 
              JOIN prescription p ON i.prescription_id = p.prescription_id 
              JOIN medication_code m ON p.medication_code_id = m.medication_code_id 
              WHERE p.patient_id = $patient_id 
              ORDER BY i.intake_datetime DESC LIMIT 15";
$intakeResult = $conn->query($intakeSql);


$userSql = "SELECT user_id, username FROM users WHERE patient_id = $patient_id";
$userRes = $conn->query($userSql);
$hasAccount = ($userRes && $userRes->num_rows > 0);
$userData = $hasAccount ? $userRes->fetch_assoc() : null;

function getBadgeClass($level) {
    switch(strtolower($level)) {
        case 'critical': return 'badge-severe';
        case 'high': return 'badge-high';
        case 'medium': return 'badge-medium';
        default: return 'badge-low';
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <a href="patients.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Patients</a>
        <h2 class="fw-bold mb-0"><?= htmlspecialchars($patient['full_name']) ?>'s Profile</h2>
    </div>
    <div class="d-flex gap-2">
        <a href="add_observation.php?patient_id=<?= $patient_id ?>" class="btn btn-premium shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Add Vitals
        </a>
        <a href="add_diagnosis.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-danger shadow-sm bg-white">
            <i class="fa-solid fa-stethoscope me-1"></i> Add Diagnosis
        </a>
        <a href="add_prescription.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-info shadow-sm bg-white">
            <i class="fa-solid fa-file-prescription me-1"></i> Add Prescription
        </a>
        <a href="take_questionnaire.php?patient_id=<?= $patient_id ?>" class="btn btn-outline-secondary shadow-sm bg-white">
            <i class="fa-solid fa-clipboard-list me-1"></i> Questionnaire
        </a>
        <a href="export_patient.php?id=<?= $patient_id ?>" class="btn btn-outline-dark shadow-sm bg-white">
            <i class="fa-solid fa-file-export me-1"></i> Export Record
        </a>
    </div>
</div>

<div class="row g-4 fade-in-up" style="animation-delay: 0.1s;">
    <!-- Personal Info Card -->
    <div class="col-lg-4">
        <div class="card glass-card h-100 border-top border-4 border-info">
            <div class="card-body">
                <div class="text-center mb-4 mt-2">
                    <div class="bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 2.5rem;">
                        <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                    </div>
                </div>
                <h5 class="card-title fw-bold text-center border-bottom pb-3 mb-3">Patient Information</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2 d-flex justify-content-between"><span class="text-muted">DOB:</span> <span class="fw-medium"><?= date('M d, Y', strtotime($patient['date_of_birth'])) ?></span></li>
                    <li class="mb-2 d-flex justify-content-between"><span class="text-muted">Gender:</span> <span class="fw-medium"><?= htmlspecialchars($patient['gender']) ?></span></li>
                    <li class="mb-2 d-flex justify-content-between"><span class="text-muted">Blood Group:</span> <span class="badge bg-danger"><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span></li>
                    <li class="mb-2 d-flex justify-content-between"><span class="text-muted">Phone:</span> <span class="fw-medium"><?= htmlspecialchars($patient['phone']) ?></span></li>
                    <li class="mb-3 d-flex justify-content-between"><span class="text-muted">Region:</span> <span class="fw-medium text-end"><i class="fa-solid fa-map-marker-alt text-danger"></i> <span style="font-size: 0.85rem;"><?= htmlspecialchars($patient['full_region_name']) ?></span></span></li>
                    
                    <?php if(!$hasAccount): ?>
                        <li class="mb-2 pt-3 border-top">
                            <div class="alert bg-light border p-2 small mb-2">
                                <i class="fa-solid fa-info-circle text-info me-1"></i> No portal account linked.
                            </div>
                            <form action="provision_patient.php" method="POST">
                                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">
                                <input type="hidden" name="email" value="<?= strtolower(str_replace(' ', '.', $patient['full_name'])) ?>@medpulse.com">
                                <button type="submit" class="btn btn-sm btn-info text-white w-100 shadow-sm"><i class="fa-solid fa-user-shield me-1"></i> Provision Patient Portal</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="mb-2 pt-3 border-top small text-muted">
                            <i class="fa-solid fa-check-circle text-success me-1"></i> Portal Account: <strong><?= htmlspecialchars($userData['username']) ?></strong>
                        </li>
                    <?php endif; ?>

                    <li class="pt-2 border-top d-flex gap-2">
                        <a href="edit_patient.php?id=<?= $patient_id ?>" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="fa-solid fa-user-edit me-1"></i> Edit Profile</a>
                        <?php if(isAdmin()): ?>
                            <a href="delete_record.php?type=patient&id=<?= $patient_id ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('CRITICAL: Are you sure you want to delete this patient and ALL their medical history? This cannot be undone.');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Latest Health Score Card -->
    <div class="col-lg-8">
        <div class="card glass-card h-100">
            <div class="card-header border-0 bg-transparent pt-4 pb-0">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-heart-pulse text-danger me-2"></i> Current Health Risk Status (NEWS2 based)</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if($latestScore): ?>
                    <div class="text-center w-100">
                        <div class="display-1 fw-bold mb-2 <?= strtolower($latestScore['risk_level']) == 'critical' ? 'text-danger' : 
                            (strtolower($latestScore['risk_level']) == 'high' ? 'text-warning' : 
                            (strtolower($latestScore['risk_level']) == 'medium' ? 'text-info' : 'text-success')) ?>">
                            <?= $latestScore['total_score'] ?>
                        </div>
                        <h4 class="mb-4">Risk Level: <span class="badge <?= getBadgeClass($latestScore['risk_level']) ?> px-3 py-2 fs-5 shadow-sm"><?= htmlspecialchars($latestScore['risk_level']) ?></span></h4>
                        <p class="text-muted mb-0">Assessed on <?= date('D, M d, Y - h:i A', strtotime($latestScore['score_datetime'])) ?></p>
                    </div>
                <?php else: ?>
                    <div class="text-center w-100 py-5">
                        <div class="text-muted mb-3"><i class="fa-solid fa-clipboard-question fs-1 opacity-50"></i></div>
                        <h5 class="text-muted">No Risk Score Calculated Yet</h5>
                        <p class="small text-muted mb-0">Add recent vitals to compute the initial NEWS2 score.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 fade-in-up" style="animation-delay: 0.2s;">
    <!-- Recent Observations -->
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                <h6 class="fw-bold mb-0 text-primary">Recent Vital Observations</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-3">Date & Time</th>
                                <th>Test Name</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($obsResult && $obsResult->num_rows > 0): ?>
                                <?php while($obs = $obsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?= date('M d h:i a', strtotime($obs['observation_datetime'])) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($obs['test_name']) ?></td>
                                        <td>
                                            <span class="fw-bold"><?= $obs['observation_value'] ?></span> 
                                            <span class="text-muted small"><?= htmlspecialchars($obs['unit_name'] ?: $obs['unit']) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-3 text-muted">No vital records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Diagnosis History -->
    <div class="col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary">Diagnosis History</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if($diagResult && $diagResult->num_rows > 0): ?>
                        <?php while($diag = $diagResult->fetch_assoc()): ?>
                            <li class="list-group-item bg-transparent border-bottom-0 border-top py-3">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <h6 class="mb-0 fw-bold border-start border-3 border-danger ps-2"><?= htmlspecialchars($diag['disease_name']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($diag['diagnosis_date'])) ?></small>
                                </div>
                                <div class="ps-3 mt-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-light text-dark border me-2">ICD: <?= htmlspecialchars($diag['icd_code']) ?></span>
                                        <?php if($diag['diagnosis_notes']): ?>
                                            <p class="mb-0 small text-muted mt-2"><i class="fa-solid fa-note-sticky text-info"></i> <?= htmlspecialchars($diag['diagnosis_notes']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <a href="delete_record.php?type=diagnosis&id=<?= $diag['diagnosis_id'] ?>&patient_id=<?= $patient_id ?>" class="text-danger opacity-50 hover-opacity-100" onclick="return confirm('Delete this diagnosis?');">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item bg-transparent text-center text-muted py-4 border-0">No past diagnoses recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 fade-in-up" style="animation-delay: 0.3s;">
    <!-- Prescriptions -->
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-pills me-2"></i>Recent Prescriptions</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 300px;">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Medication</th>
                                <th class="pe-3">Dosage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($rxResult && $rxResult->num_rows > 0): ?>
                                <?php while($rx = $rxResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?= date('M d, Y', strtotime($rx['prescribed_date'])) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($rx['medication_name']) ?></td>
                                        <td class="pe-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?= htmlspecialchars($rx['dosage']) ?> <br><span class="badge bg-light text-dark border"><?= htmlspecialchars($rx['frequency']) ?></span>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <a href="record_intake.php?prescription_id=<?= $rx['prescription_id'] ?>" class="btn btn-sm btn-success rounded-circle shadow-sm" title="Log Intake">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </a>
                                                    <a href="delete_record.php?type=prescription&id=<?= $rx['prescription_id'] ?>&patient_id=<?= $patient_id ?>" class="btn btn-sm btn-outline-danger rounded-circle border-0" onclick="return confirm('Cancel/Delete this prescription?');">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No prescriptions found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Questionnaire Responses -->
    <div class="col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-clipboard-question me-2"></i>Questionnaire Responses</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php if($respResult && $respResult->num_rows > 0): ?>
                        <?php while($resp = $respResult->fetch_assoc()): ?>
                            <li class="list-group-item bg-transparent py-3">
                                <div class="d-flex w-100 justify-content-between mb-1">
                                    <small class="text-info fw-bold"><?= htmlspecialchars($resp['template_name']) ?></small>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($resp['response_datetime'])) ?></small>
                                </div>
                                <p class="mb-1 text-dark fw-medium small"><?= htmlspecialchars($resp['question_text']) ?></p>
                                <span class="badge bg-secondary text-white">Ans: <?= htmlspecialchars($resp['response_value']) ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item bg-transparent text-center text-muted py-4 border-0">No questionnaire responses recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 fade-in-up" style="animation-delay: 0.4s;">
    <!-- Intake History -->
    <div class="col-lg-12">
        <div class="card glass-card">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-clock-rotate-left me-2"></i>Medication Intake History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 250px;">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-3">Datetime</th>
                                <th>Medication</th>
                                <th>Status</th>
                                <th class="pe-3">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($intakeResult && $intakeResult->num_rows > 0): ?>
                                <?php while($log = $intakeResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?= date('M d, h:i A', strtotime($log['intake_datetime'])) ?></td>
                                        <td class="fw-medium text-dark"><?= htmlspecialchars($log['medication_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $log['intake_status'] == 'Taken' ? 'bg-success' : ($log['intake_status'] == 'Missed' ? 'bg-danger' : 'bg-warning') ?>">
                                                <?= htmlspecialchars($log['intake_status']) ?>
                                            </span>
                                        </td>
                                        <td class="pe-3 small text-muted italic"><?= htmlspecialchars($log['remarks'] ?: '-') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No intake records found. Click the <i class="fa-solid fa-plus text-success"></i> in the prescription list to log intake.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
