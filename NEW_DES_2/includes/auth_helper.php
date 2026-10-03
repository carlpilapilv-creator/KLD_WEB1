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
 */
function getUserReservations(int $userId): array {
    return dbFetchAll(
        "SELECT r.*, u.fullname AS applicant, u.role, u.department AS dept
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         WHERE r.user_id = ?
         ORDER BY r.created_at DESC",
        'i',
        [$userId]
    );
}

/**
 * Get ALL reservations (for admin views / conflict detection).
 */
function getAllReservations(): array {
    return dbFetchAll(
        "SELECT r.*, u.fullname AS applicant, u.role, u.department AS dept
         FROM `reservations` r
         JOIN `users` u ON r.user_id = u.id
         ORDER BY r.created_at DESC"
    );
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
 * Create a new reservation in the database.
 * @return string|false  The reservation ID on success, false on failure
 */
function createReservation(int $userId, array $data) {
    // Generate sequential reservation ID
    $row = dbFetchOne("SELECT COUNT(*) AS cnt FROM `reservations`");
    $count = ($row['cnt'] ?? 0) + 1;
    $resId = 'KLD-RES-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

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
            // status is hardcoded 'Pending' in SQL
        ]
    );

    return $ok !== false ? $resId : false;
}

/**
 * Cancel (reject) a reservation by ID, only if the user owns it.
 */
function cancelReservation(string $resId, int $userId): bool {
    $affected = dbExecute(
        "UPDATE `reservations` SET `status` = 'Cancelled' WHERE `id` = ? AND `user_id` = ? AND `status` = 'Pending'",
        'si',
        [$resId, $userId]
    );
    return $affected > 0;
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


