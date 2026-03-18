<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$loggedIn = function_exists('isAdminLoggedIn') ? isAdminLoggedIn() : false;
?>
<style>
    :root {
        --admin-sidebar-width: 250px;
        --admin-sidebar-width-compact: 84px;
        --admin-header-bottom: 96px;
    }

    body {
        --admin-layout-offset: calc(var(--admin-sidebar-width) + 14px);
        padding-top: calc(var(--admin-header-bottom) + 10px);
    }

    .wrap,
    main.admin-content {
        margin-left: var(--admin-layout-offset);
        transition: margin-left 0.32s cubic-bezier(0.22, 1, 0.36, 1);
        will-change: margin-left;
    }

    body.admin-sidebar-hidden .wrap,
    body.admin-sidebar-hidden main.admin-content {
        margin-left: auto;
        margin-right: auto;
    }

    main.login-card,
    main.card {
        margin-left: auto;
        margin-right: auto;
        transition: margin-left 0.32s cubic-bezier(0.22, 1, 0.36, 1);
    }

    body.admin-mobile-nav-open {
        overflow: hidden;
    }

    .admin-sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(31, 7, 22, 0.46);
        backdrop-filter: blur(2px);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.22s ease;
        z-index: 2990;
    }

    .admin-hamburger {
        position: fixed;
        top: calc(var(--admin-header-bottom) + 18px);
        left: calc(10px + var(--admin-sidebar-width) + 8px);
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.75);
        background: linear-gradient(135deg, var(--admin-brand) 0%, var(--admin-brand-strong) 100%);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 12px 20px rgba(163, 17, 92, 0.28);
        z-index: 3200;
        transition: left 0.28s ease, transform 0.18s ease, box-shadow 0.18s ease;
    }

    body.admin-sidebar-hidden .admin-hamburger {
        left: 12px;
    }

    .admin-hamburger:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 24px rgba(163, 17, 92, 0.33);
    }

    .admin-hamburger:focus-visible {
        outline: 3px solid rgba(255, 255, 255, 0.86);
        outline-offset: 2px;
    }

    .admin-sidebar {
        position: fixed;
        top: calc(var(--admin-header-bottom) + 10px);
        bottom: 10px;
        left: 10px;
        width: var(--admin-sidebar-width);
        background: linear-gradient(180deg, var(--admin-brand) 0%, var(--admin-brand-strong) 100%);
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 20px;
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        box-shadow: 0 24px 40px rgba(132, 8, 75, 0.33);
        backdrop-filter: blur(9px);
        z-index: 3000;
        overflow: hidden;
        transition: transform 0.28s ease, opacity 0.2s ease;
    }

    body.admin-sidebar-hidden .admin-sidebar {
        transform: translateX(calc(-100% - 24px));
        opacity: 0;
        pointer-events: none;
    }

    .admin-sidebar-title {
        color: #fff;
        font-size: 0.74rem;
        letter-spacing: 1.1px;
        font-weight: 900;
        text-transform: uppercase;
        opacity: 0.86;
        margin: 0.15rem 0 0.45rem;
        padding: 0 0.35rem;
    }

    .admin-sidebar a {
        text-decoration: none;
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.3px;
        font-size: 0.78rem;
        text-transform: uppercase;
        border-radius: 13px;
        padding: 0.6rem 0.7rem;
        border: 1px solid rgba(255, 255, 255, 0.17);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.52rem;
        background: rgba(255, 255, 255, 0.08);
        white-space: nowrap;
        width: 100%;
        position: relative;
    }

    .admin-sidebar a .nav-ico {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.24);
        color: #fff;
        font-size: 0.72rem;
        line-height: 1;
        flex: 0 0 auto;
    }

    .admin-sidebar a:hover {
        background: rgba(255, 255, 255, 0.19);
        border-color: rgba(255, 255, 255, 0.35);
        transform: translateX(2px);
    }

    .admin-sidebar a.active {
        color: var(--admin-brand-strong);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.97) 0%, rgba(255, 240, 249, 0.97) 100%);
        border-color: rgba(255, 255, 255, 0.9);
        box-shadow: 0 10px 22px rgba(120, 10, 67, 0.25);
    }

    .admin-sidebar a.active .nav-ico {
        background: var(--admin-brand);
        color: #fff;
    }

    .admin-sidebar a.active::before {
        content: '';
        position: absolute;
        left: -0.55rem;
        top: 50%;
        width: 0.38rem;
        height: 60%;
        transform: translateY(-50%);
        border-radius: 0 999px 999px 0;
        background: #fff;
    }

    .admin-sidebar-footer {
        margin-top: auto;
        padding-top: 0.65rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 980px) {
        body {
            --admin-layout-offset: calc(var(--admin-sidebar-width-compact) + 10px);
        }

        .admin-sidebar {
            width: var(--admin-sidebar-width-compact);
            padding: 0.6rem 0.45rem;
            align-items: center;
        }

        .admin-hamburger {
            left: calc(10px + var(--admin-sidebar-width-compact) + 8px);
        }

        .admin-sidebar-title,
        .admin-sidebar a span:not(.nav-ico) {
            display: none;
        }

        .admin-sidebar a {
            width: 100%;
            justify-content: center;
            padding: 0.6rem 0.4rem;
            gap: 0;
        }

        .admin-sidebar a.active::before {
            left: -0.45rem;
        }
    }

    @media (max-width: 640px) {
        body {
            padding-bottom: 84px;
        }

        .wrap,
        main.login-card,
        main.card {
            transform: translateX(0);
        }

        .admin-hamburger {
            left: 12px;
            top: 12px;
        }

        .admin-sidebar {
            top: auto;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: auto;
            border-radius: 16px 16px 0 0;
            padding: 0.5rem 0.6rem calc(0.5rem + env(safe-area-inset-bottom));
            flex-direction: row;
            gap: 0.35rem;
            overflow-x: auto;
        }

        body.admin-mobile-nav-open .admin-sidebar-backdrop {
            opacity: 1;
            pointer-events: auto;
        }

        .admin-sidebar-title,
        .admin-sidebar-footer {
            display: none;
        }

        .admin-sidebar a {
            flex: 0 0 auto;
            width: auto;
        }

        .admin-sidebar a span:not(.nav-ico) {
            display: inline;
        }

        .admin-sidebar a.active::before {
            display: none;
        }

        body.admin-sidebar-hidden .admin-sidebar {
            transform: translateY(calc(100% + 22px));
            opacity: 0;
        }
    }
