<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();  // only start session if none already exists
}

$timeout_duration = 3 * 60 * 60; // 3hrs

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>
