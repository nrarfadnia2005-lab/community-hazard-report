<?php
// Registers a new civilian account
require_once '../../config/db.php';
require_once '../../config/firebase.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = cleanInput($data['username'] ?? '');
$email    = cleanInput($data['email'] ?? '');
$password = $data['password'] ?? '';
$district = cleanInput($data['district'] ?? '');

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username, email, and password are required.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
if (!isValidEmail($email)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$dbc = getDB();

$tables = ['civilians' => 'email', 'officers' => 'email', 'admins' => 'email'];
foreach ($tables as $table => $col) {
    $query = $dbc->prepare("SELECT COUNT(*) FROM $table WHERE $col = ?");
    $res = $query->execute([$email]);
    if ($query->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
    exit;
    }
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$query = $dbc->prepare("INSERT INTO civilians (username, email, password, district) VALUES (?, ?, ?, ?)");
$res = $query->execute([$username, $email, $hash, $district]);

$userId = (int) $dbc->lastInsertId();
setSession($userId, 'civilians', $username, $email, $district);

if ($res) {
    try {
        $firestore = getFirebaseFirestore();
        $userData = [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'civilians',
            'district' => $district,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $firestore->collection('users')->document((string)$userId)->set($userData);
    } catch (Exception $e) {
        error_log("Firebase Sync Error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Registration successful!',
    'user' => ['id' => $userId, 'username' => $username, 'email' => $email, 'role' => 'civilians']
]);
    exit;
?>
