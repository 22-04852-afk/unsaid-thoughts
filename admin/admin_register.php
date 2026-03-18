<?php
require_once __DIR__ . '/admin_auth.php';

$error = '';
$success = '';
$formValues = [
    'username' => '',
    'email' => ''
];

try {
    $conn = getAdminDbConnection();
    ensureAdminUsersTable($conn);
    $hasAdminAccount = adminUsersCount($conn) > 0;
} catch (Exception $e) {
    $error = $e->getMessage();
    $hasAdminAccount = true;
}

$setupKey = trim((string)($_GET['setup_key'] ?? $_POST['setup_key'] ?? ''));
$bootstrapAllowed = !$hasAdminAccount && isValidAdminBootstrapKey($setupKey);
$canRegister = isAdminLoggedIn() || $bootstrapAllowed;
$csrfToken = adminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canRegister) {
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $submittedToken = $_POST['csrf_token'] ?? '';

    $formValues['username'] = $username;
    $formValues['email'] = $email;

    if (!verifyAdminCsrf($submittedToken)) {
        $error = 'Invalid request token. Please refresh the page and try again.';
    } elseif ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
        $error = 'Username must be 3-30 chars and only use letters, numbers, dot, underscore, or dash.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must be at least 10 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password)
        || !preg_match('/\d/', $password)
        || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = 'Password must include uppercase, lowercase, number, and symbol.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $checkStmt = $conn->prepare('SELECT id FROM admin_users WHERE username = ? OR email = ? LIMIT 1');
            if (!$checkStmt) {
                throw new Exception('Failed to check existing account: ' . $conn->error);
            }
            $checkStmt->bind_param('ss', $username, $email);
            $checkStmt->execute();
            $existsResult = $checkStmt->get_result();
            $exists = $existsResult && $existsResult->fetch_assoc();
            $checkStmt->close();

            if ($exists) {
                throw new Exception('Username or email is already in use.');
            }

            createAdminUser($conn, $username, $email, $password);
            $success = 'Admin account created successfully.';
            $formValues['username'] = '';
            $formValues['email'] = '';

            if (!$hasAdminAccount) {
                session_regenerate_id(true);
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_user'] = $username;
                $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

                header('Location: dashboard-admin.php');
                exit;
            }
        } catch (Exception $e) {
            $error = strpos($e->getMessage(), 'Duplicate') !== false
                || strpos($e->getMessage(), 'duplicate') !== false
                ? 'Username or email is already in use.'
                : $e->getMessage();
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
    <title>Create Admin Account - Unsaid Thoughts</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 10%, #ffd9ec 0%, #ffc6e2 25%, #ff9dcd 60%, #f278b7 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0 1rem 1rem;
        }

        .card {
            width: 100%;
            max-width: 460px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 18px;
            box-shadow: 0 24px 50px rgba(110, 10, 64, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.8);
            padding: 1.6rem;
            margin: 1rem auto;
        }

        h1 {
            color: #ca2f84;
            font-size: 1.45rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
        }

        p.sub {
            color: #8f4a73;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .field { margin-bottom: 0.9rem; }

        .field-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 0.5rem;
            align-items: end;
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

        .btn-secondary {
            border: 1px solid #f0b7d6;
            border-radius: 10px;
            background: #fff;
            color: #b91f72;
            font-weight: 700;
            font-size: 0.82rem;
            padding: 0.68rem 0.7rem;
            cursor: pointer;
        }

        .password-hint {
            margin-top: 0.35rem;
            color: #8f4a73;
            font-size: 0.78rem;
        }

        .strength-wrap {
            margin-top: 0.45rem;
        }

        .strength-bar {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: #f4dce9;
            overflow: hidden;
        }

        .strength-fill {
            width: 0%;
            height: 100%;
            background: #e04d9b;
            transition: width 0.25s ease, background 0.25s ease;
        }

        .strength-text {
            margin-top: 0.28rem;
            color: #8f4a73;
            font-size: 0.78rem;
        }

        .error, .success {
            margin-bottom: 0.8rem;
            border-radius: 10px;
            padding: 0.6rem;
            font-size: 0.86rem;
        }

        .error {
            background: #ffe8f3;
            border: 1px solid #ffa6d2;
            color: #b71870;
        }

        .success {
            background: #e8fff4;
            border: 1px solid #a6e6c7;
            color: #0b8450;
        }

        .links {
            margin-top: 0.85rem;
            text-align: center;
            font-size: 0.85rem;
        }

        .links a {
            color: #c7277f;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px dashed #dd66a6;
        }

        @media (max-width: 480px) {
            .field-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $adminHeaderTitle = 'Create Admin Account'; ?>
    <?php $adminHeaderSubtitle = 'Set up secure access for your private admin tools'; ?>
    <?php include __DIR__ . '/header.php'; ?>
    <?php include __DIR__ . '/nav.php'; ?>

    <main class="card">
        <h1>Create Admin Account</h1>
        <p class="sub">This page creates an admin account for your dashboard.</p>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($canRegister): ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="setup_key" value="<?php echo htmlspecialchars($setupKey, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" minlength="3" maxlength="30" pattern="[a-zA-Z0-9._-]{3,30}" value="<?php echo htmlspecialchars($formValues['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <p class="password-hint">Only letters, numbers, dot, underscore, and dash.</p>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="190" value="<?php echo htmlspecialchars($formValues['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="field-row">
                        <input id="password" name="password" type="password" minlength="10" maxlength="128" required>
                        <button class="btn-secondary" id="togglePassword" type="button">Show</button>
                    </div>
                    <p class="password-hint">At least 10 chars with uppercase, lowercase, number, and symbol.</p>
                    <div class="strength-wrap">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <p class="strength-text" id="strengthText">Password strength: too weak</p>
                    </div>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="field-row">
                        <input id="confirm_password" name="confirm_password" type="password" minlength="10" maxlength="128" required>
                        <button class="btn-secondary" id="toggleConfirmPassword" type="button">Show</button>
                    </div>
                </div>

                <button class="btn" type="submit">Create Account</button>
            </form>
        <?php else: ?>
            <?php if (!$hasAdminAccount): ?>
                <div class="error">
                    First admin registration is locked. Provide a valid <strong>setup_key</strong> in the URL and set the same
                    <strong>ADMIN_BOOTSTRAP_KEY</strong> in admin_config.php.
                </div>
            <?php else: ?>
                <div class="error">Registration is locked. Sign in as admin first before creating another account.</div>
            <?php endif; ?>
        <?php endif; ?>

        <p class="links">
            <a href="admin_login.php">Go to Sign In</a>
        </p>
    </main>

    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var confirmInput = document.getElementById('confirm_password');
            var togglePassword = document.getElementById('togglePassword');
            var toggleConfirm = document.getElementById('toggleConfirmPassword');
            var strengthFill = document.getElementById('strengthFill');
            var strengthText = document.getElementById('strengthText');

            function toggleField(button, input) {
                if (!button || !input) return;
                button.addEventListener('click', function () {
                    var isPassword = input.type === 'password';
                    input.type = isPassword ? 'text' : 'password';
                    button.textContent = isPassword ? 'Hide' : 'Show';
                });
            }

            function scorePassword(value) {
                var score = 0;
                if (value.length >= 10) score++;
                if (/[A-Z]/.test(value)) score++;
                if (/[a-z]/.test(value)) score++;
                if (/\d/.test(value)) score++;
                if (/[^A-Za-z0-9]/.test(value)) score++;
                if (value.length >= 14) score++;
                return score;
            }

            function updateStrength() {
                if (!passwordInput || !strengthFill || !strengthText) return;
                var score = scorePassword(passwordInput.value);
                var percentage = Math.min(100, Math.round((score / 6) * 100));
                var label = 'too weak';
                var color = '#e04d9b';

                if (score >= 5) {
                    label = 'strong';
                    color = '#13a167';
                } else if (score >= 3) {
                    label = 'medium';
                    color = '#db8f11';
                }

                strengthFill.style.width = percentage + '%';
                strengthFill.style.background = color;
                strengthText.textContent = 'Password strength: ' + label;
            }

            toggleField(togglePassword, passwordInput);
            toggleField(toggleConfirm, confirmInput);

            if (passwordInput) {
                passwordInput.addEventListener('input', updateStrength);
                updateStrength();
            }
        })();
    </script>
</body>
</html>
