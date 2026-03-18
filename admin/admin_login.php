<?php
require_once __DIR__ . '/admin_auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard-admin.php');
    exit;
}

$error = '';
$hasAdminAccount = false;
$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$maxAttempts = 5;
$windowSeconds = 15 * 60;

if (!isset($_SESSION['admin_login_rate']) || !is_array($_SESSION['admin_login_rate'])) {
    $_SESSION['admin_login_rate'] = [];
}

$rateBucket = $_SESSION['admin_login_rate'];
foreach ($rateBucket as $ip => $entries) {
    $cleaned = array_values(array_filter($entries, function ($ts) use ($windowSeconds) {
        return (time() - (int)$ts) <= $windowSeconds;
    }));

    if ($cleaned) {
        $rateBucket[$ip] = $cleaned;
    } else {
        unset($rateBucket[$ip]);
    }
}

$_SESSION['admin_login_rate'] = $rateBucket;
$attempts = $_SESSION['admin_login_rate'][$clientIp] ?? [];
$isRateLimited = count($attempts) >= $maxAttempts;

try {
    $conn = getAdminDbConnection();
    ensureAdminUsersTable($conn);
    $hasAdminAccount = adminUsersCount($conn) > 0;
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isRateLimited) {
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    }

    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($error === '' && ($username === '' || $password === '')) {
        $error = 'Enter your username/email and password.';
    } elseif ($error === '') {
        try {
            if (!isset($conn) || !($conn instanceof mysqli)) {
                $conn = getAdminDbConnection();
                ensureAdminUsersTable($conn);
                $hasAdminAccount = adminUsersCount($conn) > 0;
            }

            $matchedUser = findAdminUserByLogin($conn, $username);
            $validDbUser = $matchedUser && password_verify($password, $matchedUser['password_hash']);

            if ($validDbUser) {
                session_regenerate_id(true);
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_user'] = $matchedUser['username'];
                $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
                unset($_SESSION['admin_login_rate'][$clientIp]);

                header('Location: dashboard-admin.php');
                exit;
            }

            $error = 'Invalid admin credentials.';
            $_SESSION['admin_login_rate'][$clientIp][] = time();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="/favicon.jpg">
    <link rel="shortcut icon" href="/favicon.jpg">
    <title>Admin Login - Unsaid Thoughts</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 10%, #ffd9ec 0%, #ffc6e2 25%, #ff9dcd 60%, #f278b7 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0 1rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 18px;
            box-shadow: 0 24px 50px rgba(110, 10, 64, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 1.6rem;
            margin: 1rem auto;
        }

        .title {
            color: #ca2f84;
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
        }

        .subtitle {
            color: #8f4a73;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .field {
            margin-bottom: 0.9rem;
        }

        label {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #a03672;
            margin-bottom: 0.3rem;
        }

        input {
            width: 100%;
            border: 1px solid #f3bfdc;
            border-radius: 10px;
            padding: 0.72rem;
            font-size: 0.95rem;
            background: #fff;
        }

        input:focus {
            outline: 3px solid rgba(255, 125, 185, 0.22);
            border-color: #e85da8;
        }

        .btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #ff79bc 0%, #df3b8f 100%);
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.4px;
            padding: 0.76rem;
            cursor: pointer;
            margin-top: 0.2rem;
        }

        .btn:hover {
            filter: brightness(1.05);
        }

        .error {
            margin-bottom: 0.8rem;
            background: #ffe8f3;
            border: 1px solid #ffa6d2;
            color: #b71870;
            border-radius: 10px;
            padding: 0.6rem;
            font-size: 0.86rem;
        }

        .helper {
            margin-top: 0.9rem;
            text-align: center;
            font-size: 0.85rem;
            color: #8f4a73;
        }

        .helper a {
            color: #c7277f;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px dashed #dd66a6;
        }

        .helper a:hover {
            filter: brightness(0.95);
        }
    </style>
</head>
<body>
    <?php $adminHeaderTitle = 'Admin Sign In'; ?>
    <?php $adminHeaderSubtitle = 'Only authorized admins can access the control room'; ?>
    <?php include __DIR__ . '/header.php'; ?>
    <?php include __DIR__ . '/nav.php'; ?>

    <main class="login-card">
        <h1 class="title">Admin Access</h1>
        <p class="subtitle">Private area for managing all posted thoughts.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="field">
                <label for="username">Username or Email</label>
                <input id="username" name="username" type="text" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="btn" type="submit">Sign In as Admin</button>
        </form>

        <p class="helper">
            <?php if (!$hasAdminAccount): ?>
                <?php if (adminBootstrapKeyConfigured()): ?>
                    No admin account yet? Open <strong>admin_register.php?setup_key=YOUR_KEY</strong> to create the first one.
                <?php else: ?>
                    No admin account yet? Set <strong>ADMIN_BOOTSTRAP_KEY</strong> in admin_config.php first.
                <?php endif; ?>
            <?php else: ?>
                Need another admin account? Sign in first, then use register.
            <?php endif; ?>
        </p>
    </main>
</body>
</html>
