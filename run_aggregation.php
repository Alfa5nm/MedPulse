<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

// In a real system, this would be an automated Cron job.
// For the demo, we are running it manually via a button click.

$today = date('Y-m-d');
$conn->begin_transaction();

try {
    // 1. Fetch all regions to build a hierarchy map
    $allRegionsSql = "SELECT region_id, parent_region_id FROM region";
    $allRegionsRes = $conn->query($allRegionsSql);
    $regionsMap = [];
    while ($row = $allRegionsRes->fetch_assoc()) {
        $regionsMap[] = $row;
    }

    // Helper function to recursively find all descendant region IDs
    function getDescendantIds($region_id, $regionsMap) {
        $ids = [$region_id];
        foreach ($regionsMap as $r) {
            if ($r['parent_region_id'] == $region_id) {
                $ids = array_merge($ids, getDescendantIds($r['region_id'], $regionsMap));
            }
        }
        return $ids;
    }

    // 2. Iterate through EVERY region
    foreach ($regionsMap as $region) {
        $region_id = $region['region_id'];
        
        // Get this region and all its sub-regions
        $targetIds = getDescendantIds($region_id, $regionsMap);
        $targetIdsCsv = implode(',', $targetIds);
        
        // Count total patients in this region + sub-regions
        $pCountSql = "SELECT COUNT(patient_id) as c FROM patient WHERE region_id IN ($targetIdsCsv)";
        $pCount = $conn->query($pCountSql)->fetch_assoc()['c'];
        
        // Skip aggregation for this level if no patients exist anywhere in its hierarchy
        if ($pCount == 0) continue;
        
        // Calculate average health score for the region hierarchy today
        $hsSql = "SELECT AVG(total_score) as avg_score FROM (
                    SELECT h.total_score 
                    FROM healthscore h 
                    JOIN patient p ON h.patient_id = p.patient_id 
                    WHERE p.region_id IN ($targetIdsCsv)
                      AND DATE(h.score_datetime) = CURDATE()
                  ) temp";
        
        $hsRes = $conn->query($hsSql)->fetch_assoc();
        $avg_score = round($hsRes['avg_score'] ?? 0, 2);
        
        // Calculate Fever Rate (LOINC: 8310-5 / Temp >= 38)
        $feverCountSql = "SELECT COUNT(DISTINCT patient_id) as c FROM observation 
                          WHERE loinc_code_id = 1 AND observation_value >= 38.0 
                          AND DATE(observation_datetime) = CURDATE() 
                          AND patient_id IN (SELECT patient_id FROM patient WHERE region_id IN ($targetIdsCsv))";
        $feverCount = $conn->query($feverCountSql)->fetch_assoc()['c'];
        $fever_rate = round(($feverCount / $pCount) * 100, 2);
        
        // Calculate Low Oxygen Rate (LOINC: 2708-6 / SpO2 < 92)
        $o2CountSql = "SELECT COUNT(DISTINCT patient_id) as c FROM observation 
                       WHERE loinc_code_id = 2 AND observation_value < 92 
                       AND DATE(observation_datetime) = CURDATE() 
                       AND patient_id IN (SELECT patient_id FROM patient WHERE region_id IN ($targetIdsCsv))";
        $o2Count = $conn->query($o2CountSql)->fetch_assoc()['c'];
        $o2_rate = round(($o2Count / $pCount) * 100, 2);
        
        // Upsert into RegionalAggregate (Use CURDATE() for date consistency)
        $upsertStmt = $conn->prepare("INSERT INTO regionalaggregate (region_id, aggregate_date, patient_count, avg_health_score, fever_rate, low_oxygen_rate) 
                      VALUES (?, CURDATE(), ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                        patient_count = VALUES(patient_count), 
                        avg_health_score = VALUES(avg_health_score), 
                        fever_rate = VALUES(fever_rate), 
                        low_oxygen_rate = VALUES(low_oxygen_rate)");
        $upsertStmt->bind_param("iiddd", $region_id, $pCount, $avg_score, $fever_rate, $o2_rate);
        $upsertStmt->execute();
        $upsertStmt->close();
        
        // --- ALERT GENERATION ---
        
        // Delete old alerts for today for this region so we don't spam duplicates
        $delStmt = $conn->prepare("DELETE FROM diseasealert WHERE region_id = ? AND alert_date = CURDATE()");
        $delStmt->bind_param("i", $region_id);
        $delStmt->execute();
        $delStmt->close();
        
        $alertStmt = $conn->prepare("INSERT INTO diseasealert (region_id, alert_date, trigger_type, trigger_value, alert_level, remarks) VALUES (?, CURDATE(), ?, ?, ?, ?)");
        
        if ($avg_score > 6) {
            $type = 'Average Health Score';
            $level = 'Critical';
            $msg = 'Regional average NEWS2 score is extremely high.';
            $alertStmt->bind_param("isdss", $region_id, $type, $avg_score, $level, $msg);
            $alertStmt->execute();
        }
        
        if ($fever_rate > 20) {
            $type = 'Fever Rate Spike';
            $level = 'Medium';
            $msg = 'More than 20% of monitored patients have a fever.';
            $alertStmt->bind_param("isdss", $region_id, $type, $fever_rate, $level, $msg);
            $alertStmt->execute();
        }
        
        if ($o2_rate > 15) {
            $type = 'Low Oxygen Rate';
            $level = 'High';
            $msg = 'Elevated cluster of hypoxia detected.';
            $alertStmt->bind_param("isdss", $region_id, $type, $o2_rate, $level, $msg);
            $alertStmt->execute();
        }
        $alertStmt->close();
    }
    
    $conn->commit();
    
    // Redirect with success message
    header("Location: dashboard.php?agg_success=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("Database error during aggregation: " . $e->getMessage());
}
?>
