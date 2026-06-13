<?php
// Saves report form data as a draft
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided.']);
    exit;
}

$dbc = getDB();
$userId = getUserId();
$formJson = json_encode($data);

$check = $dbc->prepare("SELECT draft_id FROM drafts WHERE user_id = ?");
$check->execute([$userId]);

if ($check->fetch()) {
    $query = $dbc->prepare("UPDATE drafts SET form_data = ?, updated_at = NOW() WHERE user_id = ?");
    $res = $query->execute([$formJson, $userId]);
} else {
    $query = $dbc->prepare("INSERT INTO drafts (user_id, form_data) VALUES (?, ?)");
    $res = $query->execute([$userId, $formJson]);
}

echo json_encode(['success' => true, 'message' => 'Draft saved.']);
    exit;
?>
