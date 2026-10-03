<?php
require_once 'includes/auth_helper.php';
$activePage = 'privacy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Privacy Policy & User Account Privacy | KLD Facility Reservation</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/legal-faq.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body data-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">

    <!-- Shared Header Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Legal Hero Section -->
    <section class="legal-hero-section" style="background-image: url('assets/images/campus_bg.png');">
        <div class="legal-hero-overlay">
            <h1 class="legal-hero-title">DATA PRIVACY POLICY</h1>
            <p class="legal-hero-subtitle">
                How Kolehiyo ng Lungsod ng Dasmariñas collects, safeguards, and processes user account information in compliance with Republic Act No. 10173 (Data Privacy Act of 2012).
            </p>
        </div>
    </section>

    <!-- Main Content Layout -->
    <main class="legal-main-container">
        <div class="container">
            <div class="legal-layout">
                
                <!-- Table of Contents Sidebar -->
                <aside class="legal-sidebar">
                    <h3 class="legal-sidebar-title">Privacy Sections</h3>
                    <ul class="legal-toc-list">
                        <li><a href="#introduction" class="legal-toc-link active">1. Overview & Scope</a></li>
                        <li><a href="#user-account" class="legal-toc-link">2. User Account Data Collected</a></li>
                        <li><a href="#purpose" class="legal-toc-link">3. Purpose of Processing</a></li>
                        <li><a href="#security" class="legal-toc-link">4. Security & Encryption</a></li>
                        <li><a href="#sharing" class="legal-toc-link">5. Data Sharing & Disclosure</a></li>
                        <li><a href="#retention" class="legal-toc-link">6. Data Retention Policy</a></li>
                        <li><a href="#user-rights" class="legal-toc-link">7. Rights of Data Subjects</a></li>
                        <li><a href="#cookies" class="legal-toc-link">8. Cookies & Session Storage</a></li>
                        <li><a href="#dpo-contact" class="legal-toc-link">9. Data Protection Officer (DPO)</a></li>
                    </ul>

                    <div class="legal-actions-box">
                        <button type="button" class="btn-legal-action btn-legal-print" onclick="window.print()">
                            🖨️ Print Policy
                        </button>
                        <a href="register.php" class="btn-legal-action btn-legal-agree">
                            Create Protected Account
                        </a>
                    </div>
                </aside>

                <!-- Legal Policy Content Card -->
                <article class="legal-content-card">
                    <div class="legal-doc-header">
                        <span class="legal-badge">Official Policy Document</span>
                        <h1>User Account Privacy & Data Protection</h1>
                        <div class="legal-doc-meta">
                            <span><strong>Effective Date:</strong> September 15, 2026</span>
                            <span><strong>Version:</strong> 2.4</span>
                            <span><strong>Jurisdiction:</strong> Republic of the Philippines</span>
                        </div>
                    </div>

                    <!-- 1. Introduction -->
                    <section class="legal-section" id="introduction">
                        <h2>1. Overview & Institutional Commitment</h2>
                        <p>
                            The <strong>Kolehiyo ng Lungsod ng Dasmariñas (KLD)</strong> is dedicated to safeguarding the personal data privacy and security of all enrolled students, faculty members, academic administrators, recognized student organizations (RSOs), and authorized community stakeholders.
                        </p>
                        <p>
                            This Privacy Policy sets forth our institutional protocols for gathering, storing, verifying, and managing personal and sensitive information processed through the <strong>KLD Facility Reservation Portal</strong> in strict adherence to the <em>Data Privacy Act of 2012 (Republic Act No. 10173)</em> and its Implementing Rules and Regulations (IRR).
                        </p>
                        <div class="legal-callout-box">
                            <strong>Key Principle:</strong> KLD will never sell, lease, or monetize user account information or facility reservation history to external advertisers or commercial third parties.
                        </div>
                    </section>

                    <!-- 2. User Account Data Collected -->
                    <section class="legal-section" id="user-account">
                        <h2>2. User Account Information We Collect</h2>
                        <p>
                            When registering and utilizing an account within the Facility Reservation System, the platform collects the following categories of data:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Identity Information:</strong> Full legal name, KLD Student Identification Number or Faculty Employee Number.</li>
                            <li><strong>Contact Details:</strong> Official institutional email address (e.g., <code>@kld.edu.ph</code>) and primary mobile contact number.</li>
                            <li><strong>Academic & Organizational Affiliation:</strong> Institute/Department (e.g., Institute of Computing Studies, Institute of Business, Institute of Education, Institute of Health Sciences) and Recognized Student Organization designation.</li>
                            <li><strong>Account Credentials:</strong> Securely hashed passwords (cryptographic bcrypt/argon2 algorithms; plaintext passwords are never stored or visible).</li>
                            <li><strong>Reservation History:</strong> Facility requested, proposed dates/times, event description, expected number of attendees, required audio-visual/laboratory equipment, and administrative endorsement signatures.</li>
                            <li><strong>System Audit Logs:</strong> IP address, browser type, timestamp of logins, and reservation modification timestamps for security and accountability.</li>
                        </ul>
                    </section>

                    <!-- 3. Purpose of Processing -->
                    <section class="legal-section" id="purpose">
                        <h2>3. Purpose of Data Processing</h2>
                        <p>
                            All personal data collected through user accounts is processed strictly for legitimate academic, operational, and institutional purposes:
                        </p>
                        <ul class="legal-list">
                            <li>To verify identity and authenticate student and faculty eligibility to reserve university amenities.</li>
                            <li>To schedule, coordinate, approve, or decline campus facility reservations without time collisions.</li>
                            <li>To transmit automated electronic notifications, confirmation slips, and cancellation advisories to applicants.</li>
                            <li>To facilitate campus security clearance, equipment accountability, and post-event safety verification.</li>
                            <li>To generate anonymous statistical usage reports for campus facility capacity planning.</li>
                        </ul>
                    </section>

                    <!-- 4. Security & Encryption -->
                    <section class="legal-section" id="security">
                        <h2>4. Data Security & Storage Architecture</h2>
                        <p>
                            We employ comprehensive physical, organizational, and technological safeguards to prevent unauthorized access, accidental alteration, disclosure, or destruction of user data:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Data Encryption in Transit:</strong> All communication between user web browsers and the portal is encrypted using Transport Layer Security (TLS 1.3 / HTTPS).</li>
                            <li><strong>Password Hashing:</strong> Passwords are fortified with salted cryptographic hashing before being stored in the database.</li>
                            <li><strong>Role-Based Access Control (RBAC):</strong> Access to sensitive reservation applicant records is strictly restricted to designated KLD Facilities Officers and Department Administrators.</li>
                            <li><strong>Session Protection:</strong> Automatic session timeout after periods of inactivity to protect shared laboratory terminals.</li>
                        </ul>
                    </section>

                    <!-- 5. Data Sharing & Disclosure -->
                    <section class="legal-section" id="sharing">
                        <h2>5. Data Sharing & Third-Party Disclosure</h2>
                        <p>
                            User account information is shared solely within authorized internal university units directly involved in event coordination:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Office of Student Affairs & Services (OSAS):</strong> For endorsing student organization activities.</li>
                            <li><strong>Campus Safety & Security Office:</strong> For verifying authorized entry and venue occupancy limits.</li>
                            <li><strong>Management Information Systems (MIS):</strong> For technical support and system infrastructure maintenance.</li>
                        </ul>
                        <p>
                            External disclosure will only take place if required by Philippine judicial subpoenas, emergency public health mandates, or lawful government directives.
                        </p>
                    </section>

                    <!-- 6. Retention Policy -->
                    <section class="legal-section" id="retention">
                        <h2>6. Data Retention & Archival Policy</h2>
                        <p>
                            User accounts remain active throughout a student's enrollment or a faculty member's tenure at KLD. Reservation records and transaction slips are maintained for three (3) academic years in accordance with government audit compliance, after which they are securely archived or anonymized.
                        </p>
                    </section>

                    <!-- 7. Rights of Data Subjects -->
                    <section class="legal-section" id="user-rights">
                        <h2>7. Your Rights as a Data Subject</h2>
                        <p>
                            Under Republic Act No. 10173, every registered user is entitled to exercise the following privacy rights:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Right to be Informed:</strong> To understand how and why personal data is being collected and processed.</li>
                            <li><strong>Right of Access:</strong> To view and download your active profile data and reservation history via the User Dashboard.</li>
                            <li><strong>Right to Rectification:</strong> To request immediate correction of inaccurate or outdated contact information.</li>
                            <li><strong>Right to Erasure / Blocking:</strong> To request account deactivation upon university graduation or separation, subject to outstanding reservation clearances.</li>
                            <li><strong>Right to Damages:</strong> To be indemnified for any proven damages sustained due to unlawful data breach or processing.</li>
                        </ul>
                    </section>

                    <!-- 8. Cookies & Session Storage -->
                    <section class="legal-section" id="cookies">
                        <h2>8. Cookies & Session Storage</h2>
                        <p>
                            The portal utilizes essential session cookies solely to maintain user authentication, remember login preferences, and protect form submissions against Cross-Site Request Forgery (CSRF). We do not employ third-party behavioral tracking cookies.
                        </p>
                    </section>

                    <!-- 9. DPO Contact -->
                    <section class="legal-section" id="dpo-contact">
                        <h2>9. Contacting the KLD Data Protection Officer</h2>
                        <p>
                            If you have inquiries, concerns, or wish to exercise any of your data privacy rights regarding your user account, please contact our Data Protection Office:
                        </p>
                        <div class="legal-callout-box" style="background: #f8fafc; border-left-color: var(--kld-green-primary);">
                            <strong>KLD Data Protection Office (DPO)</strong><br>
                            Administrative Building, Kolehiyo ng Lungsod ng Dasmariñas<br>
                            Brgy. Burol Main, Dasmariñas City, Cavite 4114<br>
                            Email: <a href="mailto:dpo@kld.edu.ph" style="color: var(--kld-green-primary); font-weight: 600;">dpo@kld.edu.ph</a> | Phone: (046) 481-8000 Loc. 112
                        </div>
                    </section>

                </article>
            </div>
        </div>
    </main>

    <!-- Shared Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="js/main.js"></script>
</body>
</html>
