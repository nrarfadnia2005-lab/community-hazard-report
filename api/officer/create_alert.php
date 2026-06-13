<?php
// Publishes a new awareness alert for civilians
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('officer');

$dbc = getDB();
$officerId = getUserId();

$subject   = $_POST['subject'] ?? '';
$category  = $_POST['category'] ?? '';
$district  = $_POST['district'] ?? '';
$state     = $_POST['state'] ?? '';
$advice    = $_POST['advice'] ?? '';
$lat       = $_POST['latitude'] ?? null;
$lng       = $_POST['longitude'] ?? null;
$radius    = $_POST['affected_radius'] ?? '';
$areaType  = $_POST['area_type'] ?? '';
$locDetail = $_POST['location_details'] ?? '';
$cause     = $_POST['hazard_cause'] ?? '';

if (!$subject || !$category || !$advice) {
    echo json_encode(['success' => false, 'message' => 'Subject, category, and advice are required']);
    exit;
}

$photoPath = null;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = 'alert_' . uniqid() . '.' . $ext;
    $targetDir = __DIR__ . '/../../uploads/alerts/';
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $filename)) {
        $photoPath = $filename;
    }
} 

else if (!empty($_POST['existing_photo'])) {
    $existing = basename($_POST['existing_photo']);
    $sourcePath = __DIR__ . '/../../uploads/reports/' . $existing;
    if (file_exists($sourcePath)) {
        $ext = pathinfo($existing, PATHINFO_EXTENSION);
        $filename = 'alert_' . uniqid() . '.' . $ext;
        $targetDir = __DIR__ . '/../../uploads/alerts/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        if (copy($sourcePath, $targetDir . $filename)) {
            $photoPath = $filename;
        }
    }
}

try {

    $query = $dbc->prepare("INSERT INTO alerts (officer_id, alert_subject, hazard_category, district, state, advice_text, photo_file, latitude, longitude, location_radius, area_type, location_details, hazard_cause) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $res = $query->execute([$officerId, $subject, $category, $district, $state, $advice, $photoPath, $lat, $lng, $radius, $areaType, $locDetail, $cause]);
    
    echo json_encode(['success' => true, 'message' => 'Awareness alert published successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>