<?php
// Returns the logged-in user profile data
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit;
}

echo json_encode([
    'success'   => true,
    'logged_in' => true,
    'user' => [
        'id'       => getUserId(),
        'name'     => getUserName(),
        'email'    => $_SESSION['email'] ?? '',
        'role'     => getUserRole(),
        'district' => $_SESSION['district'] ?? '',
        'state'    => $_SESSION['state'] ?? ''
    ]
]);
    exit;
?>
