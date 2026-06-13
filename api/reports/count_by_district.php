<?php
// Counts active reports in a given district
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

$district = $_GET['district'] ?? '';
if (!$district) {
    echo json_encode(['success' => false, 'message' => 'District is required']);
    exit;
}

try {
    $dbc = getDB();
    
    $query = $dbc->prepare("SELECT COUNT(*) FROM reports 
        WHERE district = ? 
        AND status IN ('Received', 'Investigating')");
    $res = $query->execute([$district]);
    $activeCount = $res = $query->fetchColumn();

    echo json_encode([
        'success' => true,
        'district' => $district,
        'active_count' => (int)$activeCount
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
    exit;
}
?>
