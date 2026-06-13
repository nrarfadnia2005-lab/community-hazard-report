<?php
// Session helpers for login checks and role access
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}
function getUserRole(): ?string { return $_SESSION['role'] ?? null; }
function getUserId(): ?int { return $_SESSION['user_id'] ?? null; }
function getUserName(): ?string { return $_SESSION['user_name'] ?? null; }

function requireLogin(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
}

function requireRole($roles): void {
    requireLogin();
    if (is_string($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }
}

function setSession(int $userId, string $role, string $name, string $email, ?string $district = null): void {
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = $role;
    $_SESSION['user_name'] = $name;
    $_SESSION['email'] = $email;
    $_SESSION['district'] = $district;
    $_SESSION['login_time'] = time();
}

function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
?>
