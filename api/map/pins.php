<?php
// Returns map pins and active alerts filtered by area
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

$dbc = getDB();
$category = $_GET['category'] ?? '';
$district = $_GET['district'] ?? '';
$state = $_GET['state'] ?? '';

$where = "WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL AND r.status != 'Draft'";
$params = [];

if ($category) {
    $where .= " AND r.hazard_category = ?";
    $params[] = $category;
}
if ($district) {
    $where .= " AND r.district = ?";
    $params[] = $district;
}

$query = $dbc->prepare("SELECT r.report_id, r.report_subject, r.hazard_category, r.status,
    r.latitude, r.longitude, r.district, r.created_at, r.description_text, r.photo_file
    FROM reports r $where ORDER BY r.created_at DESC LIMIT 500");
$res = $query->execute($params);
$pins = $query->fetchAll();

$alertWhere = "WHERE a.status = 'Active'";
$alertParams = [];
$officerId = $_GET['officer_id'] ?? '';

if ($state || $district) {
    if ($officerId) {
        $alertWhere .= " AND (a.state = ? OR a.district = ? OR a.officer_id = ?)";
        $alertParams[] = $state;
        $alertParams[] = $district;
        $alertParams[] = $officerId;
    } else {
        $alertWhere .= " AND (a.state = ? OR a.district = ?)";
        $alertParams[] = $state;
        $alertParams[] = $district;
    }
} else if ($officerId) {
    $alertWhere .= " AND a.officer_id = ?";
    $alertParams[] = $officerId;
}

$alertsQuery = "SELECT a.alert_id, a.alert_subject, a.hazard_category, a.latitude, a.longitude,
    a.location_radius, a.advice_text, a.district, a.state, a.hazard_cause, a.area_type,
    a.location_details, a.photo_file, a.created_at, a.officer_id
    FROM alerts a
    $alertWhere
    ORDER BY a.created_at DESC LIMIT 20";

$alertStmt = $dbc->prepare($alertsQuery);
$alertStmt->execute($alertParams);
$alerts = $alertStmt->fetchAll();


$categories = $dbc->query("SELECT DISTINCT hazard_category FROM reports 
    WHERE hazard_category IS NOT NULL AND hazard_category != '' ORDER BY hazard_category")->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['success' => true, 'pins' => $pins, 'alerts' => $alerts, 'categories' => $categories]);
    exit;
?>
