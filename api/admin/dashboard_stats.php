<?php
// Returns dashboard stats like report counts and ratings
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$dbc = getDB();

$total = $dbc->query("SELECT COUNT(*) FROM reports WHERE status != 'Draft'")->fetchColumn();
$received = $dbc->query("SELECT COUNT(*) FROM reports WHERE status = 'Received'")->fetchColumn();
$investigating = $dbc->query("SELECT COUNT(*) FROM reports WHERE status = 'Investigating'")->fetchColumn();
$resolved = $dbc->query("SELECT COUNT(*) FROM reports WHERE status = 'Resolved'")->fetchColumn();
$rejected = $dbc->query("SELECT COUNT(*) FROM reports WHERE status = 'Deleted'")->fetchColumn();

$citizens = $dbc->query("SELECT COUNT(*) FROM civilians")->fetchColumn();
$officers = $dbc->query("SELECT COUNT(*) FROM officers")->fetchColumn();

$recent = $dbc->query("SELECT r.report_id, r.report_subject, r.status, r.hazard_category,
    r.district, r.created_at, c.username as citizen_name
    FROM reports r LEFT JOIN civilians c ON r.user_id = c.user_id
    WHERE r.status != 'Draft'
    ORDER BY r.created_at DESC LIMIT 10")->fetchAll();

$avgRating = $dbc->query("SELECT ROUND(AVG(score),1) FROM feedback")->fetchColumn();

echo json_encode([
    'success' => true,
    'stats' => [
        'total_reports' => (int)$total,
        'received' => (int)$received,
        'investigating' => (int)$investigating,
        'resolved' => (int)$resolved,
        'rejected' => (int)$rejected,
        'total_citizens' => (int)$citizens,
        'total_officers' => (int)$officers,
        'avg_rating' => $avgRating ? (float)$avgRating : 0
    ],
    'recent_reports' => $recent
]);
    exit;
?>
