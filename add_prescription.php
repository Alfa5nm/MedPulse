<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$error = '';

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medication_code_id = intval($_POST['medication_code_id'] ?? 0);
    $dosage = $conn->real_escape_string($_POST['dosage'] ?? '');
    $frequency = $conn->real_escape_string($_POST['frequency'] ?? '');
    $duration_days = intval($_POST['duration_days'] ?? 0);
    $prescribed_date = $conn->real_escape_string($_POST['prescribed_date'] ?? date('Y-m-d'));
    $instructions = $conn->real_escape_string($_POST['instructions'] ?? '');

    if ($medication_code_id <= 0 || empty($dosage) || empty($frequency) || $duration_days <= 0 || empty($prescribed_date)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO prescription (patient_id, medication_code_id, dosage, frequency, duration_days, prescribed_date, instructions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iississ", $patient_id, $medication_code_id, $dosage, $frequency, $duration_days, $prescribed_date, $instructions);
        
        if ($stmt->execute()) {
            header("Location: patient_details.php?id=$patient_id&success=prescription_added");
            exit;
        } else {
            $error = "Error adding prescription: " . $stmt->error;
        }
        $stmt->close();
    }
}


$medSql = "SELECT medication_code_id, medication_name, generic_name FROM medication_code ORDER BY medication_name ASC";
$medResult = $conn->query($medSql);
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <h2 class="fw-bold mb-0">Add Prescription</h2>
        <p class="text-muted">Prescribing medication for <span class="fw-bold text-dark"><?= htmlspecialchars($patient['full_name']) ?></span></p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?></div>
<?php endif; ?>

<div class="row fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8 mx-auto">
        <div class="card glass-card border-top border-4 border-info">
            <div class="card-body p-4">
                <form method="POST" action="add_prescription.php?patient_id=<?= $patient_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Medication <span class="text-danger">*</span></label>
                        <select name="medication_code_id" class="form-select" required>
                            <option value="">Select medication...</option>
                            <?php if($medResult && $medResult->num_rows > 0): ?>
                                <?php while($med = $medResult->fetch_assoc()): ?>
                                    <option value="<?= $med['medication_code_id'] ?>">
                                        <?= htmlspecialchars($med['medication_name']) ?> (<?= htmlspecialchars($med['generic_name']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dosage <span class="text-danger">*</span></label>
                            <input type="text" name="dosage" class="form-control" placeholder="e.g., 500mg, 1 tablet" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Frequency <span class="text-danger">*</span></label>
                            <input type="text" name="frequency" class="form-control" placeholder="e.g., Twice a day, Every 8 hours" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duration (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="duration_days" class="form-control" min="1" placeholder="e.g., 7" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prescription Date <span class="text-danger">*</span></label>
                            <input type="date" name="prescribed_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Special Instructions</label>
                        <textarea name="instructions" class="form-control" rows="3" placeholder="e.g., Take after meals..."></textarea>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-premium px-4 shadow-sm"><i class="fa-solid fa-file-prescription me-2"></i> Save Prescription</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
