<?php
// Bans or unbans a civilian user account
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$userId = (int)($data['id'] ?? 0);
$action = $data['action'] ?? ''; // 'ban' or 'unban'

if ($userId <= 0 || !in_array($action, ['ban', 'unban'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$dbc = getDB();
$status = ($action === 'ban') ? 1 : 0;
$statusText = ($action === 'ban') ? 'Banned' : 'Active';

$query = $dbc->prepare("UPDATE civilians SET ban_status = ? WHERE user_id = ?");
$query->execute([$status, $userId]);

if ($query->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'User ' . $statusText . ' successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found or status unchanged.']);
}
?>
