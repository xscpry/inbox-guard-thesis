<?php
    require_once 'vendor/autoload.php';

    // initialize
    $client = new Google_Client();
    $client->setClientId('760627622675-e923fkfqppr56bom2np50tk05c1982t7.apps.googleusercontent.com');
    $client->setClientSecret('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');
    $client->setRedirectUri('http://localhost:80/google-callback.php');
    $client->addScope('email');
    $client->addScope('profile');
    $client->addScope(Google_Service_Gmail::GMAIL_READONLY);

    require_once 'timeout.php';

?>