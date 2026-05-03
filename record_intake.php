<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$prescription_id = isset($_GET['prescription_id']) ? intval($_GET['prescription_id']) : 0;
$error = '';

if ($prescription_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Prescription ID.</div>";
    include_once 'includes/footer.php';
    exit;
}


$sql = "SELECT p.*, pt.full_name, mc.medication_name 
        FROM prescription p 
        JOIN patient pt ON p.patient_id = pt.patient_id
        JOIN medication_code mc ON p.medication_code_id = mc.medication_code_id
        WHERE p.prescription_id = $prescription_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Prescription not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$rx = $result->fetch_assoc();
$patient_id = $rx['patient_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['intake_status'] ?? 'Taken';
    $remarks = $_POST['remarks'] ?? '';
    $intake_datetime = $_POST['intake_datetime'] ?? date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO intake_log (prescription_id, intake_datetime, intake_status, remarks) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $prescription_id, $intake_datetime, $status, $remarks);
    
    if ($stmt->execute()) {
        $redirect = ($_SESSION['role'] === 'Patient') ? "my_health.php" : "patient_details.php?id=$patient_id";
        header("Location: $redirect&success=intake_logged");
        exit;
    } else {
        $error = "Error logging intake: " . $stmt->error;
    }
    $stmt->close();
}
?>

<div class="row justify-content-center fade-in-up">
    <div class="col-md-8 col-lg-6">
        <?php if($_SESSION['role'] === 'Patient'): ?>
            <a href="my_health.php" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard</a>
        <?php else: ?>
            <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-3 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <?php endif; ?>
        
        <div class="card glass-card border-top border-4 border-success">
            <div class="card-body p-4">
                <h3 class="fw-bold mb-1 text-success text-center"><i class="fa-solid fa-check-to-slot me-2"></i>Record Medication Intake</h3>
                <p class="text-center text-muted mb-4">Patient: <strong class="text-dark"><?= htmlspecialchars($rx['full_name']) ?></strong></p>
                
                <div class="alert bg-light border mb-4">
                    <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-capsules text-info me-2"></i><?= htmlspecialchars($rx['medication_name']) ?></div>
                    <div class="small text-muted">Dosage: <?= htmlspecialchars($rx['dosage']) ?> | Frequency: <?= htmlspecialchars($rx['frequency']) ?></div>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-danger mb-3"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Intake Status</label>
                        <select name="intake_status" class="form-select" required>
                            <option value="Taken">Taken</option>
                            <option value="Missed">Missed</option>
                            <option value="Delayed">Delayed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Date & Time</label>
                        <input type="datetime-local" name="intake_datetime" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Any side effects or special notes..."></textarea>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg shadow-sm"><i class="fa-solid fa-save me-2"></i>Save Intake Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
