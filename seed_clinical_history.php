<?php
require_once 'config/db.php';

echo "<h2>MedPulse Clinical Data Seeder</h2>";
echo "<p>Generating realistic medical history...</p><ul>";

// 1. Get Patients
$patientsRes = $conn->query("SELECT patient_id FROM patient LIMIT 20");
$patients = [];
while($p = $patientsRes->fetch_assoc()) $patients[] = $p['patient_id'];

// 2. Get Medication Codes
$medsRes = $conn->query("SELECT medication_code_id FROM medication_code");
$meds = [];
while($m = $medsRes->fetch_assoc()) $meds[] = $m['medication_code_id'];

if (empty($patients) || empty($meds)) {
    die("<span style='color:red;'>Error: No patients or medications found in database. Run schema.sql first.</span>");
}

$conn->begin_transaction();
try {
    foreach ($patients as $pid) {
        // Assign 1-2 random prescriptions per patient
        $numRx = rand(1, 2);
        for ($i = 0; $i < $numRx; $i++) {
            $medId = $meds[array_rand($meds)];
            $date = date('Y-m-d', strtotime('-' . rand(5, 15) . ' days'));
            
            // Randomly decide if verified (70% chance)
            $isV = (rand(1, 100) <= 70) ? 1 : 0;
            $vBy = ($isV) ? 1 : null; 

            $stmt = $conn->prepare("INSERT INTO prescription (patient_id, medication_code_id, dosage, frequency, duration_days, prescribed_date, is_verified, verified_by) VALUES (?, ?, '1 Tablet', 'Once Daily', 30, ?, ?, ?)");
            $stmt->bind_param("iisii", $pid, $medId, $date, $isV, $vBy);
            $stmt->execute();
            $rxId = $conn->insert_id;
            
            // Generate intake logs for the last 5 days
            for ($d = 0; $d < 5; $d++) {
                // 80% chance of 'Taken', 20% 'Missed'
                $status = (rand(1, 100) <= 80) ? 'Taken' : 'Missed';
                $logDate = date('Y-m-d H:i:s', strtotime('-' . $d . ' days ' . rand(8, 10) . ' hours'));
                
                $lStmt = $conn->prepare("INSERT INTO intake_log (prescription_id, intake_datetime, intake_status) VALUES (?, ?, ?)");
                $lStmt->bind_param("iss", $rxId, $logDate, $status);
                $lStmt->execute();
            }
        }
        echo "<li>Populated history for Patient ID: $pid</li>";
    }
    $conn->commit();
    echo "</ul><p style='color:green;'>[✓] Success! Clinical history has been populated.</p>";
} catch (Exception $e) {
    $conn->rollback();
    echo "</ul><p style='color:red;'>[X] Error: " . $e->getMessage() . "</p>";
}
?>
