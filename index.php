<!DOCTYPE html>
<html lang="en">
<?php
    session_start();    
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit();
    }
    
    $title = 'Inbox Guard';
    $homepage = 'active';
    require_once 'include/head.php';
?>
<body>
    <?php
        if (isset($_SESSION['user_id'])) {
            require_once 'include/header-logged-in.php';
        } else {
            require_once 'include/header-user.php';
        }
    ?>
    <section class="home-banner">
        <div class="container">
            <div class="row">
                <div class="col-md-6 index-col mt-4">
                    <div class="mb-2">
                        <h1><span class="inbox">Inbox</span><span class="guard">Guard</span></h1>
                    </div>
                    <div class="mb-4">
                        <h1 class="banner-title">Email Protection Platform</h1>
                    </div>
                    <div class="mb-4">
                        <h5>We provide users with strong protection against malicious email-based attacks. InboxGuard will ensure the 
                        security and integrity of your data.</h5>
                    </div>
                    <div class="mb-4">
                        <h5>Join us now and experience a safer, more secure inbox!</h5>
                    </div>

                    <a href="google-login.php" class="btn">Login with Google</a>
                </div>
                <div class="col-md-6 text-center">
                    <img src="img/banner.jpg" class="img-fluid home-img" alt="">
                </div>
            </div>
        </div>
    </section>
</body>
</html>