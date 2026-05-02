<?php
require_once 'config/db.php';

echo "<h2>MedPulse System Purge</h2>";
echo "<p>Cleaning clinical and patient data...</p><ul>";

$tables = [
    'regionalaggregate',
    'diseasealert',
    'intake_log',
    'prescription',
    'diagnosis',
    'healthscore',
    'observation',
    'response',
    'question',
    'questionnaire_template',
    'users',
    'patient',
    'region'
];

$conn->query("SET FOREIGN_KEY_CHECKS = 0");

foreach ($tables as $table) {
    if ($conn->query("TRUNCATE TABLE $table")) {
        echo "<li>[✓] Cleared table: <strong>$table</strong></li>";
    } else {
        echo "<li>[X] Error clearing $table: " . $conn->error . "</li>";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

echo "</ul><p style='color:green; font-weight:bold;'>Purge Complete! All patient and transactional data has been removed.</p>";
echo "<hr><p><strong>Next Steps:</strong><br>
1. Re-import your regions from <code>seed_all_regions.sql</code>.<br>
2. Go to <a href='register.php'>Registration</a> to create your new Admin account.</p>";
?>
