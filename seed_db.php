<?php
require_once 'config/db.php';

function execute_sql_file($conn, $filename) {
    if (!file_exists($filename)) {
        echo "File $filename not found.<br>";
        return;
    }
    $sql = file_get_contents($filename);
    $queries = explode(';', $sql);
    $success = 0;
    $errors = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if ($conn->query($query)) {
                $success++;
            } else {
                echo "Error executing query: " . $conn->error . "<br>";
                $errors++;
            }
        }
    }
    echo "Executed $filename: $success successes, $errors errors.<br>";
}

echo "<h1>Seeding Database</h1>";
execute_sql_file($conn, 'schema.sql');
execute_sql_file($conn, 'seed_all_regions.sql');
execute_sql_file($conn, 'seed_massive_patients.sql');
echo "<h2>Done seeding!</h2>";
?>
