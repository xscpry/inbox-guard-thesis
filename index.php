<!DOCTYPE html>
<html lang="en">
<?php
    session_start();    
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit();
    }
    
    $title = 'InboxGuard - Email Protection System';
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
    <section class="home-banner mb-4">
        <div class="container">
            <div class="row">
                <div class="col mt-5">
                    <div class="my-2">
                        <h1><span class="inbox">Inbox</span><span class="guard">Guard</span></h1>
                    </div>
                    <div class="mb-4">
                        <h1 class="banner-title">Email Protection System</h1>
                    </div>
                    <div class="mb-4">
                        <h5>We provide users with strong protection against malicious email-based attacks. InboxGuard will ensure the 
                        security and integrity of your data.</h5>
                    </div>
                    <div class="mb-4">
                        <h5>Join us now and experience a safer, more secure inbox!</h5>
                    </div>
                    
                    <a href="google-login.php" class="btn my-2">Login with Google</a>

                </div>
                <div class="col mt-5 text-center">
                    <img src="img/banner.jpg" class="img-fluid home-img" alt="">
                </div>
            </div>
        </div>
    </section>  
    <section class="section-banner">
        <div class="container">
            <div class="row">
                <div class="col">
                    <div class="mb-4">
                        <h1 class="banner-title">How It Works</h1>
                    </div>
                    <img src="img/sectionbanner.png" class="img-fluid section-banner-img" alt="">
                    <div class="mt-5">
                        <h5>Our system scans email content, detecting potential threats before they reach your inbox. With advanced algorithms, InboxGuard actively identifies phishing attempts and suspicious content, ensuring your safety.</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="section-banner my-5">
        <class class="container features">
            <div class="row">
                <div class="col container shadow-sm bg-white rounded m-2 p-4">
                    <i class="fas fa-shield"></i>
                    <h4 style="font-weight: bold;">Email Threat Detection</h4>
                    <p>Detect and block phishing attempts, suspicious links, and harmful attachments in real time.</p>
                </div>
                <div class="col container shadow-sm bg-white rounded m-2 p-4">
                    <i class="fas fa-search"></i>
                    <h4 style="font-weight: bold;">Advanced Email Analysis</h4>
                    <p>InboxGuard uses a smart algorithm to analyze and classify email content for more security.</p>
                </div>
                <div class="col container shadow-sm bg-white rounded m-2 p-4">
                    <i class="fas fa-database"></i>
                    <h4 style="font-weight: bold;">Transparent Data Use</h4>
                    <p>We only access what’s needed to secure your inbox, keeping privacy and transparency at our core.</p>
                </div>
            </div>
        </class>
    </section>
    <section class="section-banner">
        <div class="container">
            <div class="row">
                <div class="col">
                    <div class="mb-4">
                        <h1 class="banner-title">Our Story</h1>
                    </div>
                    <div class="my-5">
                        <h5>Developed from a research project aimed at defending against email-based attacks, InboxGuard is designed to keep Gmail users safe. Learn more about our journey and mission to create a safer digital world.</h5>
                    </div>
                    
                    <a href="about-us.php" class="btn my-2">Learn More</a>

                </div>
            </div>
        </div>
    </section>
    
    <div class="homepage-privacy" style="text-align: center; margin: 1%">
        <p>We value your privacy. Read our <a href="https://www.inboxguard.xscpry.com/privacy-policy.php" target="_blank" style="color: #007bff; text-decoration: underline;">Privacy Policy</a> to learn more.</p>
    </div>
</body>
    <?php require_once 'include/footer.php';?>
</html>