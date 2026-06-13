<?php
// Returns full details of a single report
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireLogin();

$reportId = $_GET['id'] ?? '';
if (!$reportId) {
    echo json_encode(['success' => false, 'message' => 'Report ID required']);
    exit;
}

$dbc = getDB();
$query = $dbc->prepare("SELECT r.*, c.username as citizen_name, o.name as officer_name, f.score as feedback_score, f.comment as feedback_comment
    FROM reports r
    LEFT JOIN civilians c ON r.user_id = c.user_id
    LEFT JOIN officers o ON r.officer_id = o.officer_id
    LEFT JOIN feedback f ON r.report_id = f.report_id
    WHERE r.report_id = ?");
$query->execute([$reportId]);
$report = $query->fetch();

if ($report && $report['status'] === 'Deleted') {
    $report['status'] = 'Rejected';
}

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

echo json_encode(['success' => true, 'report' => $report]);
exit;
?>
