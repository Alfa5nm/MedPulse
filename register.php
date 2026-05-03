<?php
require_once 'config/db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $conn->real_escape_string($_POST['role'] ?? 'HealthWorker');
    
    // Security: Only 'HealthWorker' and 'Patient' can be self-registered. 
    // Doctors/Admins must still be manually created.
    if (!in_array($role, ['HealthWorker', 'Patient'])) {
        $role = 'HealthWorker';
    }

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $region_id = intval($_POST['region_id'] ?? 0);
        $dob = $_POST['dob'] ?? '2000-01-01';
        $gender = $_POST['gender'] ?? 'Other';
        
        $conn->begin_transaction();
        try {
            $patient_id = null;
            
            // If registering as a patient, create the patient record first
            if ($role === 'Patient') {
                if ($region_id <= 0) throw new Exception("Region is required for patients.");
                
                $pStmt = $conn->prepare("INSERT INTO patient (full_name, email, region_id, date_of_birth, gender) VALUES (?, ?, ?, ?, ?)");
                $pStmt->bind_param("ssiss", $username, $email, $region_id, $dob, $gender);
                $pStmt->execute();
                $patient_id = $pStmt->insert_id;
                $pStmt->close();
            }

            $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, patient_id, is_self_registered) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssi", $username, $email, $password_hash, $role, $patient_id);
            $stmt->execute();
            
            $conn->commit();
            $success = "Registration successful! You can now login.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch regions for dropdown (Divisions first)
$regions = $conn->query("SELECT region_id, region_name FROM region WHERE region_type = 'Division' ORDER BY region_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MedPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="register-card fade-in-up">
        <div class="text-center mb-4">
            <i class="fa-solid fa-user-plus text-primary fs-1 mb-2"></i>
            <h2 class="fw-bold text-dark">Join MedPulse</h2>
            <p class="text-muted">Register as a Health Professional</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success py-2 small"><?= $success ?> <a href="login.php" class="fw-bold">Login here</a></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="johndoe" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Register As</label>
                <select name="role" id="roleSelector" class="form-select" onchange="togglePatientFields()">
                    <option value="HealthWorker">Community Health Worker</option>
                    <option value="Patient">Patient (Self-Monitoring)</option>
                </select>
            </div>

            <!-- Conditional Patient Fields -->
            <div id="patientFields" style="display: none;" class="bg-light p-3 rounded mb-4 border shadow-sm">
                <h6 class="fw-bold small text-primary mb-3"><i class="fa-solid fa-id-card me-1"></i> Patient Demographics</h6>
                
                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold">Date of Birth</label>
                        <input type="date" name="dob" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Gender</label>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <h6 class="fw-bold small text-primary mb-3 mt-4"><i class="fa-solid fa-location-dot me-1"></i> Residential Location</h6>
                
                <div class="mb-2">
                    <label class="form-label small fw-bold">Division <span class="text-danger">*</span></label>
                    <select id="division_id" class="form-select form-select-sm">
                        <option value="">Select Division</option>
                        <?php while($d = $regions->fetch_assoc()): ?>
                            <option value="<?= $d['region_id'] ?>"><?= htmlspecialchars($d['region_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-bold">District <span class="text-danger">*</span></label>
                    <select id="district_id" class="form-select form-select-sm" disabled>
                        <option value="">Select District</option>
                    </select>
                </div>

                <div class="mb-0">
                    <label class="form-label small fw-bold">Sub-district / Area <span class="text-danger">*</span></label>
                    <select id="region_id" name="region_id" class="form-select form-select-sm" disabled>
                        <option value="">Select Area</option>
                    </select>
                </div>
            </div>

            <script>
                function togglePatientFields() {
                    const role = document.getElementById('roleSelector').value;
                    const fields = document.getElementById('patientFields');
                    fields.style.display = (role === 'Patient') ? 'block' : 'none';
                }

                // Hierarchical Region Fetching
                document.getElementById('division_id').addEventListener('change', function() {
                    let divId = this.value;
                    let distSelect = document.getElementById('district_id');
                    let subSelect = document.getElementById('region_id');
                    
                    distSelect.innerHTML = '<option value="">Loading...</option>';
                    distSelect.disabled = true;
                    subSelect.innerHTML = '<option value="">Select Area</option>';
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
                                subSelect.innerHTML = '<option value="">Select Area</option>';
                                data.forEach(d => {
                                    subSelect.innerHTML += `<option value="${d.region_id}">${d.region_name}</option>`;
                                });
                                subSelect.disabled = false;
                            });
                    }
                });
            </script>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-premium py-2 fw-bold shadow-sm">Register</button>
            </div>
            <p class="text-muted small text-center">Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In</a></p>
        </form>
    </div>
</body>
</html>
