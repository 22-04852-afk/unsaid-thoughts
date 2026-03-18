<?php
/**
 * User Session Management
 * Creates/manages anonymous user IDs for tracking reactions
 */

require_once __DIR__ . '/db_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function createUserId()
{
    return bin2hex(random_bytes(16));
}

function normalizeUserIdFromCookie($value)
{
    $candidate = strtolower(trim((string)$value));
    return preg_match('/^[a-f0-9]{32}$/', $candidate) ? $candidate : '';
}

function setPersistentUserCookie($userId)
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

    setcookie('unsaid_user_id', $userId, [
        'expires' => time() + (365 * 24 * 60 * 60),
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Generate or retrieve user ID (persist per device using cookie)
if (!isset($_SESSION['user_id'])) {
    $cookieUserId = normalizeUserIdFromCookie($_COOKIE['unsaid_user_id'] ?? '');
    $user_id = $cookieUserId !== '' ? $cookieUserId : createUserId();
    $_SESSION['user_id'] = $user_id;
    setPersistentUserCookie($user_id);
} else {
    $user_id = (string)$_SESSION['user_id'];
    setPersistentUserCookie($user_id);
}

function ensureUniqueVisitsTable($conn)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS unique_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(64) NOT NULL UNIQUE,
            first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            user_agent VARCHAR(255) NULL,
            ip_hash CHAR(64) NULL,
            INDEX idx_first_seen (first_seen)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    $conn->query($sql);
}

function recordUniqueVisit($userId)
{
    static $recorded = false;
    if ($recorded || $userId === '') {
        return;
    }

    try {
        $conn = dbConnect(true);
        ensureUniqueVisitsTable($conn);

        $sql = '
            INSERT INTO unique_visits (user_id, user_agent, ip_hash)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                last_seen = CURRENT_TIMESTAMP,
                user_agent = VALUES(user_agent),
                ip_hash = VALUES(ip_hash)
        ';

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
            $stmt->bind_param('sss', $userId, $userAgent, $ipHash);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
        $recorded = true;
    } catch (Exception $e) {
        // Ignore tracking failures to avoid impacting page load.
    }
}

recordUniqueVisit($user_id);

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
?>
