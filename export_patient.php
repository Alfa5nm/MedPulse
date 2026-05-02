<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($patient_id <= 0) {
    die("Invalid Patient ID");
}

// Fetch Patient Info
$pStmt = $conn->prepare("SELECT full_name FROM patient WHERE patient_id = ?");
$pStmt->bind_param("i", $patient_id);
$pStmt->execute();
$pRes = $pStmt->get_result();
$patient = $pRes->fetch_assoc();

if (!$patient) die("Patient not found");

$filename = "MedPulse_Record_" . str_replace(' ', '_', $patient['full_name']) . "_" . date('Ymd') . ".csv";

// Set headers for download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Header Info
fputcsv($output, ['MedPulse Health Record Export']);
fputcsv($output, ['Patient Name', $patient['full_name']]);
fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
fputcsv($output, []); // Empty line

// 1. Diagnosis History
fputcsv($output, ['DIAGNOSIS HISTORY']);
fputcsv($output, ['Date', 'Disease Name', 'ICD Code', 'Notes']);
$dStmt = $conn->prepare("SELECT d.*, i.disease_name, i.icd_code FROM diagnosis d JOIN icd_code i ON d.icd_code_id = i.icd_code_id WHERE d.patient_id = ? ORDER BY d.diagnosis_date DESC");
$dStmt->bind_param("i", $patient_id);
$dStmt->execute();
$dRes = $dStmt->get_result();
while ($row = $dRes->fetch_assoc()) {
    fputcsv($output, [$row['diagnosis_date'], $row['disease_name'], $row['icd_code'], $row['diagnosis_notes']]);
}
fputcsv($output, []);

// 2. Vital Observations
fputcsv($output, ['VITAL OBSERVATIONS (Last 50)']);
fputcsv($output, ['Date/Time', 'Test Name', 'Value', 'Unit']);
$oStmt = $conn->prepare("SELECT o.*, l.test_name, l.unit_name FROM observation o JOIN loinc_code l ON o.loinc_code_id = l.loinc_code_id WHERE o.patient_id = ? ORDER BY o.observation_datetime DESC LIMIT 50");
$oStmt->bind_param("i", $patient_id);
$oStmt->execute();
$oRes = $oStmt->get_result();
while ($row = $oRes->fetch_assoc()) {
    fputcsv($output, [$row['observation_datetime'], $row['test_name'], $row['observation_value'], $row['unit_name'] ?: $row['unit']]);
}
fputcsv($output, []);

// 3. Prescription History
fputcsv($output, ['PRESCRIPTION HISTORY']);
fputcsv($output, ['Date', 'Medication', 'Dosage', 'Frequency', 'Duration (Days)', 'Instructions']);
$rxStmt = $conn->prepare("SELECT p.*, m.medication_name FROM prescription p JOIN medication_code m ON p.medication_code_id = m.medication_code_id WHERE p.patient_id = ? ORDER BY p.prescribed_date DESC");
$rxStmt->bind_param("i", $patient_id);
$rxStmt->execute();
$rxRes = $rxStmt->get_result();
while ($row = $rxRes->fetch_assoc()) {
    fputcsv($output, [$row['prescribed_date'], $row['medication_name'], $row['dosage'], $row['frequency'], $row['duration_days'], $row['instructions']]);
}

fclose($output);
exit;
?>
