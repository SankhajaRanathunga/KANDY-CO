<?php
/**
 * KANDY CO. - Admin Logout Script
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUserSession();
header('Location: login.php');
exit();
