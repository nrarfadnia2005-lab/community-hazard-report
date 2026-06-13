<?php
// Creates a new hazard report with photo and GPS verification
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
if ($isJson) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$subject   = cleanInput($data['report_subject'] ?? '');
$category  = cleanInput($data['hazard_category'] ?? '');
$cause     = cleanInput($data['hazard_cause'] ?? '');
$desc      = cleanInput($data['description_text'] ?? '');
$lat       = !empty($data['latitude']) ? (float)$data['latitude'] : null;
$lng       = !empty($data['longitude']) ? (float)$data['longitude'] : null;
$locDetail = cleanInput($data['location_details'] ?? '');
$areaType  = cleanInput($data['area_type'] ?? '');
$district  = cleanInput($data['district'] ?? '');
$state     = cleanInput($data['state'] ?? '');
$deviceLat = !empty($data['device_latitude']) ? (float)$data['device_latitude'] : null;
$deviceLng = !empty($data['device_longitude']) ? (float)$data['device_longitude'] : null;
$isLiveCamera = !empty($data['is_live_camera']) && $data['is_live_camera'] === '1';


if (empty($subject) || empty($category) || empty($desc)) {
    echo json_encode(['success' => false, 'message' => 'Subject, category, and description are required.']);
    exit;
}

$photoFile = handleFileUpload('photo', 'reports');

$metadataStatus = 'Unverified Source';
$trustScore = 0.50;
$photoLat = null;
$photoLng = null;
$maxDistanceThreshold = 5.0; // 5 km radius for verified match

// Identify if the uploaded image originates from social media or is a screenshot
$isSocialMediaOrScreenshot = false;
if (isset($_FILES['photo']['name']) && !empty($_FILES['photo']['name'])) {
    $origName = $_FILES['photo']['name'];
    if (preg_match('/whatsapp|telegram|instagram|facebook|discord|snapchat|twitter|screenshot|[-_]wa\d+/i', $origName)) {
        $isSocialMediaOrScreenshot = true;
    }
}

if ($photoFile && !$isSocialMediaOrScreenshot) {
    $filePath = __DIR__ . '/../../uploads/reports/' . $photoFile;

    // === Step 1: Use Client-Side EXIF GPS ===
    // The JavaScript front-end reads the EXIF via 'exifr' (a highly robust library)
    // BEFORE any browser stripping happens. We prioritize this because PHP's built-in
    // exif_read_data() sometimes miscalculates coordinates on Android photos.
    $clientPhotoLat = isset($data['photo_exif_lat']) && $data['photo_exif_lat'] !== '' ? (float)$data['photo_exif_lat'] : null;
    $clientPhotoLng = isset($data['photo_exif_lng']) && $data['photo_exif_lng'] !== '' ? (float)$data['photo_exif_lng'] : null;

    if ($clientPhotoLat !== null && $clientPhotoLng !== null) {
        $gps = ['lat' => $clientPhotoLat, 'lng' => $clientPhotoLng];
    } else {
        // === Step 2: Fallback to server-side EXIF reading ===
        $gps = getExifGps($filePath);
    }

    // === Step 3: Compare photo EXIF GPS vs pinned map location ===
    if ($gps) {
        $photoLat = $gps['lat'];
        $photoLng = $gps['lng'];

        if ($lat && $lng) {
            $distance = haversineDistance((float)$lat, (float)$lng, $photoLat, $photoLng);
            if ($distance < $maxDistanceThreshold) {
                // Photo GPS is within 5 km of the pinned location — verified
                $metadataStatus = 'Verified Location';
                $trustScore = 1.00;
            } else {
                // Photo GPS is more than 5 km from the pin — mismatch
                // Layer 2 Fallback: If distance fails (Android GPS bug), but it IS a real camera photo, verify it anyway
                $isCameraOriginal = !empty($data['is_camera_original']) && $data['is_camera_original'] === '1';
                $isOriginal = $isCameraOriginal || isOriginalCameraPhoto($filePath);

                if ($isOriginal) {
                    $metadataStatus = 'Verified Camera Photo';
                    $trustScore = 0.85;
                } else {
                    $metadataStatus = 'Mismatched Coordinates';
                    $trustScore = 0.20;
                }
            }
        } else {
            // No pin placed by user — auto-use photo GPS as the location
            $lat = $photoLat;
            $lng = $photoLng;
            $metadataStatus = 'Verified (Auto-GPS)';
            $trustScore = 0.90;
        }
    } else {
        // === Step 4: No photo EXIF GPS at all ===
        // Layer 2: If we couldn't get GPS EXIF, check if it's an original camera photo
        $isCameraOriginal = !empty($data['is_camera_original']) && $data['is_camera_original'] === '1';
        $isOriginal = $isCameraOriginal || isOriginalCameraPhoto($filePath);

        if ($isOriginal) {
            $metadataStatus = 'Verified Camera Photo';
            $trustScore = 0.85;
        } else if ($isLiveCamera && $lat && $lng && $deviceLat && $deviceLng) {
            $distance = haversineDistance((float)$lat, (float)$lng, $deviceLat, $deviceLng);
            if ($distance < $maxDistanceThreshold) {
                $metadataStatus = 'Verified (Live GPS)';
                $trustScore = 0.90;
            } else {
                $metadataStatus = 'Unverified Source';
                $trustScore = 0.40;
            }
        } else {
            $metadataStatus = 'Unverified Source';
            $trustScore = 0.40;
        }
    }
} else {
    $metadataStatus = 'Unverified Source';
    $trustScore = 0.40;
}

