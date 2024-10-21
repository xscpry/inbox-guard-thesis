<!DOCTYPE html>
<html lang="en">
<?php
require_once 'timeout.php';

if (session_status() == PHP_SESSION_NONE) {
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$title = 'Dashboard - InboxGuard';
$homepage = 'active';
require_once 'include/head.php';
require_once 'include/header-logged-in.php'; 

require_once 'classes/database.php';

$database = new Database();
$pdo = $database->connect();

// fetch user's first name and email from the users table
$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT firstname, email FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$firstName = $user['firstname'] ?? 'User';
$email = $user['email'] ?? '';
?>
<body>
    <?php require_once 'include/header-logged-in.php'; ?>
    
    <main>
        <div class="px-5 py-5 bg-light">
            <h1 class="pb-4 ps-5">Welcome to your Dashboard, <?php echo htmlspecialchars($firstName); ?></h1>
            
            <div class="container dashboard-container shadow-sm bg-white rounded py-5 px-5 d-flex flex-column">
                <div class="dashboard-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-5">
                    <h4 class="mb-2 mb-md-0 w-100">All Emails Detected</h4>
                    <div class="email-address mb-2 mb-md-0 w-100">
                        Account: <?php echo htmlspecialchars($email); ?>
                    </div>
                    <form class="search-bar w-100 w-md-auto" role="search">
                        <input class="form-control bg-light" id="emailSearch" type="search" placeholder="Search email subject or sender" aria-label="Search">
                    </form>
                </div>
                <div id="emailList">
                    <?php
                    // fetch all emails for the logged-in user without pagination
                    $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ? ORDER BY email_date DESC");
                    $stmt->execute([$userId]);
                    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($emails) {
                        foreach ($emails as $email) {
                            echo '<div class="email-container border rounded p-3 mb-3" onclick="window.location.href=\'view-email.php?id=' . htmlspecialchars($email['message_id']) . '\'">';
                            echo '<div class="subject font-weight-bold">' . htmlspecialchars($email['subject']) . '</div>';
                            echo '<div class="details text-muted">';
                            $classificationClass = $email['classification'] === 'Safe' ? 'classification-safe' : 'classification-malicious';
                            echo htmlspecialchars($email['sender']) . ' • ' . htmlspecialchars($email['email_date']) . ' • <span class="' . $classificationClass . '">' . htmlspecialchars($email['classification']) . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No emails found.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.getElementById('emailSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const emails = document.querySelectorAll('.email-container');
            if (filter === '') {
                emails.forEach(email => {
                    email.style.display = '';
                });
            } else {
                emails.forEach(email => {
                    const subject = email.querySelector('.subject').textContent.toLowerCase();
                    const sender = email.querySelector('.details').textContent.toLowerCase();
                    if (subject.includes(filter) || sender.includes(filter)) {
                        email.style.display = '';
                    } else {
                        email.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
    <?php require_once 'include/footer.php';?>
</html>