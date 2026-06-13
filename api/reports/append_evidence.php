<?php
// Appends extra evidence or notes to an existing report
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

$reportId = $_POST['report_id'] ?? '';
$notes    = cleanInput($_POST['notes'] ?? '');

if (empty($reportId)) {
    echo json_encode(['success' => false, 'message' => 'Report ID required.']);
    exit;
}

$dbc = getDB();

$query = $dbc->prepare("SELECT report_id FROM reports WHERE report_id = ? AND status IN ('Received','Investigating')");
$res = $query->execute([$reportId]);
if (!$res = $query->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Report not found or already closed.']);
    exit;
}

$photoFile = handleFileUpload('photo', 'reports');
if (!$photoFile && empty($notes)) {
    echo json_encode(['success' => false, 'message' => 'Provide a photo or description.']);
    exit;
}

$dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, photo_file, notes) VALUES (?, ?, 'civilian', ?, ?)")
   ->execute([$reportId, getUserId(), $photoFile, $notes]);

$updateId = $dbc->lastInsertId();

try {
    $firestore = getFirebaseFirestore();
    $updateData = [
        'update_id' => $updateId,
        'author_id' => getUserId(),
        'author_role' => 'civilian',
        'notes' => $notes,
        'photo_file_local' => $photoFile,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $firestore->collection('reports')->document($reportId)->collection('updates')->document((string)$updateId)->set($updateData);
} catch (Exception $e) {
    error_log("Firebase Sync Error: " . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Evidence appended to report.']);
    exit;
?>
