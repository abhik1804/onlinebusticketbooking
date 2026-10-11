<?php
/**
 * Shared Utility Functions
 * Shreenathji Travels
 */

defined('IN_APP') or define('IN_APP', true);

require_once __DIR__ . '/db.php';

// Safe HTML Escaping
function e(?string $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Input Sanitizer
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return trim((string)$data);
}

// Format Currency
function formatPrice($amount): string {
    return '₹' . number_format((float)$amount, 2);
}

// Format Date Indian Style: "Sunday, 04 October 2026"
function formatDateIndian(string $dateStr): string {
    try {
        $dt = new DateTime($dateStr);
        return $dt->format('l, d F Y');
    } catch (Exception $e) {
        return $dateStr;
    }
}

// Format Time 12-Hour: "09:30 PM"
function formatTime(string $timeStr): string {
    try {
        $dt = new DateTime($timeStr);
        return $dt->format('h:i A');
    } catch (Exception $e) {
        return $timeStr;
    }
}

// Generate Unique PNR
function generatePNR(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $random = '';
    for ($i = 0; $i < 6; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'ST-' . date('y') . '-' . $random;
}

// Clean Expired Seat Locks
function cleanExpiredSeatLocks(?PDO $db = null): void {
    if (!$db) {
        $db = getDB();
    }
    try {
        $db->exec("DELETE FROM seat_locks WHERE expires_at < NOW()");
    } catch (Exception $e) {
        error_log("Failed to clean seat locks: " . $e->getMessage());
    }
}

// Retrieve Website Settings with caching
function getSetting(string $key, $default = '') {
    static $settings = null;
    if ($settings === null) {
        try {
            $db = getDB();
            $rows = $db->query("SELECT key_name, key_value FROM website_settings")->fetchAll();
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['key_name']] = $r['key_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings[$key] ?? $default;
}

// JSON API Response
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Log Admin Activity
function logAdminActivity(?int $adminId, string $action, ?string $details = null): void {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO admin_activity_logs (admin_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log("Admin activity log error: " . $e->getMessage());
    }
}

// Validate & Process Bus Image Upload
function handleBusImageUpload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error code: ' . $file['error']];
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxBytes) {
        return ['success' => false, 'message' => 'Image size must be less than 5MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowedMimes[$mime])) {
        return ['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, and WebP are allowed.'];
    }

    $ext = $allowedMimes[$mime];
    $filename = 'bus_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/uploads/buses/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file on server.'];
    }

    $relativePath = 'assets/uploads/buses/' . $filename;
    return ['success' => true, 'path' => $relativePath];
}

// Validate External Bus Image URL (SSR Protection)
function validateBusImageUrl(string $url): array {
    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'message' => 'Invalid URL syntax.'];
    }

    $parsed = parse_url($url);
    $scheme = strtolower($parsed['scheme'] ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        return ['valid' => false, 'message' => 'Only HTTP or HTTPS image URLs are supported.'];
    }

    $host = strtolower($parsed['host'] ?? '');
    // Prevent SSRF: block local loopback and private networks
    if ($host === 'localhost' || $host === '127.0.0.1' || str_starts_with($host, '192.168.') || str_starts_with($host, '10.') || str_starts_with($host, '172.')) {
        return ['valid' => false, 'message' => 'Local or internal network URLs are not allowed for security reasons.'];
    }

    return ['valid' => true, 'url' => $url];
}
