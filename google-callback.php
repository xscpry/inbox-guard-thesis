<?php
session_start();
require_once 'google-config.php';

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit();
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    echo 'Error fetching the access token: ' . $token['error_description'];
    exit();
}

$_SESSION['access_token'] = $token;
$client->setAccessToken($token);

// get user profile from google
$oauth = new Google_Service_Oauth2($client);
$user_info = $oauth->userinfo->get();

$email = $user_info->email;
$google_id = $user_info->id;
$firstname = $user_info->givenName;
$lastname = $user_info->familyName;

require_once 'classes/database.php';

$database = new Database();
$pdo = $database->connect();

// check if user already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
$stmt->execute([$google_id]);
$user = $stmt->fetch();

if (!$user) {
    // user doesn't exist, create a new user
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, google_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$firstname, $lastname, $email, $google_id]);
    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $user['id'];
}

$_SESSION['user_id'] = $user_id;
header('Location: /dashboard.php');
exit();
?>