<?php
require_once 'includes/auth.php';

$auth = new Auth($pdo);
$auth->logout();
?>