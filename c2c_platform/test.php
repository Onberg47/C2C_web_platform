<?php
require_once 'config.php';
$_SESSION['test'] = 'works';
header("Location: test_redirect.php");