<?php
// Creates a new officer account
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');
requireRole('admin');

$data = json_decode(file_get_contents('php://input'), true);
$name     = cleanInput($data['name'] ?? '');
$email    = cleanInput($data['email'] ?? '');
$password = $data['password'] ?? '';
$district = cleanInput($data['district'] ?? '');

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
    exit;
}
if (!isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$dbc = getDB();

foreach (['civilians', 'officers', 'admins'] as $tbl) {
    $s = $dbc->prepare("SELECT COUNT(*) FROM $tbl WHERE email = ?");
    $s->execute([$email]);
    if ($s->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already in use.']);
    exit;
    }
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$query = $dbc->prepare("INSERT INTO officers (name, email, password, district) VALUES (?, ?, ?, ?)");
$res = $query->execute([$name, $email, $hash, $district]);

$officerId = (int)$dbc->lastInsertId();

if ($res) {
    try {
        $firestore = getFirebaseFirestore();
        $userData = [
            'user_id' => $officerId,
            'username' => $name,
            'email' => $email,
            'role' => 'officers',
            'district' => $district,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $firestore->collection('users')->document((string)$officerId)->set($userData);
    } catch (Exception $e) {
        error_log("Firebase Sync Error: " . $e->getMessage());
    }
}

echo json_encode(['success' => true, 'message' => 'Officer account created.', 'officer_id' => $officerId]);
    exit;
?>
