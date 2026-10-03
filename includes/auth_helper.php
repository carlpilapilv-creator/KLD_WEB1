<?php
/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Session & Auth Helper Functions — DATABASE-BACKED
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. All authentication checks are validated against the `users` table.
 * 2. Passwords are verified using password_verify() against bcrypt hashes.
 * 3. isLoggedIn() re-reads the DB every request to catch archived/changed users.
 * 4. CSRF tokens protect all POST forms. Session has 30-min inactivity timeout.
 * 5. Audit logging on login_success, login_failed, logout.
 */

// ─── Session Configuration (before session_start) ───────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // Set true when HTTPS is enabled
        'httponly'  => true,
        'samesite'  => 'Lax'
    ]);
    session_start();
}

// Include database connection
require_once __DIR__ . '/db_connect.php';

// ─── Inactivity Timeout (30 minutes) ────────────────────────────────────────
define('SESSION_TIMEOUT', 1800);

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        // Session expired — destroy completely
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        // Restart a clean session for flash messages / CSRF
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// ─── CSRF Helpers ───────────────────────────────────────────────────────────

/**
 * Create or return the per-session CSRF token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the CSRF token from a POST request.
 * Returns true if valid, false otherwise.
 */
function csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return $token !== '' && hash_equals(csrf_token(), $token);
}

// ─── Flash Message Helpers ──────────────────────────────────────────────────

/**
 * Store a one-time flash message in session.
 */
function setFlash(string $type, string $text): void {
    $_SESSION['flash'] = [
        'type' => $type,
        'text' => $text,
    ];
}

/**
 * Retrieve and clear the flash message for the next GET request.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Email Domain Validation ────────────────────────────────────────────────

/**
 * Check if an email address belongs to the @kld.edu.ph domain.
 * Uses FILTER_VALIDATE_EMAIL then compares the domain after the LAST "@".
 */
function isKldEmail(string $email): bool {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $atPos = strrpos($email, '@');
    $domain = substr($email, $atPos + 1);
    return $domain === 'kld.edu.ph';
}

// ─── Safe Redirect ──────────────────────────────────────────────────────────

/**
 * Validate a redirect target: must be a local relative .php path.
 * Anything else falls back to main.php.
 */
function safeRedirect(string $target): string {
    $target = trim($target);
    // Must end in .php, must not contain ://, must not start with // or \
    if (
        $target !== ''
        && preg_match('/^[a-zA-Z0-9_\-\/]+\.php(\?[^\s]*)?$/', $target)
        && strpos($target, '://') === false
        && strpos($target, '//') !== 0
        && strpos($target, '\\') === false
    ) {
        return $target;
    }
    return 'main.php';
}

// ─── Audit Logging ──────────────────────────────────────────────────────────

/**
 * Write a row to the audit_logs table.
 */
function auditLog(?int $userId, string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    dbExecute(
        "INSERT INTO `audit_logs` (`user_id`, `action`, `ip_address`) VALUES (?, ?, ?)",
        'iss',
        [$userId, $action, $ip]
    );
}

// ─── Session / Auth ─────────────────────────────────────────────────────────

/**
 * Check if a user is currently logged in.
 * Re-reads the user from the DB once per request (static cache).
 * If the user is archived or missing, destroys the session.
 */
function isLoggedIn(): bool {
    static $checked = false;
    static $valid   = false;

    if ($checked) {
        return $valid;
    }
    $checked = true;

    if (!isset($_SESSION['user']['id'])) {
        $valid = false;
        return false;
    }

    $userId = (int) $_SESSION['user']['id'];
    $dbUser = dbFetchOne(
        "SELECT * FROM `users` WHERE `id` = ? LIMIT 1",
        'i',
        [$userId]
    );

    // User deleted or archived → destroy session
    if (!$dbUser || $dbUser['archived_at'] !== null) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        session_start();
        $valid = false;
        return false;
    }

    // Refresh session copy (role/access_level changes apply without re-login)
    unset($dbUser['password_hash']);
    $_SESSION['user'] = $dbUser;
    $valid = true;
    return true;
}

/**
 * Get the current authenticated user's data from session.
 * Falls back to a guest profile if not logged in.
 */
function getCurrentUser(): array {
    if (isLoggedIn()) {
        return $_SESSION['user'];
    }
    return [
        'id'           => 0,
        'id_number'    => 'GUEST',
        'fullname'     => 'Guest User',
        'email'        => '',
        'role'         => 'Guest',
        'access_level' => 'user',
        'department'   => '',
        'contact'      => ''
    ];
}

/**
 * Enforce login requirement. Redirect to login.php if not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Attempt to authenticate a user with email/ID and password.
 * Rejects archived users with the same generic message.
 * @return array|null  User row on success, null on failure
 */
