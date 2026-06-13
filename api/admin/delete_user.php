<?php
// Deletes a civilian or officer account
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$id   = (int)($data['id'] ?? 0);

if ($type !== 'officer') {
    echo json_encode(['success' => false, 'message' => 'Invalid user type. Only officers can be deleted.']);
    exit;
}
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
    exit;
}

$dbc = getDB();

$dbc->prepare("UPDATE reports SET officer_id = NULL WHERE officer_id = ?")->execute([$id]);
$query = $dbc->prepare("DELETE FROM officers WHERE officer_id = ?");
$res = $query->execute([$id]);

if ($query->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

try {
    $firestore = getFirebaseFirestore();
    $firestore->collection('users')->document((string)$id)->delete();
} catch (Exception $e) {
    error_log("Firebase Sync Error: " . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => ucfirst($type) . ' account deleted.']);
    exit;
?>
