<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if ($patient_id <= 0) {
    echo "<div class='alert alert-danger'>Invalid Patient ID.</div>";
    include_once 'includes/footer.php';
    exit;
}


$sql = "SELECT * FROM patient WHERE patient_id = $patient_id";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Patient not found.</div>";
    include_once 'includes/footer.php';
    exit;
}
$patient = $result->fetch_assoc();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');
    $gender = $conn->real_escape_string($_POST['gender'] ?? '');
    $blood_group = $conn->real_escape_string($_POST['blood_group'] ?? '');
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $region_id = intval($_POST['region_id'] ?? 0);

    if (empty($full_name) || empty($date_of_birth) || empty($gender) || empty($region_id)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("UPDATE patient SET region_id = ?, full_name = ?, date_of_birth = ?, gender = ?, blood_group = ?, phone = ? WHERE patient_id = ?");
        $stmt->bind_param("isssssi", $region_id, $full_name, $date_of_birth, $gender, $blood_group, $phone, $patient_id);
        
        if ($stmt->execute()) {
            
            $user_id = $_SESSION['user_id'] ?? null;
            $auditAction = 'Edit';
            $auditEntity = 'Patient';
            $auditIp = $_SERVER['REMOTE_ADDR'];
            $auditDetails = "Updated demographics for Patient ID: $patient_id (Name: $full_name)";

            $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, target_entity, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $auditStmt->bind_param("ississ", $user_id, $auditAction, $auditEntity, $patient_id, $auditDetails, $auditIp);
            $auditStmt->execute();
            $auditStmt->close();

            header("Location: patient_details.php?id=$patient_id&success=updated");
            exit;
        } else {
            $error = "Error updating patient: " . $stmt->error;
        }
        $stmt->close();
    }
}


$divSql = "SELECT region_id, region_name FROM region WHERE region_type = 'Division' ORDER BY region_name ASC";
$divResult = $conn->query($divSql);


$hierSql = "SELECT r.region_id as sub_id, d.region_id as dist_id, divi.region_id as div_id
            FROM region r 
            JOIN region d ON r.parent_region_id = d.region_id
            JOIN region divi ON d.parent_region_id = divi.region_id
            WHERE r.region_id = " . $patient['region_id'];
$hierRes = $conn->query($hierSql);
$hier = $hierRes->fetch_assoc();
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <a href="patient_details.php?id=<?= $patient_id ?>" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Profile</a>
        <h2 class="fw-bold mb-0">Edit Patient Profile</h2>
        <p class="text-muted">Update demographics for <span class="text-dark fw-bold"><?= htmlspecialchars($patient['full_name']) ?></span></p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger shadow-sm"><?= $error ?></div>
<?php endif; ?>

<div class="row fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8 mx-auto">
        <div class="card glass-card">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($patient['full_name']) ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?= $patient['date_of_birth'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="Male" <?= $patient['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $patient['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $patient['gender'] == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="" <?= !$patient['blood_group'] ? 'selected' : '' ?>>Unknown</option>
                                <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                    <option value="<?= $bg ?>" <?= $patient['blood_group'] == $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone']) ?>">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Division <span class="text-danger">*</span></label>
                            <select id="division_id" class="form-select" required>
                                <?php while($div = $divResult->fetch_assoc()): ?>
                                    <option value="<?= $div['region_id'] ?>" <?= $hier['div_id'] == $div['region_id'] ? 'selected' : '' ?>><?= htmlspecialchars($div['region_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">District <span class="text-danger">*</span></label>
                            <select id="district_id" class="form-select" required>
                                
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sub-district <span class="text-danger">*</span></label>
                            <select id="region_id" name="region_id" class="form-select" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-premium px-4"><i class="fa-solid fa-save me-2"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const currentDist = <?= $hier['dist_id'] ?>;
const currentSub = <?= $hier['sub_id'] ?>;

function loadDistricts(divId, selectedId = null) {
    let distSelect = document.getElementById('district_id');
    return fetch('get_regions.php?parent_id=' + divId)
        .then(res => res.json())
        .then(data => {
            distSelect.innerHTML = '<option value="">Select District</option>';
            data.forEach(d => {
                distSelect.innerHTML += `<option value="${d.region_id}" ${selectedId == d.region_id ? 'selected' : ''}>${d.region_name}</option>`;
            });
            distSelect.disabled = false;
        });
}

function loadSubDistricts(distId, selectedId = null) {
    let subSelect = document.getElementById('region_id');
    return fetch('get_regions.php?parent_id=' + distId)
        .then(res => res.json())
        .then(data => {
            subSelect.innerHTML = '<option value="">Select Sub-district</option>';
            data.forEach(d => {
                subSelect.innerHTML += `<option value="${d.region_id}" ${selectedId == d.region_id ? 'selected' : ''}>${d.region_name}</option>`;
            });
            subSelect.disabled = false;
        });
}

document.getElementById('division_id').addEventListener('change', function() {
    loadDistricts(this.value).then(() => {
        document.getElementById('region_id').innerHTML = '<option value="">Select Sub-district</option>';
    });
});

document.getElementById('district_id').addEventListener('change', function() {
    loadSubDistricts(this.value);
});

// Initial Load
loadDistricts(document.getElementById('division_id').value, currentDist).then(() => {
    loadSubDistricts(currentDist, currentSub);
});
</script>

<?php include_once 'includes/footer.php'; ?>