function authenticateUser(string $identifier, string $password): ?array {
    // Try matching by email OR id_number
    $user = dbFetchOne(
        "SELECT * FROM `users` WHERE `email` = ? OR `id_number` = ? LIMIT 1",
        'ss',
        [$identifier, $identifier]
    );

    // Reject if not found, archived, or wrong password
    if (!$user || $user['archived_at'] !== null || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    // Don't store the hash in session
    unset($user['password_hash']);
    return $user;
}

/**
 * Register a new user in the database.
 * Enforces @kld.edu.ph email domain. Role whitelist: Student, Faculty, Student Organization.
 * access_level is always 'user'. Uses PASSWORD_BCRYPT.
 * @return array  ['success' => bool, 'message' => string, 'user' => array|null]
 */
function registerUser(array $data): array {
    // Trim inputs (no htmlspecialchars before saving)
    $email      = strtolower(trim($data['email'] ?? ''));
    $idNumber   = trim($data['id_number'] ?? '');
    $fullname   = trim($data['fullname'] ?? '');
    $contact    = trim($data['contact'] ?? '');
    $department = trim($data['department'] ?? '');
    $role       = $data['role'] ?? 'Student';

    // Whitelist role — never accept Admin
    $allowedRoles = ['Student', 'Faculty', 'Student Organization'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'Student';
    }

    // Enforce @kld.edu.ph domain
    if (!isKldEmail($email)) {
        return ['success' => false, 'message' => 'Only @kld.edu.ph institutional email addresses are allowed.', 'user' => null];
    }

    // Check for duplicate email
    $existing = dbFetchOne("SELECT `id` FROM `users` WHERE `email` = ? LIMIT 1", 's', [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'An account with this email already exists.', 'user' => null];
    }

    // Check for duplicate ID number
    if ($idNumber !== '') {
        $existingId = dbFetchOne("SELECT `id` FROM `users` WHERE `id_number` = ? LIMIT 1", 's', [$idNumber]);
        if ($existingId) {
            return ['success' => false, 'message' => 'An account with this ID number already exists.', 'user' => null];
        }
    }

    $password = $data['password'] ?? '';
    if (!isStrongPassword($password)) {
        return [
            'success' => false,
            'message' => 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.',
            'user' => null
        ];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ok = dbExecute(
        "INSERT INTO `users` (`id_number`, `fullname`, `email`, `contact`, `department`, `role`, `access_level`, `password_hash`)
         VALUES (?, ?, ?, ?, ?, ?, 'user', ?)",
        'sssssss',
        [$idNumber, $fullname, $email, $contact, $department, $role, $hash]
    );

    if ($ok !== false) {
        $userId = dbLastId();
        $user = dbFetchOne("SELECT * FROM `users` WHERE `id` = ? LIMIT 1", 'i', [$userId]);
        if ($user) unset($user['password_hash']);
        // Initialize default preferences
        dbExecute("INSERT IGNORE INTO `user_preferences` (`user_id`) VALUES (?)", 'i', [$userId]);
        return ['success' => true, 'message' => 'Registration successful!', 'user' => $user];
    }

    return ['success' => false, 'message' => 'Registration failed. Please try again.', 'user' => null];
}

/**
 * Get all reservations for a specific user from the database.
 * Excludes archived reservations.
 */
function getUserReservations(int $userId): array {
    return dbFetchAll(
        "SELECT r.*, u.fullname AS applicant, u.role, u.department AS dept
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         WHERE r.user_id = ?
           AND r.archived_at IS NULL
         ORDER BY r.created_at DESC",
        'i',
        [$userId]
    );
}

/**
 * Get ALL reservations (for admin views).
 * Excludes archived reservations.
 */
function getAllReservations(): array {
    return dbFetchAll(
        "SELECT r.*, u.fullname AS applicant, u.role, u.department AS dept
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         WHERE r.archived_at IS NULL
         ORDER BY r.created_at DESC"
    );
}

/**
 * Get booked slots for public/calendar conflict detection without leaking private data.
 * Returns only facility_id, booking_date, time_slot for active future bookings.
 */
function getBookedSlots(): array {
    return dbFetchAll(
        "SELECT `facility_id`, `booking_date`, `time_slot`
         FROM `reservations`
         WHERE `status` IN ('Pending', 'Approved')
           AND `archived_at` IS NULL
           AND `booking_date` >= CURDATE()
         ORDER BY `booking_date` ASC"
    );
}

/**
 * Count working days (Mon-Fri) between two dates.
 * Evaluates days strictly before $toDate starting from $fromDate.
 */
function countWorkingDays(string $fromDate, string $toDate): int {
    $from = new DateTime($fromDate);
    $to   = new DateTime($toDate);
    if ($from >= $to) {
        return 0;
    }
    $workingDays = 0;
    $curr = clone $from;
    while ($curr < $to) {
        $dayOfWeek = (int) $curr->format('N'); // 1 (Mon) to 7 (Sun)
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            $workingDays++;
        }
        $curr->modify('+1 day');
    }
    return $workingDays;
}

/**
 * Parse a time string (e.g. "08:00 AM", "05:00 PM") into minutes since midnight.
 */
function parseTimeToMinutes(string $timeStr): ?int {
    $timeStr = trim($timeStr);
    $d = DateTime::createFromFormat('h:i A', $timeStr);
    if (!$d) {
        $d = DateTime::createFromFormat('g:i A', $timeStr);
    }
    if (!$d) {
        return null;
    }
    return ((int)$d->format('H')) * 60 + ((int)$d->format('i'));
}

/**
 * Check whether a requested slot conflicts with existing active reservations for the facility and date.
 * Conflict formula: new_start < existing_end + B AND new_end + B > existing_start (B = slot_buffer_hrs * 60).
 */
function hasReservationConflict(string $facilityId, string $bookingDate, string $timeSlot, int $bufferHrs = 1, ?string $excludeResId = null): bool {
    $slotParts = explode('-', $timeSlot);
    if (count($slotParts) !== 2) {
        return true;
    }
    $newStart = parseTimeToMinutes($slotParts[0]);
    $newEnd   = parseTimeToMinutes($slotParts[1]);
    if ($newStart === null || $newEnd === null || $newStart >= $newEnd) {
        return true;
    }

    $sql = "SELECT `id`, `time_slot` FROM `reservations`
            WHERE `facility_id` = ?
              AND `booking_date` = ?
              AND `status` IN ('Pending', 'Approved')
              AND `archived_at` IS NULL";
    $types = 'ss';
    $params = [$facilityId, $bookingDate];

    if ($excludeResId !== null) {
        $sql .= " AND `id` != ?";
        $types .= 's';
        $params[] = $excludeResId;
    }

    $existing = dbFetchAll($sql, $types, $params);
    if (empty($existing)) {
        return false;
    }

    $b = $bufferHrs * 60;

    foreach ($existing as $row) {
        $parts = explode('-', $row['time_slot']);
        if (count($parts) !== 2) {
            continue;
        }
        $exStart = parseTimeToMinutes($parts[0]);
        $exEnd   = parseTimeToMinutes($parts[1]);
        if ($exStart === null || $exEnd === null) {
            continue;
        }

        if ($newStart < ($exEnd + $b) && ($newEnd + $b) > $exStart) {
            return true;
        }
    }

    return false;
}

/**
 * Get all official facilities from the database.
 */
function getAllFacilities(): array {
    return dbFetchAll("SELECT * FROM `facilities` ORDER BY `category`, `name`");
}

/**
 * Get a single facility by its ID/slug.
 */
function getFacilityById(string $id): ?array {
    return dbFetchOne("SELECT * FROM `facilities` WHERE `id` = ? LIMIT 1", 's', [$id]);
}

/**
 * Create a new reservation in the database with transaction locking, conflict verification, and retry.
 * @return string  The reservation ID on success, or failure reason code string on failure
 */
function createReservation(int $userId, array $data) {
    dbBegin();
    try {
        // Lock the facility row
        $fac = dbFetchOne("SELECT `id` FROM `facilities` WHERE `id` = ? FOR UPDATE", 's', [$data['facility_id']]);
        if (!$fac) {
            dbRollback();
            return 'facility';
        }

        // Server-side conflict verification
        $sysConfig = getSystemConfig();
        $bufferHrs = (int)($sysConfig['slot_buffer_hrs'] ?? 1);
        if (hasReservationConflict($data['facility_id'], $data['booking_date'], $data['time_slot'], $bufferHrs)) {
            dbRollback();
            return 'conflict';
        }

        $year = date('Y');
        $prefix = "KLD-RES-{$year}-%";
        $inserted = false;
        $resId = '';

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $maxRow = dbFetchOne(
                "SELECT MAX(CAST(SUBSTRING_INDEX(`id`, '-', -1) AS UNSIGNED)) AS `max_suffix`
                 FROM `reservations`
                 WHERE `id` LIKE ?",
                's',
                [$prefix]
            );
            $nextSuffix = ((int)($maxRow['max_suffix'] ?? 0)) + 1 + $attempt;
            $resId = 'KLD-RES-' . $year . '-' . str_pad((string)$nextSuffix, 3, '0', STR_PAD_LEFT);

            try {
                $ok = dbExecute(
                    "INSERT INTO `reservations` (`id`,`user_id`,`facility_id`,`facility_name`,`event_name`,`purpose`,`booking_date`,`time_slot`,`attendees`,`equipment`,`status`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')",
                    'sissssssis',
                    [
                        $resId,
                        $userId,
                        $data['facility_id'],
                        $data['facility_name'],
                        $data['event_name'] ?? '',
                        $data['purpose'] ?? '',
                        $data['booking_date'],
                        $data['time_slot'],
                        (int)($data['attendees'] ?? 0),
                        $data['equipment'] ?? 'Standard facility amenities',
                    ]
                );

                if ($ok !== false && $ok > 0) {
                    $inserted = true;
                    break;
                }
                global $conn;
                if ($conn && $conn->errno === 1062) {
                    continue;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    continue;
                }
                throw $e;
            }
        }

        if (!$inserted) {
            dbRollback();
            return 'id_generation_failed';
        }

        dbCommit();
        return $resId;
    } catch (Throwable $e) {
        dbRollback();
        error_log('createReservation database error: ' . $e->getMessage());
        return 'db_error';
    }
}

