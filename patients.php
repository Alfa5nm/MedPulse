<?php
require_once 'config/db.php';
include_once 'includes/header.php';


$search = isset($_GET['search']) ? $_GET['search'] : '';


$sql = "SELECT p.*, 
        CONCAT_WS(' > ', divi.region_name, d.region_name, r.region_name) as full_region_name
        FROM patient p 
        LEFT JOIN region r ON p.region_id = r.region_id
        LEFT JOIN region d ON r.parent_region_id = d.region_id
        LEFT JOIN region divi ON d.parent_region_id = divi.region_id";

if (!empty($search)) {
    $searchTerm = $conn->real_escape_string($search);
    $sql .= " WHERE p.full_name LIKE '%$searchTerm%' OR p.phone LIKE '%$searchTerm%'";
}

$sql .= " ORDER BY p.full_name ASC";
$result = $conn->query($sql);
?>

<div class="row mb-4 fade-in-up">
    <div class="col-12">
        <div class="card glass-card">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h2 class="fw-bold mb-0"><i class="fa-solid fa-users text-primary me-2"></i> Directory</h2>
                        <p class="text-muted small mb-0">Search and filter through patient records.</p>
                    </div>
                    <div class="col-md-8 text-md-end mt-3 mt-md-0">
                        <form method="GET" action="patients.php" class="row g-2 justify-content-md-end">
                            <div class="col-sm-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" name="search" class="form-control" placeholder="Name or Phone..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <select name="blood_group" class="form-select">
                                    <option value="">All Blood</option>
                                    <?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg): ?>
                                        <option value="<?= $bg ?>" <?= (isset($_GET['blood_group']) && $_GET['blood_group'] == $bg) ? 'selected' : '' ?>><?= $bg ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <button class="btn btn-premium w-100" type="submit">Filter</button>
                            </div>
                            <div class="col-sm-2">
                                <a href="add_patient.php" class="btn btn-primary w-100"><i class="fa-solid fa-plus"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card glass-card fade-in-up" style="animation-delay: 0.1s;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-premium table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Blood Group</th>
                        <th>Region</th>
                        <th>Phone</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-bold">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold"><?= htmlspecialchars($row['full_name']) ?></span>
                                            <small class="text-muted">DOB: <?= date('M d, Y', strtotime($row['date_of_birth'])) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['gender']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['blood_group'] ?? 'N/A') ?></span></td>
                                <td><i class="fa-solid fa-location-dot text-danger me-1"></i> <span style="font-size: 0.85rem;"><?= htmlspecialchars($row['full_region_name']) ?></span></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td class="text-end pe-4">
                                    <a href="patient_details.php?id=<?= $row['patient_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        View Profile
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No patients found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
