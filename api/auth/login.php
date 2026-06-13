<?php
// Handles user login for all roles
require_once '../../config/db.php';
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$email    = cleanInput($data['email'] ?? '');
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$dbc = getDB();

$roles = [
    'admin'   => ['table' => 'admins',    'id' => 'admin_id',   'name' => 'name'],
    'officer' => ['table' => 'officers',  'id' => 'officer_id', 'name' => 'name'],
    'civilians' => ['table' => 'civilians', 'id' => 'user_id',    'name' => 'username'],
];

foreach ($roles as $role => $cfg) {
    $query = $dbc->prepare("SELECT * FROM {$cfg['table']} WHERE email = ? LIMIT 1");
    $query->execute([$email]);
    $user = $query->fetch();

    if ($user) {
        if (($role === 'civilians' || $role === 'officer') && !empty($user['ban_status'])) {
            echo json_encode(['success' => false, 'message' => 'Your account has been suspended.']);
            exit;
        }

        if (password_verify($password, $user['password'])) {
            setSession(
                (int)$user[$cfg['id']],
                $role,
                $user[$cfg['name']],
                $user['email'],
                $user['district'] ?? null
            );

            $redirect = match($role) {
                'admin'   => 'admin/admin_dashboard.html',
                'officer' => 'officer/officer_dashboard.html',
                'civilians' => 'civilian/civilian_dashboard.html',
            };

            echo json_encode([
                'success'  => true,
                'message'  => 'Login successful!',
                'redirect' => $redirect,
                'user'     => [
                    'id'       => (int)$user[$cfg['id']],
                    'name'     => $user[$cfg['name']],
                    'email'    => $user['email'],
                    'role'     => $role,
                    'district' => $user['district'] ?? null
                ]
            ]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
exit;
?>
