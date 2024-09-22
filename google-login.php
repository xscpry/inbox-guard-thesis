<?php
require_once 'google-config.php';
require_once 'vendor/autoload.php'; 

$auth_url = $client->createAuthUrl();
header('Location: ' . $auth_url);
exit();
?>