<?php
require_once __DIR__ . '/admin_auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard-admin.php');
    exit;
}

header('Location: admin_login.php');
exit;
