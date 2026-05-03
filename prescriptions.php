<?php
require_once 'config/db.php';
include_once 'includes/header.php';


$sql = "SELECT p.*, pt.full_name as patient_name, mc.medication_name 
        FROM prescription p 
        JOIN patient pt ON p.patient_id = pt.patient_id
        JOIN medication_code mc ON p.medication_code_id = mc.medication_code_id
        ORDER BY p.prescribed_date DESC, p.prescription_id DESC LIMIT 50";
$result = $conn->query($sql);
?>

<div class="row align-items-center mb-4 fade-in-up">
    <div class="col-md-6 mb-3 mb-md-0">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-pills text-info me-2"></i> Prescriptions Log</h2>
        <p class="text-muted">Review recently issued prescriptions across the system.</p>
    </div>
</div>

<div class="card glass-card fade-in-up" style="animation-delay: 0.1s;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-premium table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Patient</th>
                        <th>Medication</th>
                        <th>Dosage & Frequency</th>
                        <th>Duration</th>
                        <th class="pe-4">Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($rx = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-medium"><?= date('M d, Y', strtotime($rx['prescribed_date'])) ?></td>
                                <td class="fw-bold text-dark"><a href="patient_details.php?id=<?= $rx['patient_id'] ?>" class="text-decoration-none text-primary"><?= htmlspecialchars($rx['patient_name']) ?></a></td>
                                <td><i class="fa-solid fa-capsules text-secondary me-1"></i> <?= htmlspecialchars($rx['medication_name']) ?></td>
                                <td><?= htmlspecialchars($rx['dosage']) ?> <span class="badge bg-light text-dark border"><?= htmlspecialchars($rx['frequency']) ?></span></td>
                                <td><?= intval($rx['duration_days']) ?> Days</td>
                                <td class="pe-4 small text-muted"><?= htmlspecialchars($rx['instructions']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-file-prescription fs-1 mb-3 opacity-50"></i><br>
                                No prescriptions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
