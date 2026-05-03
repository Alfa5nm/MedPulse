<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Patient ID.</div>";
    include_once 'includes/footer.php';
    exit;
}

$sql = "SELECT full_name FROM patient WHERE patient_id = $patient_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$patient = $result->fetch_assoc();
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-md-8 col-lg-6">
        <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <div class="card glass-card p-4">
            <h3 class="fw-bold mb-1 text-primary text-center">Add Vital Observations</h3>
            <p class="text-center text-muted mb-4">Patient: <strong class="text-dark"><?= htmlspecialchars($patient['full_name']) ?></strong></p>

            <form action="calculate_score.php" method="POST">
                <input type="hidden" name="patient_id" value="<?= $patient_id ?>">

                <div class="mb-3">
                    <label class="form-label fw-medium"><i class="fa-solid fa-temperature-half text-danger me-1"></i> Body Temperature (°C)</label>
                    <input type="number" step="0.1" class="form-control" name="temperature" placeholder="e.g., 36.5" required>
                    <small class="text-muted">LOINC: 8310-5</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium"><i class="fa-solid fa-lungs text-info me-1"></i> Oxygen Saturation (SpO2 %)</label>
                    <input type="number" step="1" class="form-control" name="oxygen" placeholder="e.g., 98" required>
                    <small class="text-muted">LOINC: 2708-6</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium"><i class="fa-solid fa-droplet text-danger me-1"></i> Systolic Blood Pressure (mmHg)</label>
                    <input type="number" step="1" class="form-control" name="systolic_bp" placeholder="e.g., 120" required>
                    <small class="text-muted">LOINC: 8480-6</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium"><i class="fa-solid fa-heart-pulse text-danger me-1"></i> Heart/Pulse Rate (BPM)</label>
                    <input type="number" step="1" class="form-control" name="pulse" placeholder="e.g., 75" required>
                    <small class="text-muted">LOINC: 8867-4</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium"><i class="fa-solid fa-wind text-secondary me-1"></i> Respiratory Rate (breaths/min)</label>
                    <input type="number" step="1" class="form-control" name="respiratory" placeholder="e.g., 16" required>
                    <small class="text-muted">LOINC: 9279-1</small>
                </div>

                
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-premium btn-lg shadow-sm"><i class="fa-solid fa-calculator me-2"></i> Save & Calculate NEWS2 Score</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