</style>
<button class="admin-hamburger" type="button" aria-controls="adminSidebarNav" aria-expanded="true" title="Toggle sidebar">
    <span class="hamburger-icon">X</span>
</button>
<div class="admin-sidebar-backdrop" aria-hidden="true"></div>
<nav class="admin-sidebar" id="adminSidebarNav">
    <p class="admin-sidebar-title">Control</p>
    <a href="dashboard-admin.php" class="<?php echo in_array($currentFile, ['dashboard-admin.php', 'admin.php', 'home.php'], true) ? 'active' : ''; ?>"><span class="nav-ico">⌂</span><span>dashboard-admin.php</span></a>
    <a href="settings-admin.php" class="<?php echo $currentFile === 'settings-admin.php' ? 'active' : ''; ?>"><span class="nav-ico">⚙</span><span>settings-admin.php</span></a>
    <a href="admin_register.php" class="<?php echo $currentFile === 'admin_register.php' ? 'active' : ''; ?>"><span class="nav-ico">＋</span><span>Create Admin</span></a>

    <div class="admin-sidebar-footer">
        <?php if ($loggedIn): ?>
            <a href="admin_logout.php"><span class="nav-ico">↗</span><span>Log Out</span></a>
        <?php else: ?>
            <a href="admin_login.php" class="<?php echo $currentFile === 'admin_login.php' ? 'active' : ''; ?>"><span class="nav-ico">●</span><span>Sign In</span></a>
        <?php endif; ?>
    </div>
</nav>
<script>
    (function () {
        var body = document.body;
        var button = document.querySelector('.admin-hamburger');
        var icon = document.querySelector('.hamburger-icon');
        var backdrop = document.querySelector('.admin-sidebar-backdrop');
        if (!body || !button || !icon || !backdrop) {
            return;
        }

        var storageKey = 'admin_sidebar_hidden';
        var root = document.documentElement;

        function updateHeaderOffset() {
            var header = document.querySelector('.admin-header');
            var height = header ? Math.ceil(header.getBoundingClientRect().height) : 0;
            root.style.setProperty('--admin-header-bottom', height + 'px');
        }

        function isMobileViewport() {
            return window.matchMedia('(max-width: 640px)').matches;
        }

        function render(hidden) {
            body.classList.toggle('admin-sidebar-hidden', hidden);
            button.setAttribute('aria-expanded', (!hidden).toString());
            icon.textContent = hidden ? '☰' : 'X';

            var showMobileBackdrop = isMobileViewport() && !hidden;
            body.classList.toggle('admin-mobile-nav-open', showMobileBackdrop);
        }

        var savedHidden = false;
        try {
            savedHidden = localStorage.getItem(storageKey) === '1';
        } catch (e) {
            savedHidden = false;
        }

        render(savedHidden);
        updateHeaderOffset();

        button.addEventListener('click', function () {
            var hidden = !body.classList.contains('admin-sidebar-hidden');
            render(hidden);
            try {
                localStorage.setItem(storageKey, hidden ? '1' : '0');
            } catch (e) {
                // Ignore storage failures.
            }
        });

        backdrop.addEventListener('click', function () {
            render(true);
            try {
                localStorage.setItem(storageKey, '1');
            } catch (e) {
                // Ignore storage failures.
            }
        });

        window.addEventListener('resize', function () {
            var hidden = body.classList.contains('admin-sidebar-hidden');
            updateHeaderOffset();
            render(hidden);
        });
    })();
</script>
