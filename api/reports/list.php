<?php
// Returns paginated reports filtered by role and status
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireLogin();

$dbc = getDB();
$role = getUserRole();
$userId = getUserId();

$status   = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$district = $_GET['district'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = min(50, max(5, (int)($_GET['limit'] ?? 20)));
$offset   = ($page - 1) * $limit;

$where = [];
$params = [];

if ($role === 'civilians') {
    $where[] = "r.user_id = ?";
    $params[] = $userId;
} elseif ($role === 'officer') {
    $where[] = "r.officer_id = ?";
    $params[] = $userId;
}

if ($status) { $where[] = "r.status = ?"; $params[] = $status; }
if ($category) { $where[] = "r.hazard_category = ?"; $params[] = $category; }
if ($district) { $where[] = "r.district = ?"; $params[] = $district; }

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $dbc->prepare("SELECT COUNT(*) FROM reports r $whereSQL");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = "SELECT r.*, c.username as citizen_name, o.name as officer_name, f.score as feedback_score, f.comment as feedback_comment
        FROM reports r
        LEFT JOIN civilians c ON r.user_id = c.user_id
        LEFT JOIN officers o ON r.officer_id = o.officer_id
        LEFT JOIN feedback f ON r.report_id = f.report_id
        $whereSQL
        ORDER BY r.created_at DESC
        LIMIT $limit OFFSET $offset";

$query = $dbc->prepare($sql);
$res = $query->execute($params);
$reports = $res = $query->fetchAll();

foreach ($reports as &$r) {
    if ($r['status'] === 'Deleted') {
        $r['status'] = 'Rejected';
    }
}
unset($r);

echo json_encode([
    'success' => true,
    'reports' => $reports,
    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)]
]);
    exit;
?>
