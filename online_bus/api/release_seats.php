<?php
/**
 * Release Seat Lock API
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$scheduleId = (int)($input['schedule_id'] ?? ($_SESSION['active_seat_lock']['schedule_id'] ?? 0));
$sessionId = session_id();

if ($scheduleId > 0 && $sessionId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM seat_locks WHERE schedule_id = ? AND session_id = ?");
        $stmt->execute([$scheduleId, $sessionId]);
        unset($_SESSION['active_seat_lock']);
        jsonResponse(['success' => true, 'message' => 'Seat lock released successfully.']);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Could not release seat lock.'], 500);
    }
} else {
    jsonResponse(['success' => true]);
}
