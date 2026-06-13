<?php
// Soft-deletes a report and optionally gives strike to user
require_once '../../config/db.php';
require_once '../../includes/session.php';

header('Content-Type: application/json');
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$reportId = $data['report_id'] ?? '';
$giveStrike = $data['give_strike'] ?? false;

if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Report ID required']);
    exit;
}

$dbc = getDB();

try {
    $dbc->beginTransaction();

    $query = $dbc->prepare("UPDATE reports SET status = 'Deleted' WHERE report_id = ?");
    $query->execute([$reportId]);

    if ($giveStrike) {
        $query = $dbc->prepare("SELECT user_id FROM reports WHERE report_id = ?");
        $query->execute([$reportId]);
        $userId = $query->fetchColumn();

        if ($userId) {
            $strikeStmt = $dbc->prepare("UPDATE civilians SET strike_count = strike_count + 1 WHERE user_id = ?");
            $strikeStmt->execute([$userId]);
        }
    }

    $noteStmt = $dbc->prepare("INSERT INTO updates (report_id, author_id, author_role, notes) VALUES (?, ?, 'admin', 'This report has been rejected and marked as deleted by the administrator.')");
    $noteStmt->execute([$reportId, getUserId()]);

    $dbc->commit();
    echo json_encode(['success' => true, 'message' => 'Report deleted successfully']);
} catch (Exception $e) {
    $dbc->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error deleting report']);
}
exit;
?>
