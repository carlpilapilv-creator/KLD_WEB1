<?php
require_once 'includes/auth_helper.php';
$activePage = 'faq';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequently Asked Questions (FAQs) | KLD Facility Reservation</title>
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

    <!-- FAQ Hero Section -->
    <section class="faq-hero-section" style="background-image: url('assets/images/campus_bg.png');">
        <div class="faq-hero-overlay">
            <h1 class="faq-hero-title">FREQUENTLY ASKED QUESTIONS</h1>
            <p class="faq-hero-subtitle">
                Find clear answers to the most common questions regarding booking procedures, venue rules, equipment requests, and account management at KLD.
            </p>
        </div>
    </section>

    <!-- Main FAQ Content -->
    <main class="faq-main-container">
        <div class="container">

            <!-- Search Box -->
            <div class="faq-search-wrapper">
                <div class="faq-search-box">
                    <span class="faq-search-icon">🔍</span>
                    <input type="text" id="faqSearchInput" class="faq-search-input" placeholder="Type keywords: e.g. lead time, cancel, equipment, gymnasium, approval...">
                </div>
            </div>

            <!-- Filter Category Chips -->
            <div class="faq-filter-chips">
                <button class="faq-chip active" data-faq-filter="all">All 10 Questions</button>
                <button class="faq-chip" data-faq-filter="process">Booking Process</button>
                <button class="faq-chip" data-faq-filter="rules">Rules & Policies</button>
                <button class="faq-chip" data-faq-filter="equipment">Equipment & Venues</button>
                <button class="faq-chip" data-faq-filter="account">Account & Support</button>
            </div>

            <!-- 10 Comprehensive FAQ Accordion Items -->
            <div class="faq-accordion-container">
                
                <!-- Q1 -->
                <div class="faq-accordion-item active" data-faq-category="process">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">01</span>
                            <h3 class="faq-item-question">How do I make a facility reservation on the KLD Portal?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Making a reservation is quick and paperless. First, sign in to your KLD student or faculty account. Next, click <strong>"Make Reservations"</strong> or navigate to <strong>Campus Facilities</strong>. Select your desired venue (e.g., KLD Gymnasium, AVR, or Computer Lab), specify the date, time slot, event purpose, expected attendees, and required equipment. Once submitted, your reservation enters <strong>"Pending Review"</strong> status until approved by the Facilities Office.
                        </p>
                    </div>
                </div>

                <!-- Q2 -->
                <div class="faq-accordion-item" data-faq-category="process rules">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">02</span>
                            <h3 class="faq-item-question">What is the mandatory lead time for submitting booking requests?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            To ensure proper venue staging, sanitation, and custodian scheduling, mandatory lead times apply:<br>
                            • <strong>Standard Lecture Halls & Study Commons:</strong> Minimum <strong>3 working days</strong> in advance.<br>
                            • <strong>Computer, Science & Clinical Laboratories:</strong> Minimum <strong>5 working days</strong> in advance.<br>
                            • <strong>KLD Gymnasium, Main Auditorium & AVR:</strong> Minimum <strong>7 to 10 working days</strong> in advance.
                        </p>
                    </div>
                </div>

                <!-- Q3 -->
                <div class="faq-accordion-item" data-faq-category="rules process">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">03</span>
                            <h3 class="faq-item-question">Who is eligible to reserve university venues and facilities?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Reservation privileges are granted exclusively to currently enrolled KLD students, active faculty members, accredited Recognized Student Organizations (RSOs) with faculty adviser endorsement, and administrative department heads. External community groups must secure written clearance from the KLD President's Office.
                        </p>
                    </div>
                </div>

                <!-- Q4 -->
                <div class="faq-accordion-item" data-faq-category="process account">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">04</span>
                            <h3 class="faq-item-question">How do I verify if my reservation has been officially approved?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Log in to your account and visit your <strong>User Dashboard</strong>. The status badge will reflect <strong>"Approved"</strong> (green) or <strong>"Pending"</strong> (orange). For approved bookings, click <strong>"View Slip"</strong> to review or print your official stamped Reservation Pass, which must be presented to campus security on the day of your event.
                        </p>
                    </div>
                </div>

                <!-- Q5 -->
                <div class="faq-accordion-item" data-faq-category="process rules">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">05</span>
                            <h3 class="faq-item-question">Can I cancel or reschedule an existing reservation?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Yes. You can cancel any active booking from your User Dashboard by clicking the <strong>"Cancel"</strong> button in the reservation table. Cancellations must be performed at least <strong>24 hours</strong> prior to the reserved slot to free up the venue for other applicants. To reschedule, simply cancel the existing slot and submit a new request.
                        </p>
                    </div>
                </div>

                <!-- Q6 -->
                <div class="faq-accordion-item" data-faq-category="equipment rules">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">06</span>
                            <h3 class="faq-item-question">What are the guidelines for using Audio-Visual (AV) equipment and computer labs?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Audio-visual equipment (microphones, laser projectors, amplifiers) and computer workstations must be explicitly checked during form submission. Only authorized technical staff or designated faculty advisers may adjust master sound panels. Food, open beverages, and unauthorized software installations are strictly prohibited inside all AVRs and computer labs.
                        </p>
                    </div>
                </div>

                <!-- Q7 -->
                <div class="faq-accordion-item" data-faq-category="rules process">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">07</span>
                            <h3 class="faq-item-question">Are university facilities available for booking on weekends or holidays?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Yes. Saturday reservations (8:00 AM – 5:00 PM) are permitted for official student organization activities, sports practices, and departmental conferences. Weekend bookings require additional security clearance and custodian coordination at least 7 working days in advance.
                        </p>
                    </div>
                </div>

                <!-- Q8 -->
                <div class="faq-accordion-item" data-faq-category="equipment rules">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">08</span>
                            <h3 class="faq-item-question">What happens if a facility or equipment is damaged during our event?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Facility custodians inspect venues before and after every reservation. The registered applicant and sponsoring student organization or department assume financial and disciplinary liability for any damage or missing equipment resulting from negligence or misconduct.
                        </p>
                    </div>
                </div>

                <!-- Q9 -->
                <div class="faq-accordion-item" data-faq-category="rules process">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">09</span>
                            <h3 class="faq-item-question">How does the system resolve schedule conflicts for the same facility?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            The system processes requests on a <strong>first-submitted, first-reviewed</strong> basis. If two groups request the same venue and time slot, priority is automatically accorded to official university-wide institutional events and accredited academic classes.
                        </p>
                    </div>
                </div>

                <!-- Q10 -->
                <div class="faq-accordion-item" data-faq-category="account">
                    <button class="faq-item-header" type="button" onclick="toggleFaqAccordion(this)">
                        <div class="faq-item-question-wrap">
                            <span class="faq-number-badge">10</span>
                            <h3 class="faq-item-question">What should I do if I forgot my password or cannot log in?</h3>
                        </div>
                        <span class="faq-item-icon">▼</span>
                    </button>
                    <div class="faq-item-body">
                        <p class="faq-item-answer">
                            Click on the <strong>"Forgot password?"</strong> link on the login page or navigate to <a href="forgot-password.php" style="color: var(--kld-green-primary); font-weight: 600; text-decoration: underline;">forgot-password.php</a>. Enter your registered KLD email or ID to receive a verification OTP code and securely set a new password with real-time password strength validation.
                        </p>
                    </div>
                </div>

            </div>

            <!-- FAQ Support Contact Banner -->
            <div class="faq-support-banner">
                <div class="faq-support-text">
                    <h3>Have more questions about facility bookings?</h3>
                    <p>Our Facilities Management & Student Affairs team is available to assist you with specialized venue requirements.</p>
                </div>
                <a href="mailto:facilities@kld.edu.ph" class="btn-contact-support">
                    ✉️ Contact Support Desk
                </a>
            </div>

        </div>
    </main>

    <!-- Shared Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="js/main.js"></script>
</body>
</html>