$dbc = getDB();
$userId = getUserId();

// Check if user is banned
$userCheck = $dbc->prepare("SELECT ban_status, strike_count FROM civilians WHERE user_id = ?");
$userCheck->execute([$userId]);
$userRow = $userCheck->fetch();

if ($userRow && $userRow['ban_status'] === 'Banned') {
    echo json_encode(['success' => false, 'message' => 'Your account has been banned from submitting reports due to multiple violations.']);
    exit;
}

// Strikes are only given manually by admins when deleting fake reports

$reportId = generateReportId();
$attempts = 0;
while ($attempts < 10) {
    $check = $dbc->prepare("SELECT COUNT(*) FROM reports WHERE report_id = ?");
    $check->execute([$reportId]);
    if ($check->fetchColumn() == 0) break;
    $reportId = generateReportId();
    $attempts++;
}

$query = $dbc->prepare("INSERT INTO reports 
    (report_id, user_id, report_subject, status, photo_file, latitude, longitude, 
     location_details, area_type, district, state, hazard_category, hazard_cause, description_text,
     metadata_status, trust_score, photo_latitude, photo_longitude)
    VALUES (?, ?, ?, 'Received', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$res = $query->execute([
    $reportId, $userId, $subject, $photoFile,
    $lat, $lng, $locDetail, $areaType, $district, $state,
    $category, $cause, $desc,
    $metadataStatus, $trustScore, $photoLat, $photoLng
]);

if ($res) {
    try {
        $firestore = getFirebaseFirestore();
        
        $reportData = [
            'report_id' => $reportId,
            'user_id' => $userId,
            'report_subject' => $subject,
            'status' => 'Received',
            'latitude' => $lat,
            'longitude' => $lng,
            'location_details' => $locDetail,
            'area_type' => $areaType,
            'district' => $district,
            'state' => $state,
            'hazard_category' => $category,
            'hazard_cause' => $cause,
            'description_text' => $desc,
            'metadata_status' => $metadataStatus,
            'trust_score' => $trustScore,
            'photo_file_local' => $photoFile,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $firestore->collection('reports')->document($reportId)->set($reportData);
    } catch (Exception $e) {
        error_log("Firebase Sync Error: " . $e->getMessage());
    }
}

$dbc->prepare("DELETE FROM drafts WHERE user_id = ?")->execute([getUserId()]);

echo json_encode([
    'success'   => true,
    'message'   => 'Report submitted successfully!',
    'report_id' => $reportId,
    'metadata_status' => $metadataStatus,
    'trust_score' => $trustScore
]);
    exit;
?>
