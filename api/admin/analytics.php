<?php
// Returns analytics data for charts and graphs
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$dbc = getDB();

try {
    $byCategory = $dbc->query("SELECT hazard_category, COUNT(*) as count FROM reports 
        WHERE status != 'Draft' GROUP BY hazard_category ORDER BY count DESC")->fetchAll();

    $byDistrict = $dbc->query("SELECT district, COUNT(*) as count FROM reports 
        WHERE status != 'Draft' AND district IS NOT NULL AND district != ''
        GROUP BY district ORDER BY count DESC LIMIT 15")->fetchAll();

    $byMonth = $dbc->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM reports WHERE status != 'Draft' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month ORDER BY month ASC")->fetchAll();

    $officerPerf = $dbc->query("SELECT o.officer_id, o.name, o.district,
        COUNT(DISTINCT r.report_id) as total_cases,
        SUM(CASE WHEN r.status = 'Resolved' THEN 1 ELSE 0 END) as resolved_cases,
        ROUND(AVG(f.score), 1) as avg_rating
        FROM officers o
        LEFT JOIN reports r ON o.officer_id = r.officer_id
        LEFT JOIN feedback f ON r.report_id = f.report_id
        GROUP BY o.officer_id ORDER BY resolved_cases DESC")->fetchAll();

    $byStatus = $dbc->query("SELECT status, COUNT(*) as count FROM reports 
        WHERE status != 'Draft' GROUP BY status")->fetchAll();

    $recentFeedback = $dbc->query("SELECT f.*, r.report_subject, o.name as officer_name
        FROM feedback f
        JOIN reports r ON f.report_id = r.report_id
        LEFT JOIN officers o ON r.officer_id = o.officer_id
        ORDER BY f.created_at DESC LIMIT 10")->fetchAll();

    echo json_encode([
        'success' => true,
        'by_category' => $byCategory,
        'by_district' => $byDistrict,
        'by_month' => $byMonth,
        'by_status' => $byStatus,
        'officer_performance' => $officerPerf,
        'recent_feedback' => $recentFeedback
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
    exit;
}
?>
