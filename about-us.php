<!DOCTYPE html>
<html lang="en">
<?php

session_start();

$title = 'About Us - InboxGuard';
$aboutus = 'active';
require_once 'include/head.php';
?>
<body class="bg-light">
<?php
        if (isset($_SESSION['user_id'])) {
            require_once 'include/header-logged-in.php';
        } else {
            require_once 'include/header-user.php';
        }
    ?>
    <section class="about-us-section">
        <div class="container">
            <div class="row my-5">
                <div class="col">
                    <div class="container shadow-sm p-4 bg-white rounded">
                        <div class="mb-4">
                            <p style="color: red;">How it started</p>
                            <h1>The dream is to create a safer digital world</h1>
                        </div>
                        <div class="mb-4">
                        <p>
                        This prevention tool works exclusively with Gmail, taking swift action to neutralize risks and protect sensitive data from potential breaches, financial loss, or reputational damage.
                        </p>
                        </div>
                        <div class="mb-4">
                        <p>
                        At InboxGuard, the mission is to protect users from malicious email-based threats by providing a robust security solution. It leverages advanced machine learning techniques, particularly the Random Forest algorithm, to analyze and classify email content, ensuring that suspicious and harmful emails are detected before they can cause any damage. This innovative tool actively scans and evaluates the email body content to prevent users from falling victim to cyber attacks.
                        </p>
                        </div>
                        <div class="mb-1">
                        <p>InboxGuard is the result of dedicated research aimed at enhancing email security. Emerging from a thesis project titled 
                        <span class="italic">"Defending the Inbox: A Prevention Application for Deterring Email-Based Attacks,"</span> this tool was developed as part of the fulfillment of the 
                        Bachelor of Science in Computer Science degree at the College of Computing Studies in Western Mindanao State University. Created by 
                        <span class="bold">Sunshine O. Casido</span>, InboxGuard is designed to protect Gmail users from malicious email attacks, ensuring the safety and reliability of their inbox.
                        </p>
                        </div>
                    </div>                    
                </div>
                <div class="col">
                    <div class="container shadow-sm p-1 bg-white rounded mb-4">
                        <img src="img/about-us-pic.jpg" class="home-img rounded" style="object-fit:cover;" alt="">
                    </div>
                    <div class="container shadow-sm bg-white rounded px-4 py-2 statistics">
                        <div class="row">
                            <div class="col container bg-light text-dark rounded m-2 p-2">
                                <h2 style="font-weight: bold;">500</h2>
                                <p>Emails scanned daily to ensure your inbox is secure</p>
                            </div>
                            <div class="col container bg-light text-dark rounded m-2 p-2">
                                <h2 style="font-weight: bold;">95.30%</h2>
                                <p>Accuracy rate achieved in identifying malicious emails</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col container bg-light text-dark rounded m-2 p-2">
                                <h2 style="font-weight: bold;">50+</h2>
                                <p>Users protected during initial testing phases</p>
                            </div>
                            <div class="col container bg-light text-dark rounded m-2 p-2">
                                <h2 style="font-weight: bold;">100+</h2>
                                <p>Phishing attempts prevented in test environments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>