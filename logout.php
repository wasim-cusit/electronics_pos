<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';
logout();
header('Location: ' . $base_url . 'login.php');
exit;