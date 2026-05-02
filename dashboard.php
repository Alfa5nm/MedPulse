<?php
require_once 'config/db.php';
include_once 'includes/header.php';

// Fetch top metrics
$patientCountRes = $conn->query("SELECT COUNT(*) as mt FROM patient")->fetch_assoc();
$totalPatients = $patientCountRes['mt'];

$alertsTodayRes = $conn->query("SELECT COUNT(*) as mt FROM diseasealert WHERE alert_date = CURDATE()")->fetch_assoc();
$alertsToday = $alertsTodayRes['mt'];

$avgScoreRes = $conn->query("SELECT AVG(total_score) as mt FROM healthscore")->fetch_assoc();
$avgScore = round($avgScoreRes['mt'] ?? 0, 1);

// Regional summary (latest available aggregates per region)
$regionSql = "
    SELECT r.region_name, a.patient_count, a.avg_health_score, a.fever_rate, a.low_oxygen_rate, a.aggregate_date
    FROM regionalaggregate a
    JOIN region r ON a.region_id = r.region_id
    WHERE a.aggregate_date = (SELECT MAX(aggregate_date) FROM regionalaggregate ra WHERE ra.region_id = a.region_id)
    ORDER BY a.avg_health_score DESC
";
$regions = $conn->query($regionSql);

// Fetch recent alerts
$alertSql = "
    SELECT d.trigger_type, d.trigger_value, d.alert_level, d.alert_date, r.region_name 
    FROM diseasealert d 
    JOIN region r ON d.region_id = r.region_id 
    ORDER BY d.alert_date DESC, d.alert_id DESC LIMIT 5
";
$recentAlerts = $conn->query($alertSql);

function getBadgeClass($level) {
    if(strtolower($level) == 'critical') return 'badge-severe';
    if(strtolower($level) == 'high') return 'badge-high';
    if(strtolower($level) == 'medium') return 'badge-medium';
    return 'badge-low';
}
?>

<!-- Include Chart.js for premium visuals -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4 fade-in-up">
    <div>
        <h2 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-chart-line me-2"></i> Population Health Dashboard</h2>
        <p class="text-muted">Real-time health monitoring and outbreak detection</p>
    </div>
    <a href="run_aggregation.php" class="btn btn-premium shadow-sm">
        <i class="fa-solid fa-rotate me-1"></i> Run Daily Aggregation
    </a>
</div>

