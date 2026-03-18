<?php
require_once __DIR__ . '/admin_auth.php';
requireAdminAuth();

$adminHeaderTitle = 'Admin Settings';
$adminHeaderSubtitle = 'Security, visual preferences, and account controls';

$flash = '';
$flashType = 'ok';
$adminUser = (string)($_SESSION['admin_user'] ?? '');

$preferenceOptions = [
    'theme' => ['blush', 'midnight', 'sunset', 'mint'],
    'font_family' => ['system', 'manrope', 'poppins', 'lora'],
    'density' => ['comfy', 'compact'],
    'card_style' => ['soft', 'glass', 'flat'],
    'animations' => ['on', 'off']
];

$prefs = getDefaultAdminPreferences();

try {
    $conn = getAdminDbConnection();
    ensureAdminUsersTable($conn);
    ensureAdminPreferencesTable($conn);
    $prefs = getAdminPreferences($conn, $adminUser);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';

        if (!verifyAdminCsrf($token)) {
            $flash = 'Security token mismatch. Please refresh and try again.';
            $flashType = 'error';
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'save_preferences') {
                $inputPrefs = [
                    'theme' => $_POST['theme'] ?? '',
                    'font_family' => $_POST['font_family'] ?? '',
                    'density' => $_POST['density'] ?? '',
                    'card_style' => $_POST['card_style'] ?? '',
                    'animations' => $_POST['animations'] ?? ''
                ];

                $prefs = saveAdminPreferences($conn, $adminUser, $inputPrefs);
                $flash = 'Display preferences saved successfully.';
                $flashType = 'ok';
            }

            if ($action === 'reset_preferences') {
                $prefs = saveAdminPreferences($conn, $adminUser, getDefaultAdminPreferences());
                $flash = 'Display preferences reset to defaults.';
                $flashType = 'ok';
            }

            if ($action === 'change_password') {
                $currentPassword = (string)($_POST['current_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');

                if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                    $flash = 'Fill out all password fields.';
                    $flashType = 'error';
                } elseif (!hash_equals($newPassword, $confirmPassword)) {
                    $flash = 'New password and confirmation do not match.';
                    $flashType = 'error';
                } else {
                    updateAdminPassword($conn, $adminUser, $currentPassword, $newPassword);
                    $flash = 'Password updated successfully.';
                    $flashType = 'ok';
                }
            }
        }
    }
} catch (Exception $e) {
    $flash = $e->getMessage();
    $flashType = 'error';
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

$csrfToken = adminCsrfToken();

function selected($a, $b)
{
    return $a === $b ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="/favicon.jpg">
    <link rel="shortcut icon" href="/favicon.jpg">
    <title>Settings - Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            padding: 0 1rem 1rem;
        }

        .wrap {
            width: min(980px, 100% - 1rem);
            margin: 1rem auto 2rem;
            display: grid;
            gap: 0.9rem;
        }

        .card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-line);
            border-radius: var(--admin-card-radius);
            padding: 1.1rem;
            box-shadow: var(--admin-card-shadow);
            backdrop-filter: blur(4px);
        }

        .title {
            color: var(--admin-brand-strong);
            font-size: 1.35rem;
            font-weight: 900;
            margin-bottom: 0.3rem;
        }

        .sub {
            color: var(--admin-muted);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .flash {
            border-radius: 12px;
            padding: 0.68rem 0.8rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .flash.ok {
            background: #e8fff3;
            border: 1px solid #9edfc2;
            color: #0f7f49;
        }

        .flash.error {
            background: #ffedf4;
            border: 1px solid #f3b1d2;
            color: #ba1f71;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.7rem;
            margin-bottom: 0.7rem;
        }

        .field {
            display: grid;
            gap: 0.3rem;
        }

        label {
            color: var(--admin-brand-strong);
            font-size: 0.83rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        select,
        input {
            width: 100%;
            border: 1px solid var(--admin-control-border);
            border-radius: 11px;
            padding: 0.64rem 0.68rem;
            font-size: 0.92rem;
            background: var(--admin-control-bg);
            color: var(--admin-control-text);
        }

        select option {
            background: var(--admin-control-bg);
            color: var(--admin-control-text);
        }

        input::placeholder {
            color: var(--admin-control-placeholder);
        }

        select:focus,
        input:focus {
            outline: 3px solid rgba(255, 159, 210, 0.24);
            border-color: var(--admin-brand);
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.2rem;
        }

        .btn {
            border: none;
            border-radius: 11px;
            padding: 0.62rem 0.86rem;
            font-size: 0.84rem;
            font-weight: 800;
            cursor: pointer;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--admin-brand) 0%, var(--admin-brand-strong) 100%);
            color: #fff;
            box-shadow: 0 10px 18px rgba(171, 23, 101, 0.22);
        }

        .btn-soft {
            border: 1px solid var(--admin-line);
            background: rgba(255, 255, 255, 0.86);
            color: var(--admin-brand-strong);
        }

        .list {
            display: grid;
            gap: 0.6rem;
        }

        .item {
            border: 1px solid var(--admin-line);
            border-radius: 12px;
            padding: 0.75rem;
            background: rgba(255, 246, 251, 0.9);
        }

        .item h3 {
            color: var(--admin-brand-strong);
            font-size: 0.98rem;
            margin-bottom: 0.2rem;
        }

        .item p {
            color: var(--admin-muted);
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .hr {
            height: 1px;
            border: 0;
            background: var(--admin-line);
            margin: 1rem 0;
            opacity: 0.9;
        }

        @media (max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <?php include __DIR__ . '/nav.php'; ?>

    <main class="wrap">
        <?php if ($flash !== ''): ?>
            <div class="flash <?php echo $flashType === 'error' ? 'error' : 'ok'; ?>"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="card">
            <h1 class="title">Appearance</h1>
            <p class="sub">Customize how your admin dashboard looks and feels.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_preferences">

                <div class="grid">
                    <div class="field">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme" required>
                            <?php foreach ($preferenceOptions['theme'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo selected($prefs['theme'], $option); ?>><?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="font_family">Font</label>
                        <select id="font_family" name="font_family" required>
                            <?php foreach ($preferenceOptions['font_family'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo selected($prefs['font_family'], $option); ?>><?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="density">Density</label>
                        <select id="density" name="density" required>
                            <?php foreach ($preferenceOptions['density'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo selected($prefs['density'], $option); ?>><?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="card_style">Card Style</label>
                        <select id="card_style" name="card_style" required>
                            <?php foreach ($preferenceOptions['card_style'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo selected($prefs['card_style'], $option); ?>><?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="animations">Animations</label>
                        <select id="animations" name="animations" required>
                            <?php foreach ($preferenceOptions['animations'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo selected($prefs['animations'], $option); ?>><?php echo htmlspecialchars(strtoupper($option), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Save Preferences</button>
                </div>
            </form>

            <form method="POST" class="actions" style="margin-top: 0.65rem;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="reset_preferences">
                <button class="btn btn-soft" type="submit">Reset to Default</button>
            </form>

            <hr class="hr">

            <h2 class="title" style="font-size: 1.08rem; margin-bottom: 0.35rem;">Security</h2>
            <p class="sub" style="margin-bottom: 0.8rem;">Change your admin password.</p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="grid">
                    <div class="field">
                        <label for="current_password">Current Password</label>
                        <input id="current_password" name="current_password" type="password" required>
                    </div>
                    <div class="field">
                        <label for="new_password">New Password</label>
                        <input id="new_password" name="new_password" type="password" minlength="10" required>
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm New Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" minlength="10" required>
                    </div>
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit">Update Password</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h1 class="title">System Info</h1>
            <p class="sub">Useful admin references and account context.</p>

            <div class="list">
                <article class="item">
                    <h3>Current Admin User</h3>
                    <p><?php echo htmlspecialchars($adminUser ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
                <article class="item">
                    <h3>Login Route</h3>
                    <p>Use admin_login.php to access your account.</p>
                </article>
                <article class="item">
                    <h3>Create More Admin Accounts</h3>
                    <p>Open admin_register.php while logged in to add another admin.</p>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
