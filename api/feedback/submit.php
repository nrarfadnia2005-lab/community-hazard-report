<?php
// Submits civilian feedback and rating for a resolved case
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

$data = json_decode(file_get_contents('php://input'), true);
$reportId = $data['report_id'] ?? '';
$score    = (int)($data['score'] ?? 0);
$comment  = cleanInput($data['comment'] ?? '');

if (empty($reportId) || $score < 1 || $score > 5) {
    echo json_encode(['success' => false, 'message' => 'Report ID and score (1-5) required.']);
    exit;
}

$dbc = getDB();

$query = $dbc->prepare("SELECT report_id, officer_id FROM reports WHERE report_id = ? AND user_id = ? AND status = 'Resolved'");
$res = $query->execute([$reportId, getUserId()]);
$report = $res = $query->fetch();

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found or not resolved.']);
    exit;
}

$check = $dbc->prepare("SELECT rating_id FROM feedback WHERE report_id = ? AND user_id = ?");
$check->execute([$reportId, getUserId()]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already rated this case.']);
    exit;
}

$dbc->prepare("INSERT INTO feedback (report_id, officer_id, user_id, score, comment) VALUES (?, ?, ?, ?, ?)")
   ->execute([$reportId, $report['officer_id'], getUserId(), $score, $comment]);

echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
    exit;
?>
