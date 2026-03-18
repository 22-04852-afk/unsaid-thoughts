<?php
require_once __DIR__ . '/admin_auth.php';

$adminHeaderTitle = $adminHeaderTitle ?? 'Admin Control Room';
$adminHeaderSubtitle = $adminHeaderSubtitle ?? 'Manage thoughts, songs, and community activity';

$adminPrefs = getDefaultAdminPreferences();
$themeTokens = [
    'blush' => [
        'bgA' => '#fff7fc',
        'bgB' => '#ffe8f5',
        'brand' => '#d42f86',
        'brandStrong' => '#bf1f74',
        'ink' => '#3e1e30',
        'muted' => '#8d5878',
        'line' => '#f3c9e0',
        'surface' => 'rgba(255, 255, 255, 0.88)'
    ],
    'midnight' => [
        'bgA' => '#151523',
        'bgB' => '#23152f',
        'brand' => '#7a9cff',
        'brandStrong' => '#4d79ff',
        'ink' => '#eceefe',
        'muted' => '#b7bde3',
        'line' => '#3f4166',
        'surface' => 'rgba(31, 33, 56, 0.86)'
    ],
    'sunset' => [
        'bgA' => '#fff4ec',
        'bgB' => '#ffe2d3',
        'brand' => '#ea5b2e',
        'brandStrong' => '#d64a1d',
        'ink' => '#45231a',
        'muted' => '#8f5e50',
        'line' => '#efc0b0',
        'surface' => 'rgba(255, 255, 255, 0.9)'
    ],
    'mint' => [
        'bgA' => '#eefcf8',
        'bgB' => '#daf4ee',
        'brand' => '#188f77',
        'brandStrong' => '#117763',
        'ink' => '#173630',
        'muted' => '#4f8177',
        'line' => '#aedfd4',
        'surface' => 'rgba(255, 255, 255, 0.9)'
    ]
];

$fontTokens = [
    'system' => "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif",
    'manrope' => "'Manrope', 'Segoe UI', sans-serif",
    'poppins' => "'Poppins', 'Segoe UI', sans-serif",
    'lora' => "'Lora', Georgia, serif"
];

if (!empty($_SESSION['admin_user'])) {
    try {
        $prefsConn = getAdminDbConnection();
        $adminPrefs = getAdminPreferences($prefsConn, (string)$_SESSION['admin_user']);
        $prefsConn->close();
    } catch (Exception $e) {
        $adminPrefs = getDefaultAdminPreferences();
    }
}

$selectedTheme = $themeTokens[$adminPrefs['theme']] ?? $themeTokens['blush'];
$selectedFont = $fontTokens[$adminPrefs['font_family']] ?? $fontTokens['system'];
$isDarkTheme = $adminPrefs['theme'] === 'midnight';

$densityScale = $adminPrefs['density'] === 'compact' ? '0.9' : '1';
$cardRadius = $adminPrefs['card_style'] === 'flat' ? '8px' : ($adminPrefs['card_style'] === 'glass' ? '18px' : '14px');
$cardShadow = $adminPrefs['card_style'] === 'flat'
    ? 'none'
    : ($adminPrefs['card_style'] === 'glass'
        ? '0 16px 28px rgba(56, 11, 39, 0.16)'
        : '0 10px 20px rgba(140, 18, 80, 0.1)');

$animationsEnabled = $adminPrefs['animations'] !== 'off';