<!-- Top Metrics -->
<div class="row g-4 mb-4 fade-in-up" style="animation-delay: 0.1s;">
    <div class="col-md-4">
        <div class="card glass-card h-100 border-start border-4 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 text-uppercase small">Total Monitored Patients</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($totalPatients) ?></h2>
                    </div>
                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-users fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100 border-start border-4 border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 text-uppercase small">Alerts Today</p>
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($alertsToday) ?></h2>
                    </div>
                    <div class="bg-danger text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-bell fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card glass-card h-100 border-start border-4 border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted fw-bold mb-1 text-uppercase small">Avg Population Health Score</p>
                        <h2 class="fw-bold text-dark mb-0"><?= $avgScore ?> <span class="fs-6 fw-normal text-muted">/ NEWS2</span></h2>
                    </div>
                    <div class="bg-info text-white rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-heart-pulse fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 fade-in-up" style="animation-delay: 0.2s;">
    <!-- Regional Aggregates -->
    <div class="col-lg-8">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary">Regional Health Summary (Latest Aggregates)</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-premium table-hover mb-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Region</th>
                                <th>Date</th>
                                <th>Monitored Pop.</th>
                                <th>Avg Score</th>
                                <th>Fever Rate</th>
                                <th>Low O2 Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $locNames = []; $locScores = [];
                            if($regions && $regions->num_rows > 0): 
                            ?>
                                <?php while($r = $regions->fetch_assoc()): 
                                    $locNames[] = $r['region_name'];
                                    $locScores[] = floatval($r['avg_health_score']);
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-medium text-dark"><i class="fa-solid fa-location-dot text-danger me-1"></i> <?= htmlspecialchars($r['region_name']) ?></td>
                                        <td class="text-muted"><?= date('M d', strtotime($r['aggregate_date'])) ?></td>
                                        <td><?= number_format($r['patient_count']) ?></td>
                                        <td class="fw-bold <?= $r['avg_health_score'] >= 5 ? 'text-danger' : 'text-success' ?>"><?= number_format($r['avg_health_score'], 1) ?></td>
                                        <td><?= $r['fever_rate'] ?>%</td>
                                        <td><?= $r['low_oxygen_rate'] ?>%</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No regional aggregates available yet. Run the aggregation script.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="col-lg-4">
        <div class="card glass-card h-100">
            <?php
            // Get top region for trend (default to first if exists)
            if ($regions->num_rows > 0) {
                $regions->data_seek(0);
                $firstRegion = $regions->fetch_assoc();
                $region_id = $firstRegion['region_id'];
                
                $trendSql = "SELECT aggregate_date, avg_health_score, fever_rate 
                            FROM regionalaggregate 
                            WHERE region_id = $region_id 
                            AND aggregate_date > DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                            ORDER BY aggregate_date ASC";
                $trendRes = $conn->query($trendSql);
                $trendData = [];
                while($tr = $trendRes->fetch_assoc()) { $trendData[] = $tr; }
            }
            ?>
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-chart-line me-2"></i> 7-Day Trend (<?= $firstRegion['region_name'] ?? 'N/A' ?>)</h6>
            </div>
            <div class="card-body py-2">
                <canvas id="trendChart" style="height: 120px;"></canvas>
                <?php
                    // Predictive Logic: Calculate velocity over last 48 hours
                    $velocity = 0;
                    if (count($trendData) >= 2) {
                        $last = end($trendData)['avg_health_score'];
                        $prev = $trendData[count($trendData)-2]['avg_health_score'];
                        if ($prev > 0) {
                            $velocity = (($last - $prev) / $prev) * 100;
                        }
                    }
                ?>
                <div class="mt-3 p-2 rounded bg-light border small">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Predictive Velocity:</span>
                        <span class="fw-bold <?= $velocity > 15 ? 'text-danger' : 'text-success' ?>">
                            <i class="fa-solid <?= $velocity > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?> me-1"></i>
                            <?= round($velocity, 1) ?>% / 48h
                        </span>
                    </div>
                    <?php if($velocity > 25): ?>
                        <div class="mt-1 text-danger fw-bold" style="font-size: 0.7rem;">
                            <i class="fa-solid fa-circle-exclamation"></i> HIGH MOMENTUM OUTBREAK RISK
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center border-top">
                <h6 class="fw-bold mb-0 text-primary">Recent Active Alerts</h6>
                <a href="alerts.php" class="btn btn-sm btn-outline-secondary rounded-pill">View All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if($recentAlerts && $recentAlerts->num_rows > 0): ?>
                        <?php while($alert = $recentAlerts->fetch_assoc()): ?>
                            <li class="list-group-item bg-transparent py-3 border-bottom border-light">
                                <div class="d-flex w-100 justify-content-between mb-2">
                                    <span class="badge <?= getBadgeClass($alert['alert_level']) ?>"><?= htmlspecialchars($alert['alert_level']) ?></span>
                                    <small class="text-muted"><?= date('M d', strtotime($alert['alert_date'])) ?></small>
                                </div>
                                <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($alert['region_name']) ?></h6>
                                <p class="mb-0 small text-muted">
                                    <i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> 
                                    <?= htmlspecialchars($alert['trigger_type']) ?> (Val: <?= $alert['trigger_value'] ?>)
                                </p>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item bg-transparent text-center text-muted py-4 border-0">
                            <i class="fa-solid fa-check-circle text-success fs-1 mb-2 opacity-50"></i>
                            <br>No recent alerts!
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php 
$mapData = [];
if($regions && $regions->num_rows > 0) {
    $regions->data_seek(0);
    while($r = $regions->fetch_assoc()){
        $mapData[$r['region_name']] = [
            'score' => floatval($r['avg_health_score']),
            'patients' => intval($r['patient_count']),
            'fever' => floatval($r['fever_rate'])
        ];
    }
}
?>

