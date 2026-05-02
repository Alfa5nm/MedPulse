<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// If not a patient, redirect to clinician dashboard
if ($_SESSION['role'] !== 'Patient') {
    header("Location: dashboard.php");
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Fetch Patient Info
$sql = "SELECT p.*, CONCAT_WS(' > ', divi.region_name, d.region_name, r.region_name) as full_region_name
        FROM patient p 
        LEFT JOIN region r ON p.region_id = r.region_id
        LEFT JOIN region d ON r.parent_region_id = d.region_id
        LEFT JOIN region divi ON d.parent_region_id = divi.region_id
        WHERE p.patient_id = $patient_id";
$result = $conn->query($sql);
$patient = $result->fetch_assoc();

// Fetch Latest Health Score
$scoreSql = "SELECT * FROM healthscore WHERE patient_id = $patient_id ORDER BY score_datetime DESC LIMIT 1";
$scoreResult = $conn->query($scoreSql);
$latestScore = $scoreResult->fetch_assoc();

// Fetch Diagnosis History
$diagSql = "SELECT d.*, i.disease_name, i.icd_code 
            FROM diagnosis d 
            JOIN icd_code i ON d.icd_code_id = i.icd_code_id 
            WHERE d.patient_id = $patient_id 
            ORDER BY d.diagnosis_date DESC";
$diagResult = $conn->query($diagSql);

// Fetch Active Prescriptions & Calculate Adherence
$rxSql = "SELECT p.*, m.medication_name,
          (SELECT COUNT(*) FROM intake_log WHERE prescription_id = p.prescription_id AND intake_status = 'Taken') as doses_taken,
          DATEDIFF(CURDATE(), p.prescribed_date) + 1 as days_since_start
          FROM prescription p 
          JOIN medication_code m ON p.medication_code_id = m.medication_code_id 
          WHERE p.patient_id = $patient_id 
          ORDER BY p.prescribed_date DESC";
$rxResult = $conn->query($rxSql);

include_once 'includes/header.php';
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-0 text-primary">My Health Portal</h2>
        <p class="text-muted">Welcome back, <span class="fw-bold text-dark"><?= htmlspecialchars($patient['full_name']) ?></span>. Here is your current health overview.</p>
    </div>
</div>

<div class="row g-4 mb-4 fade-in-up">
    <!-- Risk Status -->
    <div class="col-lg-4">
        <div class="card glass-card h-100 border-top border-4 <?= $latestScore && strtolower($latestScore['risk_level']) == 'critical' ? 'border-danger' : 'border-success' ?>">
            <div class="card-body text-center p-4">
                <h6 class="text-muted fw-bold text-uppercase small mb-3">Your Current Status</h6>
                <?php if($latestScore): ?>
                    <div class="display-3 fw-bold mb-2 <?= $latestScore['total_score'] >= 5 ? 'text-danger' : 'text-success' ?>">
                        <?= $latestScore['total_score'] ?>
                    </div>
                    <p class="mb-1 fw-bold fs-5">Risk Level: <?= htmlspecialchars($latestScore['risk_level']) ?></p>
                    <p class="text-muted small">Updated: <?= date('M d, Y', strtotime($latestScore['score_datetime'])) ?></p>
                <?php else: ?>
                    <div class="py-4">
                        <i class="fa-solid fa-heart-circle-check text-muted fs-1 mb-3 opacity-50"></i>
                        <p class="text-muted">No assessment data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Personal Info -->
    <div class="col-lg-8">
        <div class="card glass-card h-100">
            <div class="card-body">
                <div class="row h-100 align-items-center">
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 3rem;">
                            <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h4 class="fw-bold mb-3"><?= htmlspecialchars($patient['full_name']) ?></h4>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Phone</small>
                                <span class="fw-medium"><?= htmlspecialchars($patient['phone']) ?></span>
                            </div>
                            <div class="col-sm-6">
                                <small class="text-muted d-block">Blood Group</small>
                                <span class="badge bg-danger"><?= htmlspecialchars($patient['blood_group'] ?? 'Unknown') ?></span>
                            </div>
                            <div class="col-12">
                                <small class="text-muted d-block">Registered Region</small>
                                <span class="fw-medium small"><i class="fa-solid fa-location-dot text-danger"></i> <?= htmlspecialchars($patient['full_region_name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 fade-in-up" style="animation-delay: 0.2s;">
    <!-- Treatment & Adherence -->
    <div class="col-lg-7">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-capsules me-2"></i>Treatment & Adherence</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-premium table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Medication</th>
                                <th>Schedule</th>
                                <th>Adherence</th>
                                <th class="pe-3 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($rxResult && $rxResult->num_rows > 0): ?>
                                <?php while($rx = $rxResult->fetch_assoc()): 
                                    $adherence = 0;
                                    if ($rx['days_since_start'] > 0) {
                                        $adherence = round(($rx['doses_taken'] / $rx['days_since_start']) * 100);
                                        if ($adherence > 100) $adherence = 100;
                                    }
                                    $progressColor = ($adherence >= 80) ? 'bg-success' : (($adherence >= 50) ? 'bg-warning' : 'bg-danger');
                                ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($rx['medication_name']) ?></span>
                                            <small class="text-muted">Started: <?= date('M d, Y', strtotime($rx['prescribed_date'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($rx['frequency']) ?></span>
                                        </td>
                                        <td style="width: 150px;">
                                            <div class="d-flex align-items-center mt-2">
                                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                    <div class="progress-bar <?= $progressColor ?>" style="width: <?= $adherence ?>%"></div>
                                                </div>
                                                <span class="small fw-bold"><?= $adherence ?>%</span>
                                            </div>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="record_intake.php?prescription_id=<?= $rx['prescription_id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                                Log Dose
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No active treatments.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Health Log & Surveys -->
    <div class="col-lg-5">
        <div class="card glass-card mb-4">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-clipboard-question me-2"></i>Health Surveys</h6>
                <a href="take_questionnaire.php?patient_id=<?= $patient_id ?>" class="btn btn-sm btn-premium rounded-pill">Start New</a>
            </div>
            <div class="card-body py-3">
                <p class="text-muted small">Provide feedback on your symptoms (Bi-daily) to help your doctors monitor you better.</p>
                <div class="alert bg-light py-2 mb-0 border small">
                    <i class="fa-solid fa-clock text-primary me-2"></i> Next Survey Due: <strong>Today, 8:00 PM</strong>
                </div>
            </div>
        </div>

        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-notes-medical me-2"></i>Diagnosis History</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if($diagResult && $diagResult->num_rows > 0): ?>
                        <?php while($diag = $diagResult->fetch_assoc()): ?>
                            <li class="list-group-item bg-transparent py-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($diag['disease_name']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($diag['diagnosis_date'])) ?></small>
                                </div>
                                <span class="badge bg-light text-dark border small">ICD: <?= htmlspecialchars($diag['icd_code']) ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item bg-transparent text-center py-4 text-muted">No diagnoses recorded.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
