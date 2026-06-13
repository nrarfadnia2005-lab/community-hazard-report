<?php
// Tracks a report with its updates and feedback
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

$reportId = $_GET['id'] ?? '';
if (empty($reportId)) {
    echo json_encode(['success' => false, 'message' => 'Report ID is required.']);
    exit;
}

$dbc = getDB();

$query = $dbc->prepare("SELECT r.report_id, r.report_subject, r.status, r.hazard_category,
    r.hazard_cause, r.description_text, r.photo_file, r.latitude, r.longitude,
    r.location_details, r.district, r.state, r.priority, r.metadata_status,
    r.created_at, r.updated_at, o.name as officer_name,
    c.username as citizen_name, c.email as citizen_email
    FROM reports r
    LEFT JOIN officers o ON r.officer_id = o.officer_id
    LEFT JOIN civilians c ON r.user_id = c.user_id
    WHERE r.report_id = ? AND r.status != 'Draft'");
$res = $query->execute([$reportId]);
$report = $res = $query->fetch();

if ($report && $report['status'] === 'Deleted') {
    $report['status'] = 'Rejected';
}

if (!$report) {
    echo json_encode(['success' => false, 'message' => 'Report not found.']);
    exit;
}

$updStmt = $dbc->prepare("SELECT u.notes, u.photo_file, u.created_at, u.author_role
    FROM updates u WHERE u.report_id = ? ORDER BY u.created_at ASC");
$updStmt->execute([$reportId]);
$updates = $updStmt->fetchAll();

$feedback = null;
if ($report['status'] === 'Resolved') {
    $fbStmt = $dbc->prepare("SELECT score, comment, created_at FROM feedback WHERE report_id = ?");
    $fbStmt->execute([$reportId]);
    $feedback = $fbStmt->fetch();
}

echo json_encode([
    'success'  => true,
    'report'   => $report,
    'updates'  => $updates,
    'feedback' => $feedback
]);
    exit;
?>
