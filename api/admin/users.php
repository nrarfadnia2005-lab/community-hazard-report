<?php
// Fetches all users grouped by role
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$dbc = getDB();
$type = $_GET['type'] ?? 'all';

$result = [];

if ($type === 'all' || $type === 'citizens') {
    $result['citizens'] = $dbc->query("SELECT user_id, username, email, district, ban_status, strike_count, created_at FROM civilians ORDER BY created_at DESC")->fetchAll();
}
if ($type === 'all' || $type === 'officers') {
    $result['officers'] = $dbc->query("SELECT officer_id, name, email, district, ban_status, created_at FROM officers ORDER BY created_at DESC")->fetchAll();
}
if ($type === 'all' || $type === 'admins') {
    $result['admins'] = $dbc->query("SELECT admin_id, name, email, district, created_at FROM admins ORDER BY created_at DESC")->fetchAll();
}

echo json_encode(['success' => true, 'users' => $result]);
    exit;
?>
