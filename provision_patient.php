<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isDoctor()) {
    die("Unauthorized.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $email = $conn->real_escape_string($_POST['email']);
    
    
    // 1. Check if patient already has an account
    $check = $conn->prepare("SELECT user_id FROM users WHERE patient_id = ?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: patient_details.php?id=$patient_id&error=account_exists");
        exit;
    }
    
    // 2. Fetch patient name for username generation
    $pStmt = $conn->prepare("SELECT full_name FROM patient WHERE patient_id = ?");
    $pStmt->bind_param("i", $patient_id);
    $pStmt->execute();
    $p = $pStmt->get_result()->fetch_assoc();
    $username = strtolower(str_replace(' ', '', $p['full_name'])) . $patient_id;
    
    // 3. Create Account
    $password_hash = password_hash('patient123', PASSWORD_DEFAULT);
    $role = 'Patient';
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role, patient_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $username, $email, $password_hash, $role, $patient_id);
    
    if ($stmt->execute()) {
        header("Location: patient_details.php?id=$patient_id&account_created=1");
    } else {
        header("Location: patient_details.php?id=$patient_id&error=creation_failed");
    }
    $stmt->close();
}
?>
