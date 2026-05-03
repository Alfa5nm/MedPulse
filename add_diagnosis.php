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


$stmt = $conn->prepare("SELECT full_name FROM patient WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patientResult = $stmt->get_result();

if ($patientResult->num_rows == 0) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$patient = $patientResult->fetch_assoc();
$stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $icd_code_id = intval($_POST['icd_code_id'] ?? 0);
    $snomed_code_id = !empty($_POST['snomed_code_id']) ? intval($_POST['snomed_code_id']) : null;
    $diagnosis_date = $_POST['diagnosis_date'] ?? date('Y-m-d');
    $diagnosis_notes = $_POST['diagnosis_notes'] ?? '';

    if ($icd_code_id <= 0 || empty($diagnosis_date)) {
        $error = "Please select a disease and provide a diagnosis date.";
    } else {
        $stmt = $conn->prepare("INSERT INTO diagnosis (patient_id, icd_code_id, snomed_code_id, diagnosis_date, diagnosis_notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $patient_id, $icd_code_id, $snomed_code_id, $diagnosis_date, $diagnosis_notes);
        
        if ($stmt->execute()) {
            header("Location: patient_details.php?id=$patient_id&success=diagnosis_added");
            exit;
        } else {
            $error = "Error adding diagnosis: " . $stmt->error;
        }
        $stmt->close();
    }
}


$icdSql = "SELECT icd_code_id, icd_code, disease_name FROM icd_code ORDER BY disease_name ASC";
$icdResult = $conn->query($icdSql);


$snomedSql = "SELECT snomed_code_id, snomed_code, concept_name FROM snomed_code ORDER BY concept_name ASC";
$snomedResult = $conn->query($snomedSql);
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <h2 class="fw-bold mb-0">Record Clinical Diagnosis</h2>
        <p class="text-muted">Logging diagnosis for <span class="fw-bold text-dark"><?= htmlspecialchars($patient['full_name']) ?></span></p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?></div>
<?php endif; ?>

<div class="row fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8 mx-auto">
        <div class="card glass-card">
            <div class="card-body p-4">
                <form method="POST" action="add_diagnosis.php?patient_id=<?= $patient_id ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold">Primary Condition (ICD-10) <span class="text-danger">*</span></label>
                            <select name="icd_code_id" class="form-select" required>
                                <option value="">Select condition...</option>
                                <?php if($icdResult && $icdResult->num_rows > 0): ?>
                                    <?php while($icd = $icdResult->fetch_assoc()): ?>
                                        <option value="<?= $icd['icd_code_id'] ?>">
                                            <?= htmlspecialchars($icd['disease_name']) ?> (<?= htmlspecialchars($icd['icd_code']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Clinical Term (SNOMED-CT)</label>
                            <select name="snomed_code_id" class="form-select">
                                <option value="">Optional term...</option>
                                <?php if($snomedResult && $snomedResult->num_rows > 0): ?>
                                    <?php while($sc = $snomedResult->fetch_assoc()): ?>
                                        <option value="<?= $sc['snomed_code_id'] ?>">
                                            <?= htmlspecialchars($sc['concept_name']) ?> (<?= htmlspecialchars($sc['snomed_code']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Date of Diagnosis <span class="text-danger">*</span></label>
                        <input type="date" name="diagnosis_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Clinical Notes</label>
                        <textarea name="diagnosis_notes" class="form-control" rows="4" placeholder="Enter any additional clinical notes, severity, or context..."></textarea>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-outline-danger bg-white px-4 shadow-sm"><i class="fa-solid fa-stethoscope me-2"></i> Record Diagnosis</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
