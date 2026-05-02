<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

if (!isDoctor()) {
    die("Unauthorized.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = intval($_POST['patient_id']);
    $email = $conn->real_escape_string($_POST['email']);
    
    // Fetch patient name for username
    $pRes = $conn->query("SELECT full_name FROM patient WHERE patient_id = $patient_id");
    $p = $pRes->fetch_assoc();
    $username = strtolower(str_replace(' ', '', $p['full_name'])) . $patient_id;
    
    // Default password: patient123
    $password_hash = password_hash('patient123', PASSWORD_DEFAULT);
    $role = 'Patient';
    
    $sql = "INSERT INTO users (username, email, password_hash, role, patient_id) 
            VALUES ('$username', '$email', '$password_hash', '$role', $patient_id)";
    
    if ($conn->query($sql)) {
        header("Location: patient_details.php?id=$patient_id&account_created=1");
    } else {
        die("Error creating account: " . $conn->error);
    }
}
?>