/**
 * Check if a reservation can be cancelled by the user.
 * Allowed only for owner's Pending or Approved reservation whose start time is at least 24 hours away.
 */
function canCancelReservation(array $res, ?int $userId = null): bool {
    if (!in_array($res['status'] ?? '', ['Pending', 'Approved'], true)) {
        return false;
    }
    if (!empty($res['archived_at'])) {
        return false;
    }
    if ($userId !== null && (int)($res['user_id'] ?? 0) !== $userId) {
        return false;
    }
    $parts = explode('-', $res['time_slot'] ?? '');
    if (empty($parts[0]) || empty($res['booking_date'])) {
        return false;
    }
    $startTimeStr = trim($parts[0]);
    $startTs = strtotime($res['booking_date'] . ' ' . $startTimeStr);
    if ($startTs === false) {
        return false;
    }
    return ($startTs - time()) >= 86400;
}

/**
 * Cancel a reservation by ID, only if the user owns it and meets cancellation policy.
 */
function cancelReservation(string $resId, int $userId): bool {
    if (!preg_match('/^KLD-RES-\d{4}-\d{3,}$/', $resId)) {
        return false;
    }

    $res = dbFetchOne(
        "SELECT * FROM `reservations` WHERE `id` = ? AND `user_id` = ? AND `archived_at` IS NULL LIMIT 1",
        'si',
        [$resId, $userId]
    );

    if (!$res || !canCancelReservation($res, $userId)) {
        return false;
    }

    $affected = dbExecute(
        "UPDATE `reservations` SET `status` = 'Cancelled' WHERE `id` = ? AND `user_id` = ?",
        'si',
        [$resId, $userId]
    );

    return $affected !== false && $affected > 0;
}

