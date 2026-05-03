<?php
require_once 'config/db.php';
include_once 'includes/header.php';


$region_filter = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
$date_filter = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : '';


$regionsRs = $conn->query("SELECT region_id, region_name FROM region ORDER BY region_name");


$sql = "SELECT d.*, r.region_name 
        FROM diseasealert d 
        JOIN region r ON d.region_id = r.region_id
        WHERE 1=1";

if ($region_filter > 0) {
    $sql .= " AND d.region_id = $region_filter";
}
if (!empty($date_filter)) {
    $sql .= " AND DATE(d.alert_date) = '$date_filter'";
}

$sql .= " ORDER BY d.alert_date DESC, d.alert_id DESC LIMIT 50";
$result = $conn->query($sql);

function getBadgeClass($level) {
    switch(strtolower($level)) {
        case 'critical': return 'badge-severe';
        case 'high': return 'badge-high';
        case 'medium': return 'badge-medium';
        default: return 'badge-low';
    }
}
?>

<div class="row align-items-center mb-4 fade-in-up">
    <div class="col-md-6 mb-3 mb-md-0">
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Disease Alerts Log</h2>
        <p class="text-muted">Review historical outbreak warnings across monitored regions.</p>
    </div>
    <div class="col-md-6">
        <form method="GET" action="alerts.php" class="row g-2 justify-content-md-end">
            <div class="col-auto">
                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date_filter) ?>" title="Filter by Date">
            </div>
            <div class="col-auto">
                <select name="region_id" class="form-select form-select-sm" title="Filter by Region">
                    <option value="0">All Regions</option>
                    <?php while ($r = $regionsRs->fetch_assoc()): ?>
                        <option value="<?= $r['region_id'] ?>" <?= ($region_filter == $r['region_id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['region_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-auto d-grid">
                <button type="submit" class="btn btn-premium btn-sm"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card glass-card fade-in-up" style="animation-delay: 0.1s;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-premium table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Region</th>
                        <th>Severity</th>
                        <th>Trigger Cond.</th>
                        <th>Value</th>
                        <th class="pe-4">System Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($alert = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 text-muted fw-medium"><?= date('M d, Y', strtotime($alert['alert_date'])) ?></td>
                                <td class="fw-bold text-dark"><i class="fa-solid fa-map-marker-alt text-danger me-1"></i> <?= htmlspecialchars($alert['region_name']) ?></td>
                                <td>
                                    <span class="badge <?= getBadgeClass($alert['alert_level']) ?>"><?= htmlspecialchars($alert['alert_level']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($alert['trigger_type']) ?></td>
                                <td class="fw-bold fs-5 <?= floatval($alert['trigger_value']) > 15 ? 'text-danger' : 'text-dark' ?>"><?= $alert['trigger_value'] ?></td>
                                <td class="pe-4 small text-muted"><i class="fa-solid fa-note-sticky text-info me-1"></i> <?= htmlspecialchars($alert['remarks']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-face-smile fs-1 mb-3 opacity-50"></i><br>
                                No alerts matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
