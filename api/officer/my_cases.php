<?php
// Returns cases assigned to the logged-in officer
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('officer');

$dbc = getDB();
$officerId = getUserId();
$status = $_GET['status'] ?? '';

$where = "WHERE r.officer_id = ?";
$params = [$officerId];

if ($status && in_array($status, ['Received','Investigating','Resolved'])) {
    $where .= " AND r.status = ?";
    $params[] = $status;
}

$query = $dbc->prepare("SELECT r.*, c.username as citizen_name, c.email as citizen_email, f.score as feedback_score, f.comment as feedback_comment
    FROM reports r
    LEFT JOIN civilians c ON r.user_id = c.user_id
    LEFT JOIN feedback f ON r.report_id = f.report_id
    $where ORDER BY 
    CASE r.priority WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 ELSE 4 END,
    r.created_at DESC");
$res = $query->execute($params);
$cases = $res = $query->fetchAll();

foreach ($cases as &$case) {
    if ($case['status'] === 'Deleted') {
        $case['status'] = 'Rejected';
    }
}
unset($case);

$counts = $dbc->prepare("SELECT status, COUNT(*) as cnt FROM reports WHERE officer_id = ? GROUP BY status");
$counts->execute([$officerId]);
$statusCounts = [];
foreach ($counts->fetchAll() as $row) {
    $st = $row['status'] === 'Deleted' ? 'Rejected' : $row['status'];
    if (!isset($statusCounts[$st])) {
        $statusCounts[$st] = 0;
    }
    $statusCounts[$st] += (int)$row['cnt'];
}

echo json_encode(['success' => true, 'cases' => $cases, 'status_counts' => $statusCounts]);
    exit;
?>
