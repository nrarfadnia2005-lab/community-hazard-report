<?php
// Loads saved draft for the report form
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

$dbc = getDB();
$query = $dbc->prepare("SELECT form_data, updated_at FROM drafts WHERE user_id = ?");
$res = $query->execute([getUserId()]);
$draft = $res = $query->fetch();

if ($draft) {
    echo json_encode([
        'success' => true,
        'has_draft' => true,
        'form_data' => json_decode($draft['form_data'], true),
        'saved_at' => $draft['updated_at']
    ]);
    exit;
} else {
    echo json_encode(['success' => true, 'has_draft' => false]);
    exit;
}
?>
