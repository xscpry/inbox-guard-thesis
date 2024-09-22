<?php
session_start();
session_unset();
session_destroy();

require_once 'google-config.php';
$client->revokeToken();

header('Location: index.php');
exit();
?>