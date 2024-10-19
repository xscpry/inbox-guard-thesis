<?php
session_start();
require_once 'google-config.php';

set_time_limit(300); // 300secs to avoid timeout issues

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit();
}

// Fetch the OAuth token using the authorization code
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    echo 'Error fetching the access token: ' . $token['error_description'];
    exit();
}

// Store the access token in session
$_SESSION['access_token'] = $token;
$client->setAccessToken($token);

// Get user profile from Google
$oauth = new Google_Service_Oauth2($client);
$user_info = $oauth->userinfo->get();

// Extract user information
$email = $user_info->email;
$google_id = $user_info->id;
$firstname = $user_info->givenName;
$lastname = $user_info->familyName;

require_once 'classes/database.php';
$database = new Database();
$pdo = $database->connect();

// Check if the user already exists in the database
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
$stmt->execute([$google_id]);
$user = $stmt->fetch();

if (!$user){
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, google_id, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$firstname, $lastname, $email, $google_id, $created_at]);
    $user_id = $pdo->lastInsertId();
}else{
    $user_id = $user['id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['firstname'] = $firstname;
$_SESSION['email'] = $email;

// Fetch and process emails after user sign-in
fetch_and_process_emails($client, $pdo, $user_id);

// Redirect user to dashboard immediately after sign in
header('Location: /dashboard.php');
exit();

// Fetch and process emails function
function fetch_and_process_emails($client, $pdo, $user_id){
    $gmail = new Google_Service_Gmail($client);
    $maxResults = 20; // maximum number of emails to fetch
    $fetchedEmails = 0; // counter for fetched emails
    $nextPageToken = null;

    do {
        $params = ['maxResults' => 10]; // fetch in batches of 10
        if ($nextPageToken){
            $params['pageToken'] = $nextPageToken;
        }

        $results = $gmail->users_messages->listUsersMessages('me', $params);

        if ($results->getMessages()){
            foreach ($results->getMessages() as $message){
                $msg = $gmail->users_messages->get('me', $message->getId());
                $email_data = $msg->getPayload()->getHeaders();

                $subject = '';
                $sender = '';
                $email_date = '';

                foreach ($email_data as $header){
                    if ($header->getName() == 'Subject'){
                        $subject = $header->getValue();
                    } if ($header->getName() == 'From'){
                        $sender = $header->getValue();
                    } if ($header->getName() == 'Date'){
                        $email_date = date('Y-m-d H:i:s', strtotime($header->getValue()));
                    }
                }

                $body = '';
                $parts = $msg->getPayload()->getParts();
                if ($parts){
                    foreach ($parts as $part){
                        if ($part->getMimeType() == 'text/plain'){
                            $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                            break;
                        }
                    }
                } else {
                    // handle case where no parts are available
                    $body = base64_decode(strtr($msg->getPayload()->getBody()->getData(), '-_', '+/'));
                }

                // FastAPI for phishing classification
                $ch = curl_init('http://localhost:8082/predict');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['body' => $body]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                ]);

                $response = curl_exec($ch);
                curl_close($ch);

                $classification = 'Unknown'; // default
                if ($response) {
                    $prediction = json_decode($response, true);
                    if (isset($prediction['predicted_class'])) {
                        $classification = $prediction['predicted_class']; // set classification based on API response
                    }
                } else {
                    error_log('Failed to get prediction from the API for message ID: ' . $message->getId());
                }

                // Check for existing email before inserting
                $stmt = $pdo->prepare("SELECT * FROM emails WHERE message_id = ? AND user_id = ?");
                $stmt->execute([$message->getId(), $user_id]);
                $existingEmail = $stmt->fetch();

                // Insert if the email does not exist
                if (!$existingEmail){
                    // Insert email with date
                    $stmt = $pdo->prepare("INSERT INTO emails (user_id, message_id, subject, sender, body, classification, timestamp, email_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"); 
                    $stmt->execute([$user_id, $message->getId(), $subject, $sender, $body, $classification, time(), $email_date]);
                }

                $fetchedEmails++; // Increment the counter for fetched emails
                // Stop if we have fetched the desired number of emails
                if ($fetchedEmails >= $maxResults) {
                    break 2; // Break out of both the foreach and do...while loop
                }
            }
        }

        // Get the next page token to fetch more emails
        $nextPageToken = $results->getNextPageToken();
    } while ($nextPageToken);
}
// Optionally, call this function in an AJAX request or via cron job for asynchronous email processing
?>