<!DOCTYPE html>
<html lang="en">
<?php
    session_start();
    $timeout_duration = 5 * 60;
    // session exists and still valid?
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: index.php');
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }

    $title = 'Inbox Guard';
    $homepage = 'active';
    require_once 'include/head.php';
    require_once 'include/header-logged-in.php'; 
?>
<body>
    <?php
        // diff header based on login status
        if (isset($_SESSION['user_id'])) {
            require_once 'include/header-logged-in.php'; 
        } else {
            require_once 'include/header-user.php';
        }
    ?>
    <main>
        <h1>Welcome to your Dashboard</h1>
        <h2>Your Gmail Messages</h2>
        <ul>
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $message): ?>
                    <?php
                    // fetch detail of each mssg
                    try {
                        $messageDetail = $service->users_messages->get('me', $message->getId());
                        $headers = $messageDetail->getPayload()->getHeaders();
                        $subject = 'No Subject';
                        foreach ($headers as $header) {
                            if ($header->getName() === 'Subject') {
                                $subject = $header->getValue();
                                break;
                            }
                        }
                    } catch (Exception $e) {
                        $subject = 'Error retrieving subject';
                    }
                    ?>
                    <li><?php echo htmlspecialchars($subject); ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No messages found.</li>
            <?php endif; ?>
        </ul>
    </main>
</body>
</html>