<?php 

//logout.php

session_start();

session_destroy();

// If called via fetch/AJAX, return JSON instead of redirecting.
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_SERVER['HTTP_SEC_FETCH_MODE']) && $_SERVER['HTTP_SEC_FETCH_MODE'] === 'cors')) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

header('location:user_login.php');
?>