<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * DATABASE CONNECTION (includes/db_connect.php)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 *
 * 1. CONNECTION ONLY: This file establishes the mysqli connection and
 *    provides prepared-statement query helpers. All table creation and
 *    seed data live in schema.sql (run once during deployment).
 *
 * 2. PREPARED STATEMENTS: All query helpers use mysqli prepared statements
 *    to prevent SQL injection — no raw user input is ever concatenated.
 *
 * 3. PASSWORD SECURITY: password_hash() with PASSWORD_BCRYPT
 *    is used for all password storage. Plaintext is never stored.
 */

// Set PHP default timezone to Philippine Standard Time (UTC+8, no DST)
date_default_timezone_set('Asia/Manila');

// ─── Database Configuration ─────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASS',     '');           // Default XAMPP — no password
define('DB_NAME',     'kld_facility_reservation');
define('DB_CHARSET',  'utf8mb4');

// ─── Create Connection ──────────────────────────────────────────────────────
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
         <h2 style="color:#dc2626;">Database Connection Failed</h2>
         <p>Error: ' . htmlspecialchars($conn->connect_error) . '</p>
         <p>Please ensure MySQL/MariaDB is running in XAMPP Control Panel.</p>
         </div>');
}

// ─── Select Database ────────────────────────────────────────────────────────
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);
$conn->set_charset(DB_CHARSET);
// Synchronize MySQL session time zone with Philippine Standard Time (UTC+8)
$conn->query("SET time_zone = '+08:00'");

// ─── Query Helpers ──────────────────────────────────────────────────────────

/**
 * Helper: Execute a prepared SELECT query and return all rows.
 * @param  string $sql    SQL with ? placeholders
 * @param  string $types  Type string (e.g. "si" for string+int)
 * @param  array  $params Bind parameters
 * @return array          Array of associative rows
 */
function dbFetchAll(string $sql, string $types = '', array $params = []): array {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/**
 * Helper: Execute a prepared SELECT and return the first row.
 */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array {
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}

/**
 * Helper: Execute an INSERT/UPDATE/DELETE prepared statement.
 * @return int|false  Affected rows on success, or false on failure
 */
function dbExecute(string $sql, string $types = '', array $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $ok ? $affected : false;
}

/**
 * Helper: Get the last auto-increment ID.
 */
function dbLastId(): int {
    global $conn;
    return (int) $conn->insert_id;
}

/**
 * Helper: Begin a database transaction.
 */
function dbBegin(): bool {
    global $conn;
    return $conn->begin_transaction();
}

/**
 * Helper: Commit the active database transaction.
 */
function dbCommit(): bool {
    global $conn;
    return $conn->commit();
}

/**
 * Helper: Rollback the active database transaction.
 */
function dbRollback(): bool {
    global $conn;
    return $conn->rollback();
}
