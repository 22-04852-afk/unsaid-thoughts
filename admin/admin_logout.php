<?php
require_once __DIR__ . '/admin_auth.php';

unset($_SESSION['is_admin'], $_SESSION['admin_user'], $_SESSION['admin_csrf_token']);

header('Location: admin_login.php');
exit;
