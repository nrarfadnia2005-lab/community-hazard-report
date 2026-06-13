<?php
// Deletes an awareness alert
require_once '../../includes/session.php';
require_once '../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? '';

if ($role !== 'officer' && $role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$alertId = $input['alert_id'] ?? 0;

if (!$alertId) {
    echo json_encode(['success' => false, 'message' => 'Missing alert ID']);
    exit;
}

try {
    $dbc = getDB();
    $stmt = $dbc->prepare("DELETE FROM alerts WHERE alert_id = ?");
    $stmt->execute([$alertId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Alert deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Alert not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
