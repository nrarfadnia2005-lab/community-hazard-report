<?php
// Checks for nearby duplicate reports before submitting
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$lat = (float)($data['latitude'] ?? 0);
$lng = (float)($data['longitude'] ?? 0);
$category = $data['hazard_category'] ?? '';
$radiusKm = 0.5;

if ($lat == 0 || $lng == 0) {
    echo json_encode(['success' => true, 'duplicates' => []]);
    exit;
}

$dbc = getDB();
$query = $dbc->prepare("SELECT report_id, report_subject, hazard_category, status, latitude, longitude, created_at, photo_file
    FROM reports WHERE status IN ('Received','Investigating') AND latitude IS NOT NULL
    AND hazard_category = ? AND ABS(latitude - ?) < 0.005 AND ABS(longitude - ?) < 0.005");
$res = $query->execute([$category, $lat, $lng]);
$candidates = $res = $query->fetchAll();

$duplicates = [];
foreach ($candidates as $c) {
    $dist = haversineDistance($lat, $lng, (float)$c['latitude'], (float)$c['longitude']);
    if ($dist <= $radiusKm) {
        $c['distance_m'] = round($dist * 1000);
        $duplicates[] = $c;
    }
}

echo json_encode([
    'success' => true,
    'duplicates' => $duplicates,
    'has_duplicates' => count($duplicates) > 0,
    'message' => count($duplicates) > 0
        ? 'Similar reports found nearby. You can append evidence to an existing report instead.'
        : 'No duplicate reports found.'
]);
    exit;
?>
