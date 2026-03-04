<?php
// public/logout.php
require_once __DIR__ . '/../Core/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;