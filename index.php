<?php
session_start();

$is_logged_in = isset($_SESSION["user_id"]);
$user_name = $_SESSION["user_name"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nagar Sewa | Digital Municipal Services</title>

    <link rel="stylesheet" href="index.css">
</head>

<body>

<!-- ================= NAVBAR ================= -->

<header class="navbar">

    <div class="nav-container">

        <a href="index.php" class="logo">

            <div class="logo-icon">
                🏛️
            </div>

            <div class="logo-text">
                <h2>Nagar Sewa</h2>
                <span>Digital Municipal Services</span>
            </div>

        </a>


        <nav class="nav-menu">

            <a href="index.php" class="active">
                Home
            </a>

            <a href="#services">
                Services
            </a>

            <a href="#about">
                About
            </a>

            <a href="#how-it-works">
                How It Works
            </a>

            <a href="#contact">
                Contact
            </a>


            <?php if ($is_logged_in): ?>

                <a href="citizen/dashboard.php" class="nav-dashboard">
                    Dashboard
                </a>

                <a href="auth/logout.php" class="nav-login">
                    Logout
                </a>

            <?php else: ?>

                <a href="auth/login.php" class="nav-login">
                    Login
                </a>

                <a href="auth/register.php" class="nav-register">
                    Register
                </a>

            <?php endif; ?>

        </nav>


        <button
            type="button"
            class="mobile-menu-btn"
            onclick="toggleMenu()"
        >
            ☰
        </button>

    </div>

</header>


<!-- ================= MOBILE MENU ================= -->

<div class="mobile-menu" id="mobileMenu">

    <a href="index.php">
        Home
    </a>

    <a href="#services">
        Services
    </a>

    <a href="#about">
        About
    </a>

    <a href="#how-it-works">
        How It Works
    </a>

    <a href="#contact">
        Contact
    </a>


    <?php if ($is_logged_in): ?>

        <a href="citizen/dashboard.php">
            Dashboard
        </a>

        <a href="auth/logout.php">
            Logout
        </a>

    <?php else: ?>

        <a href="auth/login.php">
            Login
        </a>

        <a href="auth/register.php">
            Register
        </a>

    <?php endif; ?>

</div>


<!-- ================= HERO ================= -->

<section class="hero">

    <div class="hero-container">


        <div class="hero-content">

            <div class="hero-badge">
                <span>●</span>
                Digital Municipal Service Portal
            </div>


            <h1>
                Your City Services,
                <span>Anytime, Anywhere.</span>
            </h1>


            <p>
                Nagar Sewa makes municipal services simple, fast and
                accessible. Apply for certificates, submit documents,
                track applications and download approved certificates
                online.
            </p>


            <div class="hero-buttons">

                <?php if ($is_logged_in): ?>

                    <a
                        href="citizen/services.php"
                        class="btn btn-primary"
                    >
                        Apply for a Service
                        <span>→</span>
                    </a>

                    <a
                        href="citizen/dashboard.php"
                        class="btn btn-secondary"
                    >
                        Go to Dashboard
                    </a>

                <?php else: ?>

                    <a
                        href="auth/register.php"
                        class="btn btn-primary"
                    >
                        Get Started
                        <span>→</span>
                    </a>

                    <a
                        href="auth/login.php"
                        class="btn btn-secondary"
                    >
                        Login
                    </a>

                <?php endif; ?>

            </div>


            <div class="hero-trust">

                <div class="trust-item">
                    <strong>100%</strong>
                    <span>Online Process</span>
                </div>

                <div class="trust-divider"></div>

                <div class="trust-item">
                    <strong>24/7</strong>
                    <span>Service Access</span>
                </div>

                <div class="trust-divider"></div>

                <div class="trust-item">
                    <strong>Secure</strong>
                    <span>Data Protection</span>
                </div>

            </div>

        </div>


        <!-- ================= HERO VISUAL ================= -->

        <div class="hero-visual">

            <div class="hero-circle"></div>


            <div class="city-card">

                <div class="city-card-header">

                    <div class="mini-logo">
                        🏛️
                    </div>

                    <div>
                        <strong>Nagar Sewa</strong>
                        <small>Municipal Portal</small>
                    </div>

                    <span class="online-dot"></span>

                </div>


                <div class="building">

                    <div class="building-roof"></div>

                    <div class="building-body">

                        <div class="pillar"></div>
                        <div class="pillar"></div>
                        <div class="pillar"></div>
                        <div class="pillar"></div>
                        <div class="pillar"></div>

                    </div>

                    <div class="building-door"></div>

                </div>


                <div class="service-preview">

                    <div class="preview-item">

                        <span>📜</span>

                        <div>
                            <strong>Certificates</strong>
                            <small>Apply Online</small>
                        </div>

                    </div>


                    <div class="preview-item">

                        <span>📋</span>

                        <div>
                            <strong>Applications</strong>
                            <small>Track Status</small>
                        </div>

                    </div>


                    <div class="preview-item">

                        <span>📥</span>

                        <div>
                            <strong>Documents</strong>
                            <small>Download</small>
                        </div>

                    </div>

                </div>

            </div>


            <div class="floating-card floating-card-one">

                <span class="floating-icon">
                    ✓
                </span>

                <div>
                    <strong>Application Approved</strong>
                    <small>Your certificate is ready</small>
                </div>

            </div>


            <div class="floating-card floating-card-two">

                <span class="floating-icon">
                    🔒
                </span>

                <div>
                    <strong>Secure Portal</strong>
                    <small>Your information is protected</small>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= SERVICES ================= -->

<section class="services-section" id="services">

    <div class="section-container">

        <div class="section-heading">

            <span class="section-label">
                OUR SERVICES
            </span>

            <h2>
                Municipal Services Made Simple
            </h2>

            <p>
                Access important government and municipal services
                without visiting the office.
            </p>

        </div>


        <div class="services-grid">


            <div class="service-card">

                <div class="service-icon birth">
                    👶
                </div>

                <h3>
                    Birth Certificate
                </h3>

                <p>
                    Apply for a birth certificate online and
                    track your application status.
                </p>

                <a href="citizen/services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon death">
                    📜
                </div>

                <h3>
                    Death Certificate
                </h3>

                <p>
                    Submit death certificate applications and
                    required documents online.
                </p>

                <a href="citizen/services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon marriage">
                    💍
                </div>

                <h3>
                    Marriage Registration
                </h3>

                <p>
                    Register your marriage and submit supporting
                    documents digitally.
                </p>

                <a href="citizen/services.php">
                    Apply Now →
                </a>

            </div>


            <div class="service-card">

                <div class="service-icon migration">
                    🏠
                </div>

                <h3>
                    Migration Certificate
                </h3>

                <p>
                    Apply for migration and residence-related
                    certificates online.
                </p>

                <a href="citizen/services.php">
                    Apply Now →
                </a>

            </div>

        </div>

    </div>

</section>


<!-- ================= ABOUT ================= -->

<section class="about-section" id="about">

    <div class="section-container about-container">


        <div class="about-visual">

            <div class="about-main-card">

                <div class="about-building">
                    🏛️
                </div>

                <h3>
                    Digital Municipality
                </h3>

                <p>
                    Connecting citizens with municipal services
                    through technology.
                </p>


                <div class="about-stats">

                    <div>
                        <strong>24/7</strong>
                        <span>Access</span>
                    </div>

                    <div>
                        <strong>100%</strong>
                        <span>Digital</span>
                    </div>

                    <div>
                        <strong>Fast</strong>
                        <span>Service</span>
                    </div>

                </div>

            </div>

        </div>


        <div class="about-content">

            <span class="section-label">
                ABOUT NAGAR SEWA
            </span>

            <h2>
                Building a Better Digital
                <span>Municipality</span>
            </h2>

            <p>
                Nagar Sewa is a digital municipal service platform
                designed to make public services easier and more
                accessible for citizens.
            </p>

            <p>
                Citizens can submit applications, upload documents,
                check application status, receive notifications and
                download approved certificates from one convenient
                online platform.
            </p>


            <div class="about-features">

                <div class="about-feature">

                    <span>✓</span>

                    <div>
                        <strong>Easy Online Applications</strong>

                        <small>
                            Apply for services from anywhere.
                        </small>
                    </div>

                </div>


                <div class="about-feature">

                    <span>✓</span>

                    <div>
                        <strong>Application Tracking</strong>

                        <small>
                            Check your application status anytime.
                        </small>
                    </div>

                </div>


                <div class="about-feature">

                    <span>✓</span>

                    <div>
                        <strong>Digital Certificates</strong>

                        <small>
                            Download approved certificates online.
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- ================= HOW IT WORKS ================= -->

<section class="how-section" id="how-it-works">

    <div class="section-container">

        <div class="section-heading">

            <span class="section-label">
                HOW IT WORKS
            </span>

            <h2>
                Get Your Service in 4 Simple Steps
            </h2>

            <p>
                Nagar Sewa simplifies the complete application process.
            </p>

        </div>


        <div class="steps">


            <div class="step">

                <div class="step-number">
                    01
                </div>

                <div class="step-icon">
                    👤
                </div>

                <h3>
                    Create Account
                </h3>

                <p>
                    Register on Nagar Sewa using your basic information.
                </p>

            </div>


            <div class="step-line"></div>


            <div class="step">

                <div class="step-number">
                    02
                </div>

                <div class="step-icon">
                    📋
                </div>

                <h3>
                    Select Service
                </h3>

                <p>
                    Choose the municipal service you want to apply for.
                </p>

            </div>


            <div class="step-line"></div>


            <div class="step">

                <div class="step-number">
                    03
                </div>

                <div class="step-icon">
                    📤
                </div>

                <h3>
                    Submit Documents
                </h3>

                <p>
                    Fill the application and upload required documents.
                </p>

            </div>


            <div class="step-line"></div>


            <div class="step">

                <div class="step-number">
                    04
                </div>

                <div class="step-icon">
                    📥
                </div>

                <h3>
                    Get Certificate
                </h3>

                <p>
                    Track approval and download your certificate online.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ================= CTA ================= -->

<section class="cta-section">

    <div class="cta-container">

        <div class="cta-content">

            <span class="cta-icon">
                🏛️
            </span>

            <div>

                <h2>
                    Ready to Access Municipal Services Online?
                </h2>

                <p>
                    Create your Nagar Sewa account and start applying
                    for services today.
                </p>

            </div>

        </div>


        <?php if ($is_logged_in): ?>

            <a
                href="citizen/services.php"
                class="cta-button"
            >
                Explore Services →
            </a>

        <?php else: ?>

            <a
                href="auth/register.php"
                class="cta-button"
            >
                Create Account →
            </a>

        <?php endif; ?>

    </div>

</section>


<!-- ================= CONTACT ================= -->

<section class="contact-section" id="contact">

    <div class="section-container">

        <div class="section-heading">

            <span class="section-label">
                CONTACT US
            </span>

            <h2>
                We're Here to Help
            </h2>

            <p>
                Have questions about municipal services?
                Get in touch with us.
            </p>

        </div>


        <div class="contact-grid">


            <div class="contact-card">

                <div class="contact-icon">
                    📍
                </div>

                <h3>
                    Municipal Office
                </h3>

                <p>
                    Nagar Sewa Municipal Office<br>
                    Kathmandu, Nepal
                </p>

            </div>


            <div class="contact-card">

                <div class="contact-icon">
                    📞
                </div>

                <h3>
                    Phone
                </h3>

                <p>
                    +977-1-XXXXXXX<br>
                    Sunday - Friday
                </p>

            </div>


            <div class="contact-card">

                <div class="contact-icon">
                    ✉️
                </div>

                <h3>
                    Email
                </h3>

                <p>
                    support@nagarsewa.com<br>
                    info@nagarsewa.com
                </p>

            </div>

        </div>

    </div>

</section>


<!-- ================= FOOTER ================= -->

<footer class="footer">

    <div class="footer-container">


        <div class="footer-brand">

            <div class="footer-logo">

                <div class="logo-icon">
                    🏛️
                </div>

                <div>

                    <h3>
                        Nagar Sewa
                    </h3>

                    <span>
                        Digital Municipal Services
                    </span>

                </div>

            </div>


            <p>
                Making municipal services simple, accessible
                and digital for every citizen.
            </p>

        </div>


        <div class="footer-links">

            <h4>
                Quick Links
            </h4>

            <a href="index.php">
                Home
            </a>

            <a href="#services">
                Services
            </a>

            <a href="#about">
                About Us
            </a>

            <a href="#how-it-works">
                How It Works
            </a>

        </div>


        <div class="footer-links">

            <h4>
                Services
            </h4>

            <a href="citizen/services.php">
                Birth Certificate
            </a>

            <a href="citizen/services.php">
                Death Certificate
            </a>

            <a href="citizen/services.php">
                Marriage Registration
            </a>

            <a href="citizen/services.php">
                Migration Certificate
            </a>

        </div>


        <div class="footer-links">

            <h4>
                Account
            </h4>

            <?php if ($is_logged_in): ?>

                <a href="citizen/dashboard.php">
                    Dashboard
                </a>

                <a href="citizen/profile.php">
                    My Profile
                </a>

                <a href="citizen/notices.php">
                    Notices
                </a>

                <a href="auth/logout.php">
                    Logout
                </a>

            <?php else: ?>

                <a href="auth/login.php">
                    Login
                </a>

                <a href="auth/register.php">
                    Register
                </a>

            <?php endif; ?>

        </div>

    </div>


    <div class="footer-bottom">

        <p>
            © <?php echo date("Y"); ?> Nagar Sewa.
            All Rights Reserved.
        </p>

        <div>
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
        </div>

    </div>

</footer>


<script>

function toggleMenu() {

    const menu = document.getElementById("mobileMenu");

    menu.classList.toggle("show");

}

document.querySelectorAll(".mobile-menu a").forEach(function(link) {

    link.addEventListener("click", function() {

        document
            .getElementById("mobileMenu")
            .classList.remove("show");

    });

});

</script>

</body>
</html>