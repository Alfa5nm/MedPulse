<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$patient_id = intval($_GET['patient_id'] ?? 0); 

if ($id <= 0 || empty($type)) {
    die("Invalid request.");
}


if ($type === 'patient' && !isAdmin()) {
    die("Unauthorized: Only Admins can delete patients.");
}

if (!isDoctor()) {
    die("Unauthorized: You do not have permission to delete records.");
}

$table = '';
$redirect = "patient_details.php?id=$patient_id";

switch ($type) {
    case 'patient':
        $table = 'patient';
        $redirect = "patients.php?success=deleted";
        break;
    case 'diagnosis':
        $table = 'diagnosis';
        break;
    case 'prescription':
        $table = 'prescription';
        break;
    case 'observation':
        $table = 'observation';
        break;
    default:
        die("Unsupported type.");
}

if ($table) {
    $pk = $table . '_id';
    $sql = "DELETE FROM $table WHERE $pk = $id";
    if ($conn->query($sql)) {
        header("Location: $redirect");
        exit;
    } else {
        die("Error deleting record: " . $conn->error);
    }
}
?>
