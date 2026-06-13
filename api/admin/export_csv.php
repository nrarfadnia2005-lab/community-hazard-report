<?php
// Exports reports data as CSV for admin
require_once '../../config/db.php';
require_once '../../includes/session.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireRole('admin');

$dbc = getDB();
$type = $_GET['type'] ?? 'all';

$where = "r.status != 'Draft'";
if ($type === 'verified') {
    $where = "r.metadata_status LIKE 'Verified%' AND r.status != 'Draft'";
} elseif ($type === 'unverified') {
    $where = "r.metadata_status NOT LIKE 'Verified%' AND r.status != 'Draft'";
} elseif ($type === 'deleted') {
    $where = "r.status = 'Deleted'";
}

$rows = $dbc->query("SELECT r.report_id, r.report_subject, r.status, r.hazard_category,
    r.hazard_cause, r.district, r.state, r.priority, r.latitude, r.longitude,
    r.created_at, r.updated_at, c.username as citizen, o.name as officer
    FROM reports r
    LEFT JOIN civilians c ON r.user_id = c.user_id
    LEFT JOIN officers o ON r.officer_id = o.officer_id
    WHERE $where
    ORDER BY r.created_at DESC")->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="hazard_reports_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Report ID','Subject','Status','Category','Cause','District','State',
    'Priority','Latitude','Longitude','Created','Updated','Citizen','Officer']);

foreach ($rows as $row) {
    fputcsv($out, array_values($row));
}
fclose($out);
exit;
?>
