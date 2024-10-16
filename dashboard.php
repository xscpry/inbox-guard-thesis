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

require_once 'classes/database.php'; // Include database connection

$database = new Database();
$pdo = $database->connect();

// Fetch user's first name and email from the users table
$userId = $_SESSION['user_id'];
$stmtUser = $pdo->prepare("SELECT firstname, email FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$firstName = $user['firstname'] ?? 'User'; // Default to 'User' if not found
$email = $user['email'] ?? ''; // Fetch the email

?>
<body>
    <?php
    // Different header based on login status
    if (isset($_SESSION['user_id'])) {
        require_once 'include/header-logged-in.php'; 
    } else {
        require_once 'include/header-user.php';
    }
    ?>
    <main>
        <div class="px-5 py-5 dashboard-backdrop">
            <h1 class="pb-4 ps-5">Welcome to your Dashboard, <?php echo htmlspecialchars($firstName); ?></h1>
            
            <div class="container shadow-sm bg-white rounded py-5 px-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>All Email Detected</h4>
                    <div class="email-address" style="text-align: right;"><?php echo htmlspecialchars($email); ?></div>
                </div>

                <div class="input-group rounded py-4">
                    <input type="search" class="form-control rounded" placeholder="Search email" aria-label="Search" aria-describedby="search-addon" />
                    <span class="input-group-text border-0" id="search-addon">
                        <i class="fas fa-search"></i>
                    </span>
                </div>

                <?php
                // Fetch all emails for the logged-in user without pagination
                $stmt = $pdo->prepare("SELECT * FROM emails WHERE user_id = ?");
                $stmt->execute([$userId]);
                $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($emails) {
                    echo '<table class="table table-bordered">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Subject</th>';
                    echo '<th>Sender</th>';
                    echo '<th>Date</th>';
                    echo '<th>Classification</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';

                    // Display each email
                    foreach ($emails as $email) {
                        echo '<tr>';
                        echo '<td><a href="view-email.php?id=' . htmlspecialchars($email['message_id']) . '">' . htmlspecialchars($email['subject']) . '</a></td>';
                        echo '<td>' . htmlspecialchars($email['sender']) . '</td>';
                        echo '<td>' . htmlspecialchars($email['email_date']) . '</td>';
                        echo '<td>' . htmlspecialchars($email['classification']) . '</td>';
                        echo '</tr>';
                    }

                    echo '</tbody>';
                    echo '</table>';
                } else {
                    echo '<p>No emails found.</p>';
                }
                ?>

            </div>
        </div>
    </main>
</body>
</html>
