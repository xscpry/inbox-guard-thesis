<?php
session_start();
require_once 'classes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$title = 'View Email - InboxGuard';
$homepage = 'active';
require_once 'include/head.php';
require_once 'include/header-logged-in.php'; 

$database = new Database();
$pdo = $database->connect();

// fetch the email by message_id
$stmt = $pdo->prepare("SELECT * FROM emails WHERE message_id = ? AND user_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$email = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$email) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Email</title>
</head>
<body>


<div class="view-email-container">
    <h1><?= htmlspecialchars($email['subject']) ?></h1>
    <p><?= htmlspecialchars($email['sender']) ?> | <?= htmlspecialchars($email['email_date']) ?></p>
    
    <p>
        <?php if ($email['classification'] === 'Safe'): ?>
            <span class="classification-safe"><?= htmlspecialchars($email['classification']) ?></span>
        <?php elseif ($email['classification'] === 'Phishing'): ?>
            <span class="classification-malicious">Malicious</span> 
        <?php else: ?>
            <span class="classification-default"><?= htmlspecialchars($email['classification']) ?></span>
        <?php endif; ?>
    </p>
    <hr>
    <div class="email-body"><?= htmlspecialchars($email['body']) ?></div>
</div>

</body>
</html>