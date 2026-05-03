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
    
    // 🛡️ CLINICAL AUDIT: Record this deletion before it happens
    $user_id = $_SESSION['user_id'] ?? null;
    $action = 'Delete';
    $ip = $_SERVER['REMOTE_ADDR'];
    $details = "PERMANENT DELETION: Type: $type, ID: $id";
    
    $auditStmt = $conn->prepare("INSERT INTO audit_log (user_id, action_type, target_entity, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $auditStmt->bind_param("ississ", $user_id, $action, $type, $id, $details, $ip);
    $auditStmt->execute();
    $auditStmt->close();

    // Perform Deletion via Prepared Statement
    $delStmt = $conn->prepare("DELETE FROM $table WHERE $pk = ?");
    $delStmt->bind_param("i", $id);
    
    if ($delStmt->execute()) {
        $delStmt->close();
        header("Location: $redirect");
        exit;
    } else {
        die("Error deleting record: " . $conn->error);
    }
}
?>
