<?php
require_once 'config/db.php';
include_once 'includes/header.php';

$error = '';
$success = '';

// Handle form submission
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
        $stmt = $conn->prepare("INSERT INTO patient (region_id, full_name, date_of_birth, gender, blood_group, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $region_id, $full_name, $date_of_birth, $gender, $blood_group, $phone);
        
        if ($stmt->execute()) {
            $new_patient_id = $stmt->insert_id;
            header("Location: patient_details.php?id=$new_patient_id&success=registered");
            exit;
        } else {
            $error = "Error adding patient: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch Divisions for first dropdown
$divSql = "SELECT region_id, region_name FROM region WHERE region_type = 'Division' ORDER BY region_name ASC";
$divResult = $conn->query($divSql);
?>

<div class="row fade-in-up">
    <div class="col-12 mb-4">
        <a href="patients.php" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Back to Patients</a>
        <h2 class="fw-bold mb-0">Register New Patient</h2>
        <p class="text-muted">Enter the patient's demographics to enroll them in the system.</p>
    </div>
</div>

<?php if($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-circle-exclamation me-2"></i> <?= $error ?></div>
<?php endif; ?>

<div class="row fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-lg-8 mx-auto">
        <div class="card glass-card">
            <div class="card-body p-4">
                <form method="POST" action="add_patient.php">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_birth" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Unknown</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 01711223344">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Division <span class="text-danger">*</span></label>
                            <select id="division_id" class="form-select" required>
                                <option value="">Select Division</option>
                                <?php if($divResult && $divResult->num_rows > 0): ?>
                                    <?php while($div = $divResult->fetch_assoc()): ?>
                                        <option value="<?= $div['region_id'] ?>"><?= htmlspecialchars($div['region_name']) ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">District <span class="text-danger">*</span></label>
                            <select id="district_id" class="form-select" disabled required>
                                <option value="">Select District</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Sub-district <span class="text-danger">*</span></label>
                            <select id="region_id" name="region_id" class="form-select" disabled required>
                                <option value="">Select Sub-district</option>
                            </select>
                        </div>
                        <div class="col-12 mt-1">
                            <div class="form-text">Helps in tracking regional disease outbreaks accurately.</div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-premium px-4"><i class="fa-solid fa-user-plus me-2"></i> Register Patient</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
document.getElementById('division_id').addEventListener('change', function() {
    let divId = this.value;
    let distSelect = document.getElementById('district_id');
    let subSelect = document.getElementById('region_id');
    
    distSelect.innerHTML = '<option value="">Loading...</option>';
    distSelect.disabled = true;
    subSelect.innerHTML = '<option value="">Select Sub-district</option>';
    subSelect.disabled = true;

    if (divId) {
        fetch('get_regions.php?parent_id=' + divId)
            .then(res => res.json())
            .then(data => {
                distSelect.innerHTML = '<option value="">Select District</option>';
                data.forEach(d => {
                    distSelect.innerHTML += `<option value="${d.region_id}">${d.region_name}</option>`;
                });
                distSelect.disabled = false;
            });
    }
});

document.getElementById('district_id').addEventListener('change', function() {
    let distId = this.value;
    let subSelect = document.getElementById('region_id');
    
    subSelect.innerHTML = '<option value="">Loading...</option>';
    subSelect.disabled = true;

    if (distId) {
        fetch('get_regions.php?parent_id=' + distId)
            .then(res => res.json())
            .then(data => {
                subSelect.innerHTML = '<option value="">Select Sub-district</option>';
                data.forEach(d => {
                    subSelect.innerHTML += `<option value="${d.region_id}">${d.region_name}</option>`;
                });
                subSelect.disabled = false;
            });
    }
});
</script>
