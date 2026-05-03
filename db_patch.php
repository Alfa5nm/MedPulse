<?php
require_once 'config/db.php';

function addColumn($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition")) {
            echo "<li style='color:green;'>[✓] Added $column to $table</li>";
        } else {
            echo "<li style='color:red;'>[X] Failed adding $column to $table: " . $conn->error . "</li>";
        }
    } else {
        echo "<li style='color:orange;'>[!] $column already exists in $table</li>";
    }
}

echo "<h2>MedPulse Resilient Patcher</h2><ul>";

addColumn($conn, 'users', 'is_self_registered', "BOOLEAN DEFAULT 0");
addColumn($conn, 'observation', 'is_verified', "BOOLEAN DEFAULT 0");
addColumn($conn, 'observation', 'verified_by', "INT");
addColumn($conn, 'prescription', 'is_verified', "BOOLEAN DEFAULT 0");
addColumn($conn, 'prescription', 'verified_by', "INT");
addColumn($conn, 'patient', 'email', "VARCHAR(100)");

echo "</ul><p>Patching complete.</p>";
?>
