<?php
require_once 'config/db.php';

$sql = [
    "ALTER TABLE observation ADD COLUMN is_verified BOOLEAN DEFAULT 0",
    "ALTER TABLE observation ADD COLUMN verified_by INT",
    "ALTER TABLE prescription ADD COLUMN is_verified BOOLEAN DEFAULT 0",
    "ALTER TABLE prescription ADD COLUMN verified_by INT",
    "ALTER TABLE users ADD COLUMN is_self_registered BOOLEAN DEFAULT 0"
];

echo "<h2>Updating Schema...</h2><ul>";
foreach ($sql as $query) {
    if ($conn->query($query)) {
        echo "<li style='color:green;'>[✓] Success: $query</li>";
    } else {
        echo "<li style='color:red;'>[X] Failed: $query (" . $conn->error . ")</li>";
    }
}
echo "</ul>";
?>
