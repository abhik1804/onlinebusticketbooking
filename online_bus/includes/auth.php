<?php
/**
 * Authentication and Session Management
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

require_once __DIR__ . '/db.php';

// USER AUTHENTICATION HELPERS
function isUserLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isUserLoggedIn()) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT id, full_name, email, mobile, gender, address, created_at FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function requireUserLogin(string $redirectUrl = ''): void {
    if (!isUserLoggedIn()) {
        $app = require __DIR__ . '/../config/app.php';
        $redirect = $redirectUrl ?: $app['base_url'] . '/user/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        header("Location: {$redirect}");
        exit;
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_mobile'] = $user['mobile'];
}

function logoutUser(): void {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_mobile']);
}

// ADMIN AUTHENTICATION HELPERS
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, role, is_active FROM admins WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

function requireAdminLogin(array $allowedRoles = []): void {
    if (!isAdminLoggedIn()) {
        $app = require __DIR__ . '/../config/app.php';
        header("Location: " . $app['admin_url'] . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!empty($allowedRoles)) {
        $currentRole = $_SESSION['admin_role'] ?? '';
        if (!in_array($currentRole, $allowedRoles, true)) {
            die("Access Denied: You do not have sufficient permissions to view this page.");
        }
    }
}

function loginAdmin(array $admin): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];

    // Update last login
    $db = getDB();
    $stmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$admin['id']]);
}

function logoutAdmin(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email'], $_SESSION['admin_role']);
}
