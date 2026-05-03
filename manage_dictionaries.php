<?php
require_once 'config/db.php';
include_once 'includes/header.php';

if (!isAdmin()) {
    echo "<div class='alert alert-danger'>Access Denied: Admin only.</div>";
    include_once 'includes/footer.php';
    exit;
}

$tab = $_GET['tab'] ?? 'meds';
$success = '';
$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_med'])) {
        $name = $conn->real_escape_string($_POST['med_name']);
        if ($conn->query("INSERT INTO medication_code (medication_name) VALUES ('$name')")) $success = "Medication added.";
    } elseif (isset($_POST['add_icd'])) {
        $code = $conn->real_escape_string($_POST['icd_code']);
        $name = $conn->real_escape_string($_POST['disease_name']);
        if ($conn->query("INSERT INTO icd_code (icd_code, disease_name) VALUES ('$code', '$name')")) $success = "ICD Code added.";
    }
}


$meds = $conn->query("SELECT * FROM medication_code ORDER BY medication_name ASC");
$icds = $conn->query("SELECT * FROM icd_code ORDER BY disease_name ASC");
$regions = $conn->query("SELECT r.*, p.region_name as parent_name FROM region r LEFT JOIN region p ON r.parent_region_id = p.region_id ORDER BY r.region_type, r.region_name");

?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-gears me-2"></i>System Dictionaries</h2>
        <p class="text-muted">Manage the underlying medical and regional databases.</p>
    </div>
</div>

<?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card glass-card">
    <div class="card-header bg-transparent pt-3">
        <ul class="nav nav-tabs card-header-tabs border-0" id="dictionaryTabs">
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'meds' ? 'active fw-bold border-bottom border-3 border-primary' : '' ?>" href="?tab=meds">Medications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'icd' ? 'active fw-bold border-bottom border-3 border-primary' : '' ?>" href="?tab=icd">ICD-10 Diseases</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab == 'regions' ? 'active fw-bold border-bottom border-3 border-primary' : '' ?>" href="?tab=regions">Regions</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        <?php if($tab == 'meds'): ?>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Add New Medication</h6>
                            <form method="POST">
                                <input type="text" name="med_name" class="form-control mb-3" placeholder="Medication Name" required>
                                <button type="submit" name="add_med" class="btn btn-primary w-100">Add to List</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-premium table-hover">
                            <thead><tr><th>ID</th><th>Medication Name</th></tr></thead>
                            <tbody>
                                <?php while($m = $meds->fetch_assoc()): ?>
                                    <tr><td>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($tab == 'icd'): ?>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Add ICD-10 Code</h6>
                            <form method="POST">
                                <input type="text" name="icd_code" class="form-control mb-2" placeholder="ICD Code (e.g. B34.2)" required>
                                <input type="text" name="disease_name" class="form-control mb-3" placeholder="Disease Name" required>
                                <button type="submit" name="add_icd" class="btn btn-primary w-100">Add to List</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-premium table-hover">
                            <thead><tr><th>Code</th><th>Disease Name</th></tr></thead>
                            <tbody>
                                <?php while($i = $icds->fetch_assoc()): ?>
                                    <tr><td><span class="badge bg-secondary"><?= htmlspecialchars($i['icd_code']) ?></span></td><td class="fw-medium"><?= htmlspecialchars($i['disease_name']) ?></td></tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($tab == 'regions'): ?>
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-premium table-hover">
                    <thead><tr><th>Type</th><th>Region Name</th><th>Parent Region</th></tr></thead>
                    <tbody>
                        <?php while($r = $regions->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-info text-dark"><?= $r['region_type'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($r['region_name']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($r['parent_name'] ?? 'None') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
