<?php
require_once 'config/db.php';
header('Content-Type: application/json');

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;

if ($parent_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT region_id, region_name FROM region WHERE parent_region_id = $parent_id ORDER BY region_name ASC";
$result = $conn->query($sql);

$regions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $regions[] = $row;
    }
}

echo json_encode($regions);
?>