/**
 * Archive a user's own reservation (Cancelled, Rejected, or Completed).
 * Returns true only if one row changed.
 */
function archiveReservation(string $resId, int $userId): bool {
    if (!preg_match('/^KLD-RES-\d{4}-\d{3,}$/', $resId)) {
        return false;
    }

    try {
        $affected = dbExecute(
            "UPDATE `reservations`
             SET `archived_at` = NOW()
             WHERE `id` = ?
               AND `user_id` = ?
               AND `status` IN ('Cancelled', 'Rejected', 'Completed')
               AND `archived_at` IS NULL",
            'si',
            [$resId, $userId]
        );
        return $affected === 1;
    } catch (Throwable $e) {
        error_log('archiveReservation error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user has administrative privileges.
 * True when role === 'Admin' OR access_level IN ('admin', 'superadmin').
 */
function isPrivilegedUser(array $user): bool {
    return (($user['role'] ?? '') === 'Admin') || in_array($user['access_level'] ?? '', ['admin', 'superadmin'], true);
}

/**
 * Get pending reservations for admin review.
 * Returns only display fields, ordered by created_at ASC (FIFO).
 */
function getPendingReservations(): array {
    return dbFetchAll(
        "SELECT r.id, r.user_id, u.fullname AS requester_name, r.facility_name,
                r.event_name, r.purpose, r.booking_date, r.time_slot,
                r.attendees, r.created_at
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         WHERE r.status = 'Pending'
           AND r.archived_at IS NULL
         ORDER BY r.created_at ASC"
    );
}

/**
 * Approve a pending reservation.
 * @return array ['success' => bool, 'message' => string]
 */
function approveReservation(string $resId, int $adminId): array {
    if (!preg_match('/^KLD-RES-\d{4}-\d{3,}$/', $resId)) {
        return ['success' => false, 'message' => 'Invalid reservation ID format.'];
    }

    $adminUser = dbFetchOne(
        "SELECT `id`, `role`, `access_level`, `archived_at` FROM `users` WHERE `id` = ? LIMIT 1",
        'i',
        [$adminId]
    );
    if (!$adminUser || !empty($adminUser['archived_at']) || !isPrivilegedUser($adminUser)) {
        error_log('approveReservation refused: admin ' . $adminId . ' failed privilege check');
        return ['success' => false, 'message' => 'You do not have permission to review reservations.'];
    }

    $existingRes = dbFetchOne(
        "SELECT `facility_id`, `user_id`, `status`, `archived_at` FROM `reservations` WHERE `id` = ? LIMIT 1",
        's',
        [$resId]
    );
    if (!$existingRes) {
        error_log('approveReservation refused: reservation ' . $resId . ' not found');
        return ['success' => false, 'message' => 'Reservation not found.'];
    }
    if ((int)$existingRes['user_id'] === $adminId) {
        error_log('approveReservation refused: admin ' . $adminId . ' tried to approve own reservation ' . $resId);
        return ['success' => false, 'message' => 'You cannot review your own request.'];
    }
    if ($existingRes['status'] !== 'Pending' || !empty($existingRes['archived_at'])) {
        error_log('approveReservation refused: reservation ' . $resId . ' status=' . $existingRes['status'] . ' archived=' . ($existingRes['archived_at'] ?? 'null'));
        return ['success' => false, 'message' => 'This request was already reviewed.'];
    }

    dbBegin();
    try {
        $fac = dbFetchOne("SELECT `id` FROM `facilities` WHERE `id` = ? FOR UPDATE", 's', [$existingRes['facility_id']]);
        if (!$fac) {
            dbRollback();
            return ['success' => false, 'message' => 'Facility not found.'];
        }

        $res = dbFetchOne("SELECT * FROM `reservations` WHERE `id` = ?", 's', [$resId]);
        if (!$res) {
            dbRollback();
            return ['success' => false, 'message' => 'Reservation not found.'];
        }

        if ((int)$res['user_id'] === $adminId) {
            dbRollback();
            return ['success' => false, 'message' => 'You cannot review your own request.'];
        }

        if ($res['status'] !== 'Pending' || !empty($res['archived_at'])) {
            dbRollback();
            return ['success' => false, 'message' => 'This request was already reviewed.'];
        }

        $parts = explode('-', $res['time_slot'] ?? '');
        $startTimeStr = trim($parts[0] ?? '');
        $startTs = (!empty($startTimeStr) && !empty($res['booking_date']))
            ? strtotime($res['booking_date'] . ' ' . $startTimeStr)
            : false;

        if ($startTs === false || $startTs <= time()) {
            dbRollback();
            error_log('approveReservation refused: reservation ' . $resId . ' time already passed (startTs=' . ($startTs ?: 'false') . ' now=' . time() . ')');
            return ['success' => false, 'message' => 'This request is for a time that has already started.'];
        }

        $sysConfig = getSystemConfig();
        $bufferHrs = (int)($sysConfig['slot_buffer_hrs'] ?? 1);
        if (hasReservationConflict($res['facility_id'], $res['booking_date'], $res['time_slot'], $bufferHrs, $resId)) {
            dbRollback();
            error_log('approveReservation refused: reservation ' . $resId . ' conflicts with another booking on ' . $res['booking_date'] . ' ' . $res['time_slot']);
            return ['success' => false, 'message' => 'This time slot conflicts with another reservation.'];
        }

        $affected = dbExecute(
            "UPDATE `reservations`
             SET `status` = 'Approved',
                 `reviewed_by` = ?,
                 `reviewed_at` = NOW(),
                 `review_note` = NULL
             WHERE `id` = ?
               AND `status` = 'Pending'
               AND `archived_at` IS NULL",
            'is',
            [$adminId, $resId]
        );

        if ($affected === 1) {
            dbCommit();
            return ['success' => true, 'message' => 'Reservation approved successfully.'];
        }

        dbRollback();
        return ['success' => false, 'message' => 'Failed to approve reservation.'];
    } catch (Throwable $e) {
        dbRollback();
        error_log('approveReservation error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred while approving the reservation.'];
    }
}

/**
 * Reject a pending reservation.
 * @return array ['success' => bool, 'message' => string]
 */
function rejectReservation(string $resId, int $adminId, string $note = ''): array {
    if (!preg_match('/^KLD-RES-\d{4}-\d{3,}$/', $resId)) {
        return ['success' => false, 'message' => 'Invalid reservation ID format.'];
    }

    $adminUser = dbFetchOne(
        "SELECT `id`, `role`, `access_level`, `archived_at` FROM `users` WHERE `id` = ? LIMIT 1",
        'i',
        [$adminId]
    );
    if (!$adminUser || !empty($adminUser['archived_at']) || !isPrivilegedUser($adminUser)) {
        return ['success' => false, 'message' => 'You do not have permission to review reservations.'];
    }

    $existingRes = dbFetchOne(
        "SELECT `facility_id`, `user_id`, `status`, `archived_at` FROM `reservations` WHERE `id` = ? LIMIT 1",
        's',
        [$resId]
    );
    if (!$existingRes) {
        return ['success' => false, 'message' => 'Reservation not found.'];
    }
    if ((int)$existingRes['user_id'] === $adminId) {
        return ['success' => false, 'message' => 'You cannot review your own request.'];
    }
    if ($existingRes['status'] !== 'Pending' || !empty($existingRes['archived_at'])) {
        return ['success' => false, 'message' => 'This request was already reviewed.'];
    }

    $trimmedNote = trim($note);
    $reviewNote  = $trimmedNote !== '' ? mb_substr($trimmedNote, 0, 255) : null;

    dbBegin();
    try {
        $fac = dbFetchOne("SELECT `id` FROM `facilities` WHERE `id` = ? FOR UPDATE", 's', [$existingRes['facility_id']]);
        if (!$fac) {
            dbRollback();
            return ['success' => false, 'message' => 'Facility not found.'];
        }

        $res = dbFetchOne("SELECT * FROM `reservations` WHERE `id` = ?", 's', [$resId]);
        if (!$res) {
            dbRollback();
            return ['success' => false, 'message' => 'Reservation not found.'];
        }

        if ((int)$res['user_id'] === $adminId) {
            dbRollback();
            return ['success' => false, 'message' => 'You cannot review your own request.'];
        }

        if ($res['status'] !== 'Pending' || !empty($res['archived_at'])) {
            dbRollback();
            return ['success' => false, 'message' => 'This request was already reviewed.'];
        }

        $affected = dbExecute(
            "UPDATE `reservations`
             SET `status` = 'Rejected',
                 `reviewed_by` = ?,
                 `reviewed_at` = NOW(),
                 `review_note` = ?
             WHERE `id` = ?
               AND `status` = 'Pending'
               AND `archived_at` IS NULL",
            'iss',
            [$adminId, $reviewNote, $resId]
        );

        if ($affected === 1) {
            dbCommit();
            return ['success' => true, 'message' => 'Reservation rejected successfully.'];
        }

        dbRollback();
        return ['success' => false, 'message' => 'Failed to reject reservation.'];
    } catch (Throwable $e) {
        dbRollback();
        error_log('rejectReservation error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred while rejecting the reservation.'];
    }
}

/**
 * Get user notification preferences from the database.
 */
function getUserPreferences(int $userId): array {
    $prefs = dbFetchOne("SELECT * FROM `user_preferences` WHERE `user_id` = ?", 'i', [$userId]);
    return $prefs ?: [];
}

/**
 * Save user notification preferences.
 */
function saveUserPreferences(int $userId, array $prefs): bool {
    $ok = dbExecute(
        "INSERT INTO `user_preferences` (`user_id`,`email_new_reservation`,`email_status_update`,`email_reminders`,`system_new_reservation`,`system_status_update`)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            `email_new_reservation`  = VALUES(`email_new_reservation`),
            `email_status_update`    = VALUES(`email_status_update`),
            `email_reminders`        = VALUES(`email_reminders`),
            `system_new_reservation` = VALUES(`system_new_reservation`),
            `system_status_update`   = VALUES(`system_status_update`)",
        'iiiiii',
        [
            $userId,
            (int)($prefs['email_new_reservation'] ?? 0),
            (int)($prefs['email_status_update'] ?? 0),
            (int)($prefs['email_reminders'] ?? 0),
            (int)($prefs['system_new_reservation'] ?? 0),
            (int)($prefs['system_status_update'] ?? 0),
        ]
    );
    return $ok !== false;
}

/**
 * Change user password (verifies old password first).
 * Uses PASSWORD_BCRYPT.
 * @return array ['success' => bool, 'message' => string]
 */
function changePassword(int $userId, string $oldPassword, string $newPassword): array {
    $user = dbFetchOne("SELECT `password_hash` FROM `users` WHERE `id` = ?", 'i', [$userId]);
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    if (!password_verify($oldPassword, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    if (!isStrongPassword($newPassword)) {
        return ['success' => false, 'message' => 'New password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.'];
    }

    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $ok = dbExecute("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?", 'si', [$newHash, $userId]);
    return $ok !== false
        ? ['success' => true, 'message' => 'Password updated successfully.']
        : ['success' => false, 'message' => 'Failed to update password.'];
}

/**
 * Update user profile (fullname, contact, department).
 * Never touches email, id_number, role, access_level, password_hash, or email_verified.
 * @return array ['success' => bool, 'message' => string]
 */
function updateUserProfile(int $userId, string $fullname, string $contact, string $department): array {
    $fullname   = trim($fullname);
    $contact    = trim($contact);
    $department = trim($department);

    $fnLen = mb_strlen($fullname);
    if ($fnLen < 2 || $fnLen > 150 || !preg_match('/\p{L}/u', $fullname)) {
        return ['success' => false, 'message' => 'Full name must be between 2 and 150 characters and contain at least one letter.'];
    }

    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $contact)) {
        return ['success' => false, 'message' => 'Contact number must be between 7 and 20 digits, spaces, plus or hyphens.'];
    }

    if ($department !== '') {
        $deptLen = mb_strlen($department);
        if ($deptLen < 2 || $deptLen > 100 || !preg_match("/^[\\p{L}\\p{N} .,&()\\-']+$/u", $department)) {
            return ['success' => false, 'message' => 'Department must be between 2 and 100 characters and contain only allowed letters, numbers, spaces, and punctuation.'];
        }
    }

    try {
        $ok = dbExecute(
            "UPDATE `users` SET `fullname` = ?, `contact` = ?, `department` = ? WHERE `id` = ? AND `archived_at` IS NULL",
            'sssi',
            [$fullname, $contact, $department, $userId]
        );
        if ($ok !== false) {
            if (isset($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) === $userId) {
                $_SESSION['user']['fullname']   = $fullname;
                $_SESSION['user']['contact']    = $contact;
                $_SESSION['user']['department'] = $department;
            }
            return ['success' => true, 'message' => 'Profile updated successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to update profile. Please try again.'];
    } catch (Throwable $e) {
        error_log('updateUserProfile error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'A system error occurred while updating your profile.'];
    }
}

/**
 * Archive own user account.
 * Requires correct password, non-privileged status, and no active upcoming reservations.
 * @return array ['success' => bool, 'reason' => string, 'message' => string]
 */
function archiveOwnAccount(int $userId, string $password): array {
    $user = dbFetchOne(
        "SELECT `id`, `password_hash`, `role`, `access_level` FROM `users` WHERE `id` = ? AND `archived_at` IS NULL LIMIT 1",
        'i',
        [$userId]
    );

    if (!$user) {
        return ['success' => false, 'reason' => 'not_found', 'message' => 'User account not found.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'reason' => 'password', 'message' => 'Current password is incorrect.'];
    }

    $isPrivileged = ($user['role'] === 'Admin') || in_array($user['access_level'], ['admin', 'superadmin'], true);
    if ($isPrivileged) {
        return ['success' => false, 'reason' => 'privileged', 'message' => 'Administrator accounts must be archived by a superadmin.'];
    }

    $activeRes = dbFetchOne(
        "SELECT `id` FROM `reservations`
         WHERE `user_id` = ?
           AND `status` IN ('Pending', 'Approved')
           AND `archived_at` IS NULL
           AND `booking_date` >= CURDATE()
         LIMIT 1",
        'i',
        [$userId]
    );

    if ($activeRes) {
        return ['success' => false, 'reason' => 'active_reservations', 'message' => 'Cancel or complete your active reservations first.'];
    }

    try {
        $affected = dbExecute(
            "UPDATE `users` SET `archived_at` = NOW() WHERE `id` = ? AND `archived_at` IS NULL",
            'i',
            [$userId]
        );
        if ($affected !== false && $affected > 0) {
            return ['success' => true, 'reason' => '', 'message' => 'Account successfully deactivated.'];
        }
        return ['success' => false, 'reason' => 'db_error', 'message' => 'Failed to deactivate account. Please try again.'];
    } catch (Throwable $e) {
        error_log('archiveOwnAccount error: ' . $e->getMessage());
        return ['success' => false, 'reason' => 'db_error', 'message' => 'A system error occurred while processing your request.'];
    }
}


/**
 * Get system configuration from the database.
 */
function getSystemConfig(): array {
    $rows = dbFetchAll("SELECT `config_key`, `config_value` FROM `system_config`");
    $config = [];
    foreach ($rows as $row) {
        $config[$row['config_key']] = $row['config_value'];
    }
    return $config;
}

/**
 * Save system configuration (admin only).
 */
function saveSystemConfig(array $config, int $adminId): bool {
    foreach ($config as $key => $value) {
        dbExecute(
            "INSERT INTO `system_config` (`config_key`,`config_value`,`updated_by`)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`), `updated_by` = VALUES(`updated_by`)",
            'ssi',
            [$key, (string)$value, $adminId]
        );
    }
    return true;
}


/**
 * Validate password strength server-side:
 * Minimum 8 characters, at least one uppercase, one lowercase, one number, and one symbol.
 */
function isStrongPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^a-zA-Z0-9]/', $password);
}

// ─── OTP / Email Verification ───────────────────────────────────────────────

/**
 * Generate a 6-digit OTP code, invalidate prior unused codes, and store bcrypt hash.
 * Code expires in 10 minutes (SQL DATE_ADD). Returns plain code for mailing only.
 */
function createOtp(int $userId, string $purpose): string {
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Invalidate earlier unused codes for user + purpose (UPDATE, no DELETE)
    dbExecute(
        "UPDATE `otp_codes` SET `used` = 1 WHERE `user_id` = ? AND `purpose` = ? AND `used` = 0",
        'is',
        [$userId, $purpose]
    );

    $codeHash = password_hash($code, PASSWORD_BCRYPT);

    dbExecute(
        "INSERT INTO `otp_codes` (`user_id`, `code_hash`, `purpose`, `expires_at`, `used`, `attempts`)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, 0)",
        'iss',
        [$userId, $codeHash, $purpose]
    );

    return $code;
}

/**
 * Verify a 6-digit OTP code against the latest valid, unexpired entry with < 5 attempts.
 * Increments attempts on every try. Marks used=1 on successful verification.
 */
function verifyOtp(int $userId, string $purpose, string $code): bool {
    $otp = dbFetchOne(
        "SELECT `id`, `code_hash` FROM `otp_codes`
         WHERE `user_id` = ? AND `purpose` = ? AND `used` = 0 AND `expires_at` > NOW() AND `attempts` < 5
         ORDER BY `id` DESC LIMIT 1",
        'is',
        [$userId, $purpose]
    );

    if (!$otp) {
        return false;
    }

    // Increment attempts on every attempt
    dbExecute(
        "UPDATE `otp_codes` SET `attempts` = `attempts` + 1 WHERE `id` = ?",
        'i',
        [$otp['id']]
    );

    if (password_verify($code, $otp['code_hash'])) {
        dbExecute(
            "UPDATE `otp_codes` SET `used` = 1 WHERE `id` = ?",
            'i',
            [$otp['id']]
        );
        return true;
    }

    return false;
}

// ─── OTP Request Limits ─────────────────────────────────────────────────────
const OTP_HOURLY_LIMITS = [
    'login'  => 10,
    'signup' => 5,
    'reset'  => 5
];

/**
 * Check if the user has reached the hourly limit of OTP codes for the specified purpose.
 */
function isOtpCapped(int $userId, string $purpose): bool {
    $limit = OTP_HOURLY_LIMITS[$purpose] ?? 5;
    $hourly = dbFetchOne(
        "SELECT COUNT(*) AS `cnt` FROM `otp_codes`
         WHERE `user_id` = ? AND `purpose` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
        'is',
        [$userId, $purpose]
    );
    return ($hourly && (int)$hourly['cnt'] >= $limit);
}

/**
 * Check if a new OTP can be resent (cooldown: 60 seconds since last generated code, plus hourly cap).
 * Comparison is evaluated directly in MySQL.
 */
function canResendOtp(int $userId, string $purpose): bool {
    $recent = dbFetchOne(
        "SELECT `id` FROM `otp_codes`
         WHERE `user_id` = ? AND `purpose` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 60 SECOND)
         LIMIT 1",
        'is',
        [$userId, $purpose]
    );
    if (!empty($recent)) {
        return false;
    }

    if (isOtpCapped($userId, $purpose)) {
        return false;
    }

    return true;
}

/**
 * Get remaining cooldown seconds before a new OTP can be requested (0 if ready).
 * When capped, returns the seconds until the oldest code in the 60-minute window ages out (at least 1).
 */
function getOtpResendWait(int $userId, string $purpose): int {
    if (isOtpCapped($userId, $purpose)) {
        $row = dbFetchOne(
            "SELECT GREATEST(1, 3600 - TIMESTAMPDIFF(SECOND, MIN(`created_at`), NOW())) AS `wait_seconds`
             FROM `otp_codes`
             WHERE `user_id` = ? AND `purpose` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 60 MINUTE)",
            'is',
            [$userId, $purpose]
        );
        return $row ? (int)$row['wait_seconds'] : 1;
    }

    $row = dbFetchOne(
        "SELECT GREATEST(0, 60 - TIMESTAMPDIFF(SECOND, `created_at`, NOW())) AS `wait_seconds`
         FROM `otp_codes`
         WHERE `user_id` = ? AND `purpose` = ?
         ORDER BY `id` DESC
         LIMIT 1",
        'is',
        [$userId, $purpose]
    );
    return $row ? (int) $row['wait_seconds'] : 0;
}

/**
 * Get reservation statistics for the admin dashboard.
 * Returns: pending (all time), approved_month, rejected_month (current calendar month), facilities.
 */
function getReservationStats(): array {
    $pending = dbFetchOne(
        "SELECT COUNT(*) AS `cnt` FROM `reservations` WHERE `status` = 'Pending' AND `archived_at` IS NULL"
    );
    $approvedMonth = dbFetchOne(
        "SELECT COUNT(*) AS `cnt` FROM `reservations`
         WHERE `status` = 'Approved'
           AND `archived_at` IS NULL
           AND YEAR(`reviewed_at`) = YEAR(CURDATE())
           AND MONTH(`reviewed_at`) = MONTH(CURDATE())"
    );
    $rejectedMonth = dbFetchOne(
        "SELECT COUNT(*) AS `cnt` FROM `reservations`
         WHERE `status` = 'Rejected'
           AND `archived_at` IS NULL
           AND YEAR(`reviewed_at`) = YEAR(CURDATE())
           AND MONTH(`reviewed_at`) = MONTH(CURDATE())"
    );
    $facilities = dbFetchOne(
        "SELECT COUNT(*) AS `cnt` FROM `facilities`"
    );
    return [
        'pending'        => (int)($pending['cnt'] ?? 0),
        'approved_month' => (int)($approvedMonth['cnt'] ?? 0),
        'rejected_month' => (int)($rejectedMonth['cnt'] ?? 0),
        'facilities'     => (int)($facilities['cnt'] ?? 0),
    ];
}

/**
 * Get the last 5 reviewed reservations for the admin dashboard.
 * Returns display fields only: reservation ID, status, facility, requester, reviewed date.
 */
function getRecentlyReviewed(): array {
    return dbFetchAll(
        "SELECT r.id, r.status, r.facility_name, r.reviewed_at, u.fullname AS requester_name
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         WHERE r.status IN ('Approved', 'Rejected')
           AND r.archived_at IS NULL
         ORDER BY r.reviewed_at DESC
         LIMIT 5"
    );
}
