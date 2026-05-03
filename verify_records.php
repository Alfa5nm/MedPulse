<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (isPatient()) {
    header("Location: dashboard.php");
    exit;
}

include_once 'includes/header.php';

// Handle Verification Action
if (isset($_GET['action']) && isset($_GET['type']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type'];
    $user_id = $_SESSION['user_id'];
    
    if ($type === 'obs') {
        $stmt = $conn->prepare("UPDATE observation SET is_verified = 1, verified_by = ? WHERE observation_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE prescription SET is_verified = 1, verified_by = ? WHERE prescription_id = ?");
    }
    
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: verify_records.php?success=verified");
    exit;
}

// Fetch Unverified Vitals
$obsSql = "SELECT o.*, p.full_name, lc.test_name 
           FROM observation o 
           JOIN patient p ON o.patient_id = p.patient_id
           JOIN loinc_code lc ON o.loinc_code_id = lc.loinc_code_id
           WHERE o.is_verified = 0 
           ORDER BY o.observation_datetime DESC";
$unverifiedObs = $conn->query($obsSql);

// Fetch Unverified Prescriptions
$rxSql = "SELECT pr.*, p.full_name, mc.medication_name 
          FROM prescription pr 
          JOIN patient p ON pr.patient_id = p.patient_id
          JOIN medication_code mc ON pr.medication_code_id = mc.medication_code_id
          WHERE pr.is_verified = 0 
          ORDER BY pr.prescribed_date DESC";
$unverifiedRx = $conn->query($rxSql);
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-user-check me-2"></i> Data Verification Queue</h2>
        <p class="text-muted">Review and approve self-reported patient data before it enters the aggregate statistics.</p>
    </div>
</div>

<div class="row g-4 fade-in-up">
    <!-- Vitals Verification -->
    <div class="col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary">Pending Vitals (Observations)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="ps-3">Patient</th>
                                <th>Test</th>
                                <th>Value</th>
                                <th class="pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($unverifiedObs && $unverifiedObs->num_rows > 0): ?>
                                <?php while($o = $unverifiedObs->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($o['full_name']) ?></td>
                                        <td><?= htmlspecialchars($o['test_name']) ?></td>
                                        <td class="fw-bold text-info"><?= $o['observation_value'] ?> <?= $o['unit'] ?></td>
                                        <td class="pe-3">
                                            <a href="verify_records.php?action=verify&type=obs&id=<?= $o['observation_id'] ?>" class="btn btn-sm btn-success rounded-pill px-3">Verify</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No pending vitals.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Prescription Verification -->
    <div class="col-lg-6">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary">Pending Prescriptions</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="ps-3">Patient</th>
                                <th>Medication</th>
                                <th class="pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($unverifiedRx && $unverifiedRx->num_rows > 0): ?>
                                <?php while($r = $unverifiedRx->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?= htmlspecialchars($r['full_name']) ?></td>
                                        <td><?= htmlspecialchars($r['medication_name']) ?> <br><small class="text-muted"><?= $r['dosage'] ?></small></td>
                                        <td class="pe-3">
                                            <a href="verify_records.php?action=verify&type=rx&id=<?= $r['prescription_id'] ?>" class="btn btn-sm btn-success rounded-pill px-3">Verify</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center py-5 text-muted">No pending prescriptions.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
