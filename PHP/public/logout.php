<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Auth.php';

$db = new Database();
$auth = new Auth($db);

$auth->logout();
header('Location: login.php'); // Nach dem Logout zur Login-Seite umleiten
exit();
?>
