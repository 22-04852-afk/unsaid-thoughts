<?php
require_once __DIR__ . '/admin_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn()
{
    return !empty($_SESSION['is_admin']) && !empty($_SESSION['admin_user']);
}

function requireAdminAuth()
{
    if (!isAdminLoggedIn()) {
        header('Location: admin_login.php');
        exit;
    }
}

function adminCsrfToken()
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function verifyAdminCsrf($token)
{
    if (empty($_SESSION['admin_csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

function getAdminDbConnection()
{
    $conn = new mysqli('localhost', 'root', '', 'unsaid_thoughts');
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function ensureAdminUsersTable($conn)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(120) NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new Exception('Failed to create admin_users table: ' . $conn->error);
    }
}

function adminUsersCount($conn)
{
    $result = $conn->query('SELECT COUNT(*) AS total FROM admin_users');
    if (!$result) {
        throw new Exception('Failed to count admin users: ' . $conn->error);
    }

    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

function findAdminUserByLogin($conn, $login)
{
    $sql = 'SELECT id, username, email, password_hash FROM admin_users WHERE username = ? OR email = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare admin user lookup: ' . $conn->error);
    }

    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $user ?: null;
}

function createAdminUser($conn, $username, $email, $password)
{
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)');
    if (!$stmt) {
        throw new Exception('Failed to prepare admin user create: ' . $conn->error);
    }

    $stmt->bind_param('sss', $username, $email, $passwordHash);
    $ok = $stmt->execute();
    $error = $stmt->error;
    $stmt->close();

    if (!$ok) {
        throw new Exception('Failed to create admin account: ' . $error);
    }
}

function ensureAdminPreferencesTable($conn)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS admin_preferences (
            admin_username VARCHAR(120) PRIMARY KEY,
            theme VARCHAR(32) NOT NULL DEFAULT 'blush',
            font_family VARCHAR(32) NOT NULL DEFAULT 'system',
            density VARCHAR(16) NOT NULL DEFAULT 'comfy',
            card_style VARCHAR(16) NOT NULL DEFAULT 'soft',
            animations VARCHAR(8) NOT NULL DEFAULT 'on',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    if (!$conn->query($sql)) {
        throw new Exception('Failed to create admin_preferences table: ' . $conn->error);
    }
}

function getDefaultAdminPreferences()
{
    return [
        'theme' => 'blush',
        'font_family' => 'system',
        'density' => 'comfy',
        'card_style' => 'soft',
        'animations' => 'on'
    ];
}

function normalizeAdminPreferences($input)
{
    $defaults = getDefaultAdminPreferences();
    $allowed = [
        'theme' => ['blush', 'midnight', 'sunset', 'mint'],
        'font_family' => ['system', 'manrope', 'poppins', 'lora'],
        'density' => ['comfy', 'compact'],
        'card_style' => ['soft', 'glass', 'flat'],
        'animations' => ['on', 'off']
    ];

    $normalized = $defaults;
    foreach ($allowed as $key => $allowedValues) {
        $candidate = isset($input[$key]) ? strtolower(trim((string)$input[$key])) : $defaults[$key];
        $normalized[$key] = in_array($candidate, $allowedValues, true) ? $candidate : $defaults[$key];
    }

    return $normalized;
}

function getAdminPreferences($conn, $username)
{
    $defaults = getDefaultAdminPreferences();
    if ($username === '') {
        return $defaults;
    }

    ensureAdminPreferencesTable($conn);

    $stmt = $conn->prepare('SELECT theme, font_family, density, card_style, animations FROM admin_preferences WHERE admin_username = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('Failed to prepare preferences lookup: ' . $conn->error);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return $defaults;
    }

    return normalizeAdminPreferences($row);
}

function saveAdminPreferences($conn, $username, $preferences)
{
    if ($username === '') {
        throw new Exception('Missing admin username for preferences save.');
    }

    ensureAdminPreferencesTable($conn);
    $prefs = normalizeAdminPreferences($preferences);

    $sql = '
        INSERT INTO admin_preferences (admin_username, theme, font_family, density, card_style, animations)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            theme = VALUES(theme),
            font_family = VALUES(font_family),
            density = VALUES(density),
            card_style = VALUES(card_style),
            animations = VALUES(animations)
    ';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare preferences save: ' . $conn->error);
    }

    $stmt->bind_param(
        'ssssss',
        $username,
        $prefs['theme'],
        $prefs['font_family'],
        $prefs['density'],
        $prefs['card_style'],
        $prefs['animations']
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to save admin preferences: ' . $error);
    }

    $stmt->close();
    return $prefs;
}

function updateAdminPassword($conn, $username, $currentPassword, $newPassword)
{
    if ($username === '') {
        throw new Exception('Invalid admin user.');
    }

    $stmt = $conn->prepare('SELECT password_hash FROM admin_users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('Failed to prepare password lookup: ' . $conn->error);
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        throw new Exception('Admin account not found.');
    }

    if (!password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('Current password is incorrect.');
    }

    if (strlen($newPassword) < 10
        || !preg_match('/[A-Z]/', $newPassword)
        || !preg_match('/[a-z]/', $newPassword)
        || !preg_match('/\d/', $newPassword)
        || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        throw new Exception('New password must be at least 10 chars and include upper, lower, number, and symbol.');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare('UPDATE admin_users SET password_hash = ? WHERE username = ?');
    if (!$updateStmt) {
        throw new Exception('Failed to prepare password update: ' . $conn->error);
    }

    $updateStmt->bind_param('ss', $newHash, $username);
    if (!$updateStmt->execute()) {
        $error = $updateStmt->error;
        $updateStmt->close();
        throw new Exception('Failed to update password: ' . $error);
    }

    $updateStmt->close();
}

