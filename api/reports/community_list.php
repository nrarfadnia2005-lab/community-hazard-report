<?php
// Lists community reports from other users in the area
require_once '../../config/db.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');
requireRole('civilians');

$dbc = getDB();
$userId = getUserId();
$district = $_GET['district'] ?? '';

$where = ["r.status IN ('Received','Investigating')"];
$params = [];

$where[] = "r.user_id != ?";
$params[] = $userId;

if ($district) {
    $where[] = "r.district = ?";
    $params[] = $district;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

$query = $dbc->prepare("SELECT r.report_id, r.report_subject, r.hazard_category, r.status, 
    r.district, r.state, r.created_at, r.photo_file, r.description_text, r.location_details,
    c.username as citizen_name
    FROM reports r
    LEFT JOIN civilians c ON r.user_id = c.user_id
    $whereSQL
    ORDER BY r.created_at DESC
    LIMIT 50");
$query->execute($params);
$reports = $query->fetchAll();

echo json_encode([
    'success' => true,
    'reports' => $reports
]);
exit;
?>
