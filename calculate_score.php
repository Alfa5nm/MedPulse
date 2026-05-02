<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$patient_id = intval($_POST['patient_id']);
$temp = floatval($_POST['temperature']);
$spo2 = intval($_POST['oxygen']);
$sys_bp = intval($_POST['systolic_bp']);
$pulse = intval($_POST['pulse']);
$resp = intval($_POST['respiratory']);

$temp_score = 0;
$spo2_score = 0;
$sys_bp_score = 0;
$pulse_score = 0;
$resp_score = 0;

// Logical approximations structure for NEWS2
if ($temp <= 35.0) $temp_score = 3;
elseif ($temp >= 35.1 && $temp <= 36.0) $temp_score = 1;
elseif ($temp >= 36.1 && $temp <= 38.0) $temp_score = 0;
elseif ($temp >= 38.1 && $temp <= 39.0) $temp_score = 1;
elseif ($temp >= 39.1) $temp_score = 2;

if ($spo2 <= 91) $spo2_score = 3;
elseif ($spo2 >= 92 && $spo2 <= 93) $spo2_score = 2;
elseif ($spo2 >= 94 && $spo2 <= 95) $spo2_score = 1;
elseif ($spo2 >= 96) $spo2_score = 0;

if ($sys_bp <= 90) $sys_bp_score = 3;
elseif ($sys_bp >= 91 && $sys_bp <= 100) $sys_bp_score = 2;
elseif ($sys_bp >= 101 && $sys_bp <= 110) $sys_bp_score = 1;
elseif ($sys_bp >= 111 && $sys_bp <= 219) $sys_bp_score = 0;
elseif ($sys_bp >= 220) $sys_bp_score = 3;

if ($pulse <= 40) $pulse_score = 3;
elseif ($pulse >= 41 && $pulse <= 50) $pulse_score = 1;
elseif ($pulse >= 51 && $pulse <= 90) $pulse_score = 0;
elseif ($pulse >= 91 && $pulse <= 110) $pulse_score = 1;
elseif ($pulse >= 111 && $pulse <= 130) $pulse_score = 2;
elseif ($pulse >= 131) $pulse_score = 3;

if ($resp <= 8) $resp_score = 3;
elseif ($resp >= 9 && $resp <= 11) $resp_score = 1;
elseif ($resp >= 12 && $resp <= 20) $resp_score = 0;
elseif ($resp >= 21 && $resp <= 24) $resp_score = 2;
elseif ($resp >= 25) $resp_score = 3;

$consciousness_score = 0; // Hardcoded default, usually assessed by "AVPU" scale.

$total = $temp_score + $spo2_score + $sys_bp_score + $pulse_score + $resp_score + $consciousness_score;

$risk_level = 'Low';
if ($total >= 7 || $temp_score == 3 || $spo2_score == 3 || $sys_bp_score == 3 || $pulse_score == 3 || $resp_score == 3) {
    $risk_level = 'Critical';
} elseif ($total >= 5) {
    $risk_level = 'High';
} elseif ($total >= 3) {
    $risk_level = 'Medium';
}

$conn->begin_transaction();
try {
    $obsStmt = $conn->prepare("INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit) VALUES (?, ?, ?, ?)");
    $now = date('Y-m-d H:i:s');
    
    // Seed LOINC codes mapping: 1=Temp, 2=SpO2, 3=SysBP, 4=Pulse, 5=Resp
    // Temp
    $loinc = 1;
    $unit = 'C';
    $obsStmt->bind_param("iids", $patient_id, $loinc, $temp, $unit);
    $obsStmt->execute();
    
    // SpO2
    $loinc = 2;
    $unit = '%';
    $spoTemp = floatval($spo2);
    $obsStmt->bind_param("iids", $patient_id, $loinc, $spoTemp, $unit);
    $obsStmt->execute();

    // SysBP
    $loinc = 3;
    $unit = 'mmHg';
    $sbpTemp = floatval($sys_bp);
    $obsStmt->bind_param("iids", $patient_id, $loinc, $sbpTemp, $unit);
    $obsStmt->execute();

    // Pulse
    $loinc = 4;
    $unit = 'beats/min';
    $pTemp = floatval($pulse);
    $obsStmt->bind_param("iids", $patient_id, $loinc, $pTemp, $unit);
    $obsStmt->execute();

    // Resp
    $loinc = 5;
    $unit = 'breaths/min';
    $rtemp = floatval($resp);
    $obsStmt->bind_param("iids", $patient_id, $loinc, $rtemp, $unit);
    $obsStmt->execute();

    $hsStmt = $conn->prepare("INSERT INTO healthscore (patient_id, respiratory_score, oxygen_score, systolic_bp_score, pulse_score, temperature_score, consciousness_score, total_score, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $hsStmt->bind_param("iiiiiiiis", $patient_id, $resp_score, $spo2_score, $sys_bp_score, $pulse_score, $temp_score, $consciousness_score, $total, $risk_level);
    $hsStmt->execute();

    $conn->commit();
    header("Location: patient_details.php?id=$patient_id&success=1");
    exit;
} catch (Exception $e) {
    $conn->rollback();
    die("Database error calculating score: " . $e->getMessage());
}
?>