$controlBg = $isDarkTheme ? 'rgba(24, 26, 44, 0.94)' : 'rgba(255, 255, 255, 0.92)';
$controlText = $isDarkTheme ? '#eef0ff' : '#3a2331';
$controlBorder = $isDarkTheme ? '#5f66a8' : $selectedTheme['line'];
$controlPlaceholder = $isDarkTheme ? '#b7bde3' : '#9a6c84';
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@700&family=Manrope:wght@400;600;700;800&family=Poppins:wght@400;600;700;800&family=Lora:wght@400;600;700&display=swap');

    :root {
        --admin-bg-a: <?php echo htmlspecialchars($selectedTheme['bgA'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-bg-b: <?php echo htmlspecialchars($selectedTheme['bgB'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-brand: <?php echo htmlspecialchars($selectedTheme['brand'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-brand-strong: <?php echo htmlspecialchars($selectedTheme['brandStrong'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-ink: <?php echo htmlspecialchars($selectedTheme['ink'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-muted: <?php echo htmlspecialchars($selectedTheme['muted'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-line: <?php echo htmlspecialchars($selectedTheme['line'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-surface: <?php echo htmlspecialchars($selectedTheme['surface'], ENT_QUOTES, 'UTF-8'); ?>;
        --admin-control-bg: <?php echo htmlspecialchars($controlBg, ENT_QUOTES, 'UTF-8'); ?>;
        --admin-control-text: <?php echo htmlspecialchars($controlText, ENT_QUOTES, 'UTF-8'); ?>;
        --admin-control-border: <?php echo htmlspecialchars($controlBorder, ENT_QUOTES, 'UTF-8'); ?>;
        --admin-control-placeholder: <?php echo htmlspecialchars($controlPlaceholder, ENT_QUOTES, 'UTF-8'); ?>;
        --admin-font-stack: <?php echo $selectedFont; ?>;
        --admin-density-scale: <?php echo $densityScale; ?>;
        --admin-card-radius: <?php echo $cardRadius; ?>;
        --admin-card-shadow: <?php echo $cardShadow; ?>;
    }

    body {
        font-family: var(--admin-font-stack);
        color: var(--admin-ink);
        font-size: calc(16px * var(--admin-density-scale));
        background: linear-gradient(160deg, var(--admin-bg-a) 0%, var(--admin-bg-b) 100%);
    }

    <?php if (!$animationsEnabled): ?>
    *, *::before, *::after {
        transition: none !important;
        animation: none !important;
    }
    <?php endif; ?>

    .admin-header {
        background: linear-gradient(135deg, var(--admin-brand) 0%, var(--admin-brand-strong) 100%);
        border-bottom: 2px dashed rgba(255, 255, 255, 0.45);
        box-shadow: 0 12px 34px rgba(187, 21, 112, 0.35);
        padding: 1rem 1rem;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 3100;
        overflow: hidden;
        width: 100vw;
        margin-left: 0;
    }

    .admin-header::before,
    .admin-header::after {
        position: absolute;
        color: rgba(255, 255, 255, 0.25);
        font-size: 1.4rem;
        animation: adminFloat 4s ease-in-out infinite;
    }

    .admin-header::before {
        content: '✦';
        top: 10px;
        left: 14px;
    }

    .admin-header::after {
        content: '◈';
        top: 10px;
        right: 14px;
        animation-direction: reverse;
    }

    .admin-header-inner {
        width: min(1160px, calc(100% - 1rem));
        margin: 0 auto;
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 1rem;
        align-items: center;
        position: relative;
        z-index: 1;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 16px;
        padding: 0.72rem 0.82rem;
        backdrop-filter: blur(2px);
    }

    .admin-back {
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.65);
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.9px;
        border-radius: 999px;
        padding: 0.42rem 0.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }

    .admin-back:hover {
        background: rgba(255, 255, 255, 0.24);
    }

    .admin-head-copy h1 {
        font-family: 'Caveat', cursive;
        font-size: 2.08rem;
        color: #fff;
        line-height: 1;
        margin-bottom: 0.2rem;
        letter-spacing: 0.2px;
    }

    .admin-head-copy p {
        font-family: 'Manrope', sans-serif;
        color: rgba(255, 255, 255, 0.94);
        font-size: 0.84rem;
        font-weight: 600;
    }

    .admin-badge {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.55);
        border-radius: 999px;
        color: #fff;
        font-family: 'Manrope', sans-serif;
        font-size: 0.73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.9px;
        padding: 0.4rem 0.75rem;
        text-align: center;
        white-space: nowrap;
    }

    @keyframes adminFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    @media (max-width: 700px) {
        .admin-header-inner {
            grid-template-columns: 1fr;
            text-align: center;
            padding: 0.72rem;
        }

        .admin-back,
        .admin-badge {
            justify-self: center;
        }

        .admin-head-copy h1 {
            font-size: 1.8rem;
        }
    }
</style>
<header class="admin-header">
    <div class="admin-header-inner">
        <a class="admin-back" href="../home.php">Main Site</a>
        <div class="admin-head-copy">
            <h1><?php echo htmlspecialchars($adminHeaderTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($adminHeaderSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <span class="admin-badge">Admin Only</span>
    </div>
</header>
