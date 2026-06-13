<?php
// Resets a civilian password using email
require_once '../../config/db.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = cleanInput($data['email'] ?? '');
$newPassword = $data['new_password'] ?? '';

if (empty($email) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Email and new password are required.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$dbc = getDB();

$query = $dbc->prepare("SELECT user_id FROM civilians WHERE email = ? LIMIT 1");
$query->execute([$email]);
$user = $query->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with this email.']);
    exit;
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$query = $dbc->prepare("UPDATE civilians SET password = ? WHERE email = ?");
$query->execute([$hash, $email]);

echo json_encode(['success' => true, 'message' => 'Password updated successfully! You can now log in.']);
exit;
?>