<?php if(!empty($locNames)): ?>
<div class="row mt-4 fade-in-up" style="animation-delay: 0.3s;">
    <div class="col-lg-7 mb-4 mb-lg-0">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="fa-solid fa-map-location-dot me-2"></i> Disease Heatmap</h6>
            </div>
            <div class="card-body p-0">
                <div id="bdMap" style="height: 400px; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card glass-card h-100">
            <div class="card-header bg-transparent py-3">
                <h6 class="fw-bold mb-0 text-primary">Avg Health Score by Region</h6>
            </div>
            <div class="card-body d-flex align-items-center">
                <canvas id="regionChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Bar Chart
    const ctx = document.getElementById('regionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_slice($locNames, 0, 10)) ?>,
            datasets: [{
                label: 'Avg Health Score (Top 10)',
                data: <?= json_encode(array_slice($locScores, 0, 10)) ?>,
                backgroundColor: 'rgba(56, 189, 248, 0.7)',
                borderColor: 'rgba(56, 189, 248, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 15 }
            }
        }
    });

    // Leaflet Map
    const mapData = <?= json_encode($mapData) ?>;
    const map = L.map('bdMap').setView([23.6850, 90.3563], 6.5);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    function getColor(score) {
        if (score === undefined) return '#f1f5f9'; // Very light gray for no data
        return score >= 6 ? '#ef4444' : // Red (Critical)
               score >= 3 ? '#f59e0b' : // Yellow (Warning)
                            '#10b981';  // Green (Safe)
    }

    fetch('assets/js/bd-map.geojson')
        .then(res => res.json())
        .then(data => {
            L.geoJson(data, {
                style: function(feature) {
                    const upazila = feature.properties.NAME_3;
                    const district = feature.properties.NAME_2;
                    let regionStat = mapData[upazila] || mapData[district] || null;
                    let score = regionStat ? regionStat.score : undefined;
                    
                    return {
                        fillColor: getColor(score),
                        weight: 1,
                        opacity: 1,
                        color: 'white',
                        fillOpacity: regionStat ? 0.8 : 0.4
                    };
                },
                onEachFeature: function(feature, layer) {
                    const upazila = feature.properties.NAME_3;
                    const district = feature.properties.NAME_2;
                    let regionStat = mapData[upazila] || mapData[district] || null;
                    
                    let popupContent = `<div style="font-family: 'Inter', sans-serif;">`;
                    popupContent += `<h6 class="mb-1 fw-bold">${upazila || district}</h6>`;
                    if (regionStat) {
                        popupContent += `<div class="small">`;
                        popupContent += `<div><strong>Patients:</strong> ${regionStat.patients}</div>`;
                        popupContent += `<div><strong>Avg Score:</strong> ${regionStat.score}</div>`;
                        popupContent += `<div><strong>Fever Rate:</strong> ${regionStat.fever}%</div>`;
                        popupContent += `</div>`;
                    } else {
                        popupContent += `<div class="text-muted small">No active patients</div>`;
                    }
                    popupContent += `</div>`;
                    
                    layer.bindTooltip(popupContent);
                    
                    // Highlight on hover
                    layer.on({
                        mouseover: function(e) {
                            var layer = e.target;
                            layer.setStyle({
                                weight: 3,
                                color: '#6366f1',
                                fillOpacity: 0.9
                            });
                            layer.bringToFront();
                        },
                        mouseout: function(e) {
                            // Reset style
                            // Because style function uses the feature, we need to reset to default
                            let s = regionStat ? regionStat.score : undefined;
                            layer.setStyle({
                                fillColor: getColor(s),
                                weight: 1,
                                color: 'white',
                                fillOpacity: regionStat ? 0.8 : 0.4
                            });
                        }
                    });
                }
            }).addTo(map);
        })
        .catch(err => console.error("Error loading map GeoJSON: ", err));
});
</script>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>
