<?php
// Assigns a report to an officer with priority
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$reportId  = $data['report_id'] ?? '';
$officerId = (int)($data['officer_id'] ?? 0);
$priority  = $data['priority'] ?? null;

if (empty($reportId) || $officerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Report ID and officer ID required.']);
    exit;
}

$dbc = getDB();

$oStmt = $dbc->prepare("SELECT officer_id, name, district FROM officers WHERE officer_id = ? AND ban_status = 0");
$oStmt->execute([$officerId]);
$officer = $oStmt->fetch();
if (!$officer) {
    echo json_encode(['success' => false, 'message' => 'Officer not found or is suspended.']);
    exit;
}

$rStmt = $dbc->prepare("SELECT report_id, status FROM reports WHERE report_id = ?");
$rStmt->execute([$reportId]);
$report = $rStmt->fetch();
if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found.']);
    exit;
}

$sql = "UPDATE reports SET officer_id = ?, admin_id = ?, status = 'Investigating'";
$params = [$officerId, getUserId()];

if ($priority && in_array($priority, ['Low','Medium','High','Critical'])) {
    $sql .= ", priority = ?";
    $params[] = $priority;
}

$sql .= " WHERE report_id = ?";
$params[] = $reportId;

$dbc->prepare($sql)->execute($params);

$dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, notes) VALUES (?, ?, 'admin', ?)")
   ->execute([$reportId, getUserId(), "Case assigned to Officer: {$officer['name']} (District: {$officer['district']})"]);

$updateId = $dbc->lastInsertId();

try {
    $firestore = getFirebaseFirestore();
    
    $updateFields = [
        ['path' => 'officer_id', 'value' => $officerId],
        ['path' => 'admin_id', 'value' => getUserId()],
        ['path' => 'status', 'value' => 'Investigating']
    ];
    if ($priority && in_array($priority, ['Low','Medium','High','Critical'])) {
        $updateFields[] = ['path' => 'priority', 'value' => $priority];
    }
    $firestore->collection('reports')->document($reportId)->update($updateFields);

    $updateData = [
        'update_id' => $updateId,
        'author_id' => getUserId(),
        'author_role' => 'admin',
        'notes' => "Case assigned to Officer: {$officer['name']} (District: {$officer['district']})",
        'created_at' => date('Y-m-d H:i:s')
    ];
    $firestore->collection('reports')->document($reportId)->collection('updates')->document((string)$updateId)->set($updateData);

} catch (Exception $e) {
    error_log("Firebase Sync Error: " . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => "Case assigned to {$officer['name']}."]);
    exit;
?>
