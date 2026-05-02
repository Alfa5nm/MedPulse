<?php
require_once 'config/db.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $conn->real_escape_string($_POST['role'] ?? 'HealthWorker');

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password_hash, $role);
        
        if ($stmt->execute()) {
            $success = "Registration successful! You can now login.";
        } else {
            if ($stmt->errno == 1062) {
                $error = "Email or Username already exists.";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | MedPulse</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="register-card fade-in-up">
        <div class="text-center mb-4">
            <i class="fa-solid fa-user-plus text-primary fs-1 mb-2"></i>
            <h2 class="fw-bold text-dark">Join MedPulse</h2>
            <p class="text-muted">Register as a Health Professional</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small"><?= $error ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success py-2 small"><?= $success ?> <a href="login.php" class="fw-bold">Login here</a></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="johndoe" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Role</label>
                <select name="role" class="form-select">
                    <option value="HealthWorker">Community Health Worker</option>
                    <option value="Doctor">Clinical Doctor</option>
                    <option value="Admin">Administrator</option>
                </select>
            </div>
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-premium py-2 fw-bold shadow-sm">Register</button>
            </div>
            <p class="text-muted small text-center">Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In</a></p>
        </form>
    </div>
</body>
</html>
