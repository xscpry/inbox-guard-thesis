<?php
    require_once 'vendor/autoload.php';
    Dotenv\Dotenv::createImmutable(__DIR__)->load();

    // initialize
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri('http://localhost:80/google-callback.php');
    $client->addScope('email');
    $client->addScope('profile');
    $client->addScope(Google_Service_Gmail::GMAIL_READONLY);

    require_once 'timeout.php';

?>