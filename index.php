<?php
/**
 * Landing Page — redirects based on login status
 */
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $role = $_SESSION['role'];
    header("Location: /ap/$role/");
    exit;
}

header('Location: /ap/login.html');
exit;
