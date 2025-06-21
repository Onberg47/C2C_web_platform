<?php
require_once __DIR__ . '/../../config.php';

// Destroy session
$_SESSION = array();
session_destroy();

// Redirect to home
header("Location: " . BASE_URL);
exit();
?>