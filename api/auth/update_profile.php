<?php
// Updates civilian profile info and password
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('civilians');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided.']);
    exit;
}

$name = cleanInput($data['name'] ?? '');
$email = cleanInput($data['email'] ?? '');
$state = cleanInput($data['state'] ?? '');
$district = cleanInput($data['district'] ?? '');
$newPass = $data['new_password'] ?? '';

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

$dbc = getDB();
$userId = getUserId();

$check = $dbc->prepare("SELECT user_id FROM civilians WHERE email = ? AND user_id != ?");
$check->execute([$email, $userId]);
if ($check->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Email is already in use by another account.']);
    exit;
}

if (!empty($newPass)) {
    if (strlen($newPass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }
    $hashed = password_hash($newPass, PASSWORD_BCRYPT);
    $query = $dbc->prepare("UPDATE civilians SET username = ?, email = ?, state = ?, district = ?, password = ? WHERE user_id = ?");
    $query->execute([$name, $email, $state, $district, $hashed, $userId]);
} else {
    $query = $dbc->prepare("UPDATE civilians SET username = ?, email = ?, state = ?, district = ? WHERE user_id = ?");
    $query->execute([$name, $email, $state, $district, $userId]);
}

$_SESSION['user_name'] = $name;
$_SESSION['email'] = $email;
$_SESSION['state'] = $state;
$_SESSION['district'] = $district;

echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
exit;
?>
