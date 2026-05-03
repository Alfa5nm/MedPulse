<?php
session_start();


$public_pages = ['login.php', 'register.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header("Location: login.php");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

function isDoctor() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'Doctor' || $_SESSION['role'] === 'Admin');
}
?>
