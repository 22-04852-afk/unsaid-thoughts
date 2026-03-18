<?php
require_once __DIR__ . '/admin_auth.php';
requireAdminAuth();

header('Location: dashboard-admin.php');
exit;
