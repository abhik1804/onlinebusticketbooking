<?php
/**
 * Temporary Seat Locking API
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
}

// Read raw JSON input
$input = json_decode(file_get_contents('php://input'), true);
$scheduleId = (int)($input['schedule_id'] ?? 0);
$seats = $input['seats'] ?? [];

if ($scheduleId <= 0 || empty($seats) || !is_array($seats)) {
    jsonResponse(['success' => false, 'message' => 'Please select at least one valid seat.'], 400);
}

// Limit max seats per booking
if (count($seats) > 6) {
    jsonResponse(['success' => false, 'message' => 'You cannot select more than 6 seats in a single booking.'], 400);
}

$db = getDB();
$sessionId = session_id();
$userId = $_SESSION['user_id'] ?? null;
$appConfig = require __DIR__ . '/../config/app.php';
$lockSeconds = $appConfig['seat_lock_seconds'] ?? 600;

try {
    // 1. Clean expired locks first
    cleanExpiredSeatLocks($db);

    // 2. Start transaction
    $db->beginTransaction();

    // 3. Verify schedule exists
    $schedStmt = $db->prepare("SELECT s.id, s.departure_date, s.base_fare, s.sleeper_fare, b.total_seats, b.id as bus_id 
                               FROM schedules s 
                               JOIN buses b ON s.bus_id = b.id 
                               WHERE s.id = ? AND s.status = 'scheduled' FOR UPDATE");
    $schedStmt->execute([$scheduleId]);
    $schedule = $schedStmt->fetch();

    if (!$schedule) {
        $db->rollBack();
        jsonResponse(['success' => false, 'message' => 'Bus schedule not found or no longer active.'], 404);
    }

    $inPlaceholders = implode(',', array_fill(0, count($seats), '?'));

    // 4. Check if any seat is already booked in confirmed / pending bookings
    $bookedSql = "SELECT bp.seat_number 
                  FROM booking_passengers bp 
                  JOIN bookings b ON bp.booking_id = b.id 
                  WHERE b.schedule_id = ? 
                    AND b.booking_status IN ('confirmed', 'pending_payment') 
                    AND bp.seat_number IN ($inPlaceholders)";
    $bookedStmt = $db->prepare($bookedSql);
    $bookedStmt->execute(array_merge([$scheduleId], $seats));
    $alreadyBooked = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($alreadyBooked)) {
        $db->rollBack();
        jsonResponse([
            'success' => false, 
            'message' => 'Seat(s) ' . implode(', ', $alreadyBooked) . ' are already booked. Please choose other seats.'
        ], 409);
    }

    // 5. Check if any seat is blocked by admin
    $blockedSql = "SELECT seat_number FROM blocked_seats WHERE schedule_id = ? AND seat_number IN ($inPlaceholders)";
    $blockedStmt = $db->prepare($blockedSql);
    $blockedStmt->execute(array_merge([$scheduleId], $seats));
    $alreadyBlocked = $blockedStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($alreadyBlocked)) {
        $db->rollBack();
        jsonResponse([
            'success' => false, 
            'message' => 'Seat(s) ' . implode(', ', $alreadyBlocked) . ' are reserved by administration.'
        ], 409);
    }

    // 6. Check if currently locked by ANOTHER session
    $lockCheckSql = "SELECT seat_number FROM seat_locks 
                     WHERE schedule_id = ? 
                       AND seat_number IN ($inPlaceholders) 
                       AND session_id != ? 
                       AND expires_at > NOW() FOR UPDATE";
    $lockCheckStmt = $db->prepare($lockCheckSql);
    $lockCheckStmt->execute(array_merge([$scheduleId], $seats, [$sessionId]));
    $lockedByOther = $lockCheckStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($lockedByOther)) {
        $db->rollBack();
        jsonResponse([
            'success' => false, 
            'message' => 'Seat(s) ' . implode(', ', $lockedByOther) . ' are temporarily held by another passenger. Please select alternative seats.'
        ], 409);
    }

    // 7. Clear prior locks held by THIS session for this schedule
    $delStmt = $db->prepare("DELETE FROM seat_locks WHERE schedule_id = ? AND session_id = ?");
    $delStmt->execute([$scheduleId, $sessionId]);

    // 8. Insert new seat locks with expiry
    $expiresAtTime = time() + $lockSeconds;
    $expiresAtSql = date('Y-m-d H:i:s', $expiresAtTime);

    $insStmt = $db->prepare("INSERT INTO seat_locks (schedule_id, seat_number, session_id, user_id, expires_at) VALUES (?, ?, ?, ?, ?)");
    foreach ($seats as $seatNum) {
        $insStmt->execute([$scheduleId, cleanInput($seatNum), $sessionId, $userId, $expiresAtSql]);
    }

    // Commit Transaction
    $db->commit();

    // Store locked seats state in PHP session for booking verification
    $_SESSION['active_seat_lock'] = [
        'schedule_id' => $scheduleId,
        'seats' => $seats,
        'expires_at' => $expiresAtTime
    ];

    jsonResponse([
        'success' => true,
        'message' => 'Seats locked successfully for 10 minutes.',
        'expires_at' => $expiresAtTime,
        'locked_seats' => $seats
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Seat lock error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'An error occurred while securing seat lock. Please try again.'], 500);
}
