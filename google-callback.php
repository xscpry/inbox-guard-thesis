<?php
session_start();
require_once 'google-config.php';

// 300 secs time limit to avoid timeout issues when email processing
set_time_limit(300); 

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit();
}

// fetch oauth token using the authorization code
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    echo 'Error fetching the access token: ' . $token['error_description'];
    exit();
}

// store the access token in session
$_SESSION['access_token'] = $token;
$client->setAccessToken($token);

// get user profile from google
$oauth = new Google_Service_Oauth2($client);
$user_info = $oauth->userinfo->get();

// extract user info
$email = $user_info->email;
$google_id = $user_info->id;
$firstname = $user_info->givenName;
$lastname = $user_info->familyName;

require_once 'classes/database.php';
$database = new Database();
$pdo = $database->connect();

// check if user already exists in db
$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ?");
$stmt->execute([$google_id]);
$user = $stmt->fetch();

if (!$user) {
    // if user doesn't exist, insert new record
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, google_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$firstname, $lastname, $email, $google_id]);
    $user_id = $pdo->lastInsertId();
} else {
    $user_id = $user['id'];
}

$_SESSION['user_id'] = $user_id;
$_SESSION['firstname'] = $firstname;
$_SESSION['email'] = $email;

// redirect user to dashboard immediately after sign-in
header('Location: /dashboard.php');
exit();

// background email processing logic
function fetch_and_process_emails($client, $pdo, $user_id) {
    // initialize Gmail service and fetch the user's emails
    $gmail = new Google_Service_Gmail($client);
    $nextPageToken = null;

    // fetch emails from both inbox and spam
    $folders = ['inbox', 'spam'];

    foreach ($folders as $folder) {
        do {
            $params = ['maxResults' => 10];
            if ($nextPageToken) {
                $params['pageToken'] = $nextPageToken;
            }

            // list messages from the specified folder
            $results = $gmail->users_messages->listUsersMessages('me', array_merge($params, ['labelIds' => $folder]));

            if ($results->getMessages()) {
                foreach ($results->getMessages() as $message) {
                    $msg = $gmail->users_messages->get('me', $message->getId());
                    $email_data = $msg->getPayload()->getHeaders();

                    $subject = '';
                    $sender = '';
                    $email_date = '';

                    // retrieve subject, sender, and date from headers
                    foreach ($email_data as $header) {
                        if ($header->getName() == 'Subject') {
                            $subject = $header->getValue();
                        }
                        if ($header->getName() == 'From') {
                            $sender = $header->getValue();
                        }
                        if ($header->getName() == 'Date') {
                            $dateTime = strtotime($header->getValue());
                            $email_date = date('m/d/y h:i A', $dateTime);
                        }
                    }

                    // email body
                    $body = '';
                    $parts = $msg->getPayload()->getParts();
                    if ($parts) {
                        foreach ($parts as $part) {
                            if ($part->getMimeType() == 'text/plain') {
                                $body = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
                                break;
                            }
                        }
                    } else {
                        $body = base64_decode(strtr($msg->getPayload()->getBody()->getData(), '-_', '+/'));
                    }

                    // FastAPI for phishing classification
                    $ch = curl_init('http://localhost:8080/predict');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['body' => $body]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                    ]);

                    $response = curl_exec($ch);
                    curl_close($ch);

                    $classification = 'Unclassified'; // default
                    if ($response) {
                        $prediction = json_decode($response, true);
                        if (isset($prediction['predicted_class'])) {
                            $classification = $prediction['predicted_class'];
                        } else {
                            error_log('Unexpected prediction response: ' . print_r($response, true));
                        }
                    } else {
                        error_log('No response from classification API for message ID: ' . $message->getId());
                    }


                    // check for existing email before inserting
                    $stmt = $pdo->prepare("SELECT * FROM emails WHERE message_id = ? AND user_id = ?");
                    $stmt->execute([$message->getId(), $user_id]);
                    $existingEmail = $stmt->fetch();

                    if (!$existingEmail) {
                        $stmt = $pdo->prepare("INSERT INTO emails (user_id, message_id, subject, sender, body, classification, email_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $message->getId(), $subject, $sender, $body, $classification, $email_date]);
                    }
                }
            }

            // get the next page token to fetch more emails
            $nextPageToken = $results->getNextPageToken();
        } while ($nextPageToken);
    }
}
?>
