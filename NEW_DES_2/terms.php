<?php
require_once 'includes/auth_helper.php';
$activePage = 'terms';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service & Reservation Guidelines | KLD Facility Reservation</title>
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
            <h1 class="legal-hero-title">TERMS OF SERVICE</h1>
            <p class="legal-hero-subtitle">
                Institutional rules, booking guidelines, user responsibilities, and venue care policies for the Kolehiyo ng Lungsod ng Dasmariñas Facility Reservation System.
            </p>
        </div>
    </section>

    <!-- Main Content Layout -->
    <main class="legal-main-container">
        <div class="container">
            <div class="legal-layout">
                
                <!-- Table of Contents Sidebar -->
                <aside class="legal-sidebar">
                    <h3 class="legal-sidebar-title">Terms Navigation</h3>
                    <ul class="legal-toc-list">
                        <li><a href="#acceptance" class="legal-toc-link active">1. Acceptance & Eligibility</a></li>
                        <li><a href="#account-security" class="legal-toc-link">2. Account Responsibility</a></li>
                        <li><a href="#booking-lead-time" class="legal-toc-link">3. Lead Time & Approvals</a></li>
                        <li><a href="#slips" class="legal-toc-link">4. Official Reservation Slips</a></li>
                        <li><a href="#cancellation" class="legal-toc-link">5. Cancellation & No-Show</a></li>
                        <li><a href="#venue-conduct" class="legal-toc-link">6. Venue Care & Conduct</a></li>
                        <li><a href="#equipment-damages" class="legal-toc-link">7. Equipment & Damage Liability</a></li>
                        <li><a href="#prohibited" class="legal-toc-link">8. Prohibited Items</a></li>
                        <li><a href="#sanctions" class="legal-toc-link">9. Violations & Sanctions</a></li>
                    </ul>

                    <div class="legal-actions-box">
                        <button type="button" class="btn-legal-action btn-legal-print" onclick="window.print()">
                            🖨️ Print Terms
                        </button>
                        <a href="index.php#facilities" class="btn-legal-action btn-legal-agree">
                            Browse Venues
                        </a>
                    </div>
                </aside>

                <!-- Legal Policy Content Card -->
                <article class="legal-content-card">
                    <div class="legal-doc-header">
                        <span class="legal-badge">University Regulations</span>
                        <h1>Terms of Facility Reservation & Use</h1>
                        <div class="legal-doc-meta">
                            <span><strong>Effective Date:</strong> September 15, 2026</span>
                            <span><strong>Applicability:</strong> All Campus Venues & Laboratories</span>
                        </div>
                    </div>

                    <!-- 1. Acceptance & Eligibility -->
                    <section class="legal-section" id="acceptance">
                        <h2>1. Acceptance of Terms & Eligibility</h2>
                        <p>
                            By creating an account, accessing, or submitting a reservation request through the <strong>Kolehiyo ng Lungsod ng Dasmariñas (KLD) Facility Reservation System</strong>, you agree to be bound by these Terms of Service, university student handbooks, faculty manuals, and all applicable Philippine laws.
                        </p>
                        <p>
                            Eligibility to book campus facilities is extended exclusively to:
                        </p>
                        <ul class="legal-list">
                            <li>Currently enrolled KLD undergraduate and diploma students.</li>
                            <li>Active full-time and part-time faculty members and academic departments.</li>
                            <li>Accredited Recognized Student Organizations (RSOs) with faculty adviser endorsement.</li>
                            <li>University administrative offices and authorized local government partners.</li>
                        </ul>
                    </section>

                    <!-- 2. Account Responsibility -->
                    <section class="legal-section" id="account-security">
                        <h2>2. User Account Security & Accountability</h2>
                        <p>
                            Users are responsible for maintaining the strict confidentiality of their portal credentials (ID Number, Institutional Email, and Password). Any reservation submitted through your authenticated account shall be deemed authorized and endorsed by you.
                        </p>
                        <div class="legal-callout-box">
                            <strong>Security Advisory:</strong> Sharing account passwords or using credentials of another student or faculty member is considered an offense under the KLD Student Code of Conduct.
                        </div>
                    </section>

                    <!-- 3. Lead Time & Approvals -->
                    <section class="legal-section" id="booking-lead-time">
                        <h2>3. Booking Procedure & Minimum Lead Times</h2>
                        <p>
                            To allow adequate preparation, equipment staging, and administrative review, all reservation requests must adhere to the following mandatory lead times:
                        </p>
                        <ul class="legal-list">
                            <li><strong>Standard Lecture Halls, Classrooms & Study Commons:</strong> Minimum of three (3) working days prior to the event.</li>
                            <li><strong>Computer, Science & Clinical Simulation Laboratories:</strong> Minimum of five (5) working days prior to the event with lab custodian concurrence.</li>
                            <li><strong>KLD Gymnasium, Main Auditorium & Audio-Visual Rooms:</strong> Minimum of seven (7) to ten (10) working days prior to the event.</li>
                        </ul>
                        <p>
                            Submitting a request does not guarantee automatic venue reservation until official administrative approval is reflected on your User Dashboard.
                        </p>
                    </section>

                    <!-- 4. Official Reservation Slips -->
                    <section class="legal-section" id="slips">
                        <h2>4. Official Printable Reservation Slips</h2>
                        <p>
                            Upon approval, an <strong>Official Reservation Slip</strong> with a unique tracking code is generated on the applicant's User Dashboard. The applicant must present a printed or digital copy of this slip to the Campus Safety & Security Office and the facility custodian upon entering the venue.
                        </p>
                    </section>

                    <!-- 5. Cancellation & No-Show -->
                    <section class="legal-section" id="cancellation">
                        <h2>5. Cancellation, Rescheduling & No-Show Policies</h2>
                        <p>
                            If a scheduled activity cannot proceed, the applicant must cancel the booking via the User Dashboard at least <strong>twenty-four (24) hours</strong> before the reserved time slot to open the space for other university groups.
                        </p>
                        <p>
                            Failing to cancel and not occupying the reserved venue (No-Show) more than twice in a single academic semester may result in a thirty (30) day suspension of facility reservation privileges.
                        </p>
                    </section>

                    <!-- 6. Venue Care & Conduct -->
                    <section class="legal-section" id="venue-conduct">
                        <h2>6. Venue Care, Cleanliness & Code of Conduct</h2>
                        <p>
                            Users are expected to uphold the university standard of <strong>Clean As You Go (CLAYGO)</strong>. At the conclusion of any reservation:
                        </p>
                        <ul class="legal-list">
                            <li>All chairs, tables, and movable equipment must be returned to their default arrangement.</li>
                            <li>Trash, event props, decorations, and food containers must be segregated and disposed of in designated campus waste bins.</li>
                            <li>Air-conditioning units, lights, sound systems, and projectors must be properly shut down in coordination with facility custodians.</li>
                        </ul>
                    </section>

                    <!-- 7. Equipment & Damage Liability -->
                    <section class="legal-section" id="equipment-damages">
                        <h2>7. Equipment Handling & Liability for Damages</h2>
                        <p>
                            The applicant and organizing department assume full responsibility for any loss, breakage, or physical damage to university property, audio-visual instruments, sports fixtures, or laboratory apparatus caused by negligence or misuse during the reservation window.
                        </p>
                        <div class="legal-callout-box">
                            <strong>Damage Policy:</strong> Repair or replacement costs will be assessed by the KLD General Services Office and billed directly to the organizing student organization or responsible party.
                        </div>
                    </section>

                    <!-- 8. Prohibited Items -->
                    <section class="legal-section" id="prohibited">
                        <h2>8. Prohibited Items & Activities</h2>
                        <p>
                            The following items and actions are strictly prohibited within all KLD campus facilities:
                        </p>
                        <ul class="legal-list">
                            <li>Open flames, pyrotechnics, fireworks, or hazardous chemical substances without written approval from the Fire Safety Officer.</li>
                            <li>Alcoholic beverages, vape devices, e-cigarettes, and illegal substances.</li>
                            <li>Attaching adhesives, nails, or paint to walls, projector screens, or acoustic panels that cause surface defacement.</li>
                            <li>Unauthorized commercial merchandising, non-academic soliciting, or gambling.</li>
                        </ul>
                    </section>

                    <!-- 9. Violations & Sanctions -->
                    <section class="legal-section" id="sanctions">
                        <h2>9. Violations, Penalties & System Disclaimers</h2>
                        <p>
                            KLD reserves the right to immediately cancel or revoke any approved reservation in the event of unforeseen university emergencies, institutional calendar priority events, or flagrant violations of these terms. Disciplinary action will be handled in accordance with the Student Discipline Board.
                        </p>
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
