<?php
// Logs the user out and destroys session
require_once '../../includes/session.php';
require_once '../../includes/helpers.php';
header('Content-Type: application/json');
destroySession();
echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
?>
