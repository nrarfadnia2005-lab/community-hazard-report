<?php
// Handles officer actions: update status, add note, resolve case
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('officer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$dbc = getDB();
$officerId = getUserId();

$isJson = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
if ($isJson) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$reportId = $data['report_id'] ?? '';
$action   = $data['action'] ?? '';
$notes    = cleanInput($data['notes'] ?? '');
$newStatus = $data['status'] ?? '';

if (empty($reportId)) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required.']);
    exit;
}

$check = $dbc->prepare("SELECT report_id, status FROM reports WHERE report_id = ? AND officer_id = ?");
$check->execute([$reportId, $officerId]);
$report = $check->fetch();

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'This case is not assigned to you.']);
    exit;
}

$photoFile = handleFileUpload('evidence_photo', 'reports');

switch ($action) {

    case 'update_status':
        $allowed = ['Investigating'];
        if (!in_array($newStatus, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status transition.']);
            exit;
        }

        $dbc->prepare("UPDATE reports SET status = ? WHERE report_id = ?")->execute([$newStatus, $reportId]);

        $logNote = "Status updated to: $newStatus";
        if (!empty($notes)) $logNote .= " — $notes";

        $dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, notes, photo_file) VALUES (?, ?, 'officer', ?, ?)")
            ->execute([$reportId, $officerId, $logNote, $photoFile]);
        $updateId = $dbc->lastInsertId();

        syncCaseUpdateToFirebase($reportId, $newStatus, $updateId, $officerId, 'officer', $logNote, $photoFile);

        echo json_encode(['success' => true, 'message' => "Case status updated to $newStatus."]);
        break;

    case 'add_note':
        if (empty($notes) && !$photoFile) {
            echo json_encode(['success' => false, 'message' => 'Please provide notes or upload a photo.']);
            exit;
        }

        $dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, notes, photo_file) VALUES (?, ?, 'officer', ?, ?)")
            ->execute([$reportId, $officerId, $notes, $photoFile]);
        $updateId = $dbc->lastInsertId();

        syncCaseUpdateToFirebase($reportId, null, $updateId, $officerId, 'officer', $notes, $photoFile);

        echo json_encode(['success' => true, 'message' => 'Investigation note added.']);
        break;

    case 'resolve':
        if (empty($notes)) {
            echo json_encode(['success' => false, 'message' => 'Please provide resolution notes describing how the case was resolved.']);
            exit;
        }
        if (!$photoFile) {
            echo json_encode(['success' => false, 'message' => 'An evidence photo must be provided to resolve this case.']);
            exit;
        }

        $dbc->prepare("UPDATE reports SET status = 'Resolved' WHERE report_id = ?")->execute([$reportId]);

        $logNote = " Case Resolved — $notes";
        $dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, notes, photo_file) VALUES (?, ?, 'officer', ?, ?)")
            ->execute([$reportId, $officerId, $logNote, $photoFile]);
        $updateId = $dbc->lastInsertId();

        syncCaseUpdateToFirebase($reportId, 'Resolved', $updateId, $officerId, 'officer', $logNote, $photoFile);

        echo json_encode(['success' => true, 'message' => 'Case has been resolved successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action. Use: update_status, add_note, or resolve.']);
        break;
}

function syncCaseUpdateToFirebase($reportId, $newStatus, $updateId, $authorId, $authorRole, $notes, $photoFile) {
    try {
        $firestore = getFirebaseFirestore();
        if ($newStatus !== null) {
            $firestore->collection('reports')->document($reportId)->update([
                ['path' => 'status', 'value' => $newStatus]
            ]);
        }
        if ($updateId) {
            $updateData = [
                'update_id' => $updateId,
                'author_id' => $authorId,
                'author_role' => $authorRole,
                'notes' => $notes,
                'photo_file_local' => $photoFile,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $firestore->collection('reports')->document($reportId)->collection('updates')->document((string)$updateId)->set($updateData);
        }
    } catch (Exception $e) {
        error_log("Firebase Sync Error: " . $e->getMessage());
    }
}

exit;
?>
