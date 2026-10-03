<?php
/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Page: Public Landing Page & Facility Preview (index.php)
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. Session Access Control (CRITICAL): The very first line of logic checks
 *    whether an active user session already exists. If $authenticated is true,
 *    the browser is immediately redirected to main.php with header() + exit.
 *    This prevents any logged-in user from ever viewing or navigating back
 *    to the public landing page — enforcing strict one-way access flow.
 * 2. Unauthenticated Guest Access: Guests can freely browse facility specs.
 *    Any attempt to initiate a reservation strictly launches #authRequiredModal.
 * 3. Strict Code Decoupling: Zero inline styles. Powered by css/home.css.
 */
require_once 'includes/auth_helper.php';
require_once 'includes/icons.php';

// SESSION ACCESS CONTROL: If the user is already logged in, bypass the public
// landing page entirely and redirect straight to the main reservation portal.
// This prevents authenticated users from navigating backward to index.php.
if (isLoggedIn()) {
    header('Location: main.php');
    exit;
}

$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Reservation | Kolehiyo ng Lungsod ng Dasmariñas</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body data-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">

    <!-- Shared Header Navbar (Public Navigation) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- ==========================================
         1. HERO SECTION (Spacious Visual Hierarchy)
         ========================================== -->
    <section class="hero-section" id="home">
        <div class="hero-overlay">
            <div class="hero-content-container">
                <h1 class="hero-title">FACILITY RESERVATION</h1>
                <p class="hero-subtitle">Seamless venue bookings for the KLD Community.</p>
                <div class="hero-cta-group">
                    <button type="button" class="pill-btn btn-hero-primary btn-make-reservation" id="heroMakeReservationBtn">
                        MAKE RESERVATIONS
                    </button>
                    <a href="#facilities" class="pill-btn btn-hero-secondary">
                        BROWSE FACILITIES
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================
         2. FACILITY RESERVATIONS INTRO SECTION
         ========================================== -->
    <section class="school-intro-section">
        <div class="container">
            <div class="intro-content-wrapper">
                <h2 class="intro-title">Facility Reservations</h2>
                <p class="intro-text">
                    The Kolehiyo ng Lungsod ng Dasmariñas (KLD) Facility Reservation System provides a streamlined, centralized platform for students, faculty, student organizations, and authorized academic partners to reserve campus venues. Whether hosting academic symposiums, athletic matches, cultural productions, or specialized IT lab sessions, explore our state-of-the-art facilities and submit your requests with ease.
                </p>
            </div>
        </div>
    </section>

    <!-- ==========================================
         3. AVAILABLE FACILITIES — ANIMATED IMAGE SLIDER
         ========================================== -->
    <section class="facilities-carousel-section" id="facilities">
        <div class="carousel">
            <!-- Main Carousel Slide List -->
            <div class="list">
                <!-- Slide 1: KLD Gymnasium -->
                <div class="item">
                    <img src="assets/images/gym.png" alt="KLD Gymnasium">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">KLD GYMNASIUM</div>
                        <div class="topic">SPORTS & EVENTS</div>
                        <div class="des">
                            Multi-purpose indoor sports arena for basketball, volleyball, championship tournaments, campus-wide assemblies, and major institutional gatherings. Capacity: 1,200 persons.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="gymnasium">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="gymnasium">SEE DETAILS</button>
                        </div>
                    </div>
                </div>

                <!-- Slide 2: Audio Visual Room -->
                <div class="item">
                    <img src="assets/images/avr.png" alt="Audio Visual Room">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">AUDIO VISUAL ROOM</div>
                        <div class="topic">AVR & SEMINAR</div>
                        <div class="des">
                            High-tier presentation amphitheater equipped with 4K laser projection, studio acoustics, wireless microphones, and tiered plush theater seating. Capacity: 180 persons.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="avr">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="avr">SEE DETAILS</button>
                        </div>
                    </div>
                </div>

                <!-- Slide 3: Nursing Lecture Hall -->
                <div class="item">
                    <img src="assets/images/CB2_AVR_2.png" alt="Nursing Lecture Hall">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">NURSING LECTURE HALL</div>
                        <div class="topic">LECTURE AMPHITHEATER</div>
                        <div class="des">
                            Modern lecture theater configured for clinical demonstrations, medical case presentations, and multidisciplinary symposiums. Capacity: 140 persons.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="nursing-lecture">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="nursing-lecture">SEE DETAILS</button>
                        </div>
                    </div>
                </div>

                <!-- Slide 4: College Learning Commons -->
                <div class="item">
                    <img src="assets/images/dwd_hall.jpg" alt="College Learning Commons">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">LEARNING COMMONS</div>
                        <div class="topic">STUDY & COLLABORATION</div>
                        <div class="des">
                            Open collaborative study space with group discussion areas, individual study stations, digital learning resources, and high-speed Wi-Fi. Capacity: 200 persons.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="learning-commons">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="learning-commons">SEE DETAILS</button>
                        </div>
                    </div>
                </div>

                <!-- Slide 5: Computer Laboratory -->
                <div class="item">
                    <img src="assets/images/comlab.png" alt="Computer Laboratory">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">COMPUTER LABORATORY</div>
                        <div class="topic">COMPUTING FACILITY</div>
                        <div class="des">
                            High-spec computer laboratory with 50 Core-i7 dual-monitor workstations, gigabit fiber LAN, and dedicated programming environments for IT classes and practical exams.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="computer-lab">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="computer-lab">SEE DETAILS</button>
                        </div>
                    </div>
                </div>

                <!-- Slide 6: Anatomy & Physiology Lab -->
                <div class="item">
                    <img src="assets/images/psychology_lab.jpg" alt="Anatomy and Physiology Laboratory">
                    <div class="content">
                        <div class="author">KLD FACILITY</div>
                        <div class="title">ANATOMY & PHYSIOLOGY LAB</div>
                        <div class="topic">HEALTH SCIENCES</div>
                        <div class="des">
                            Equipped with full anatomical skeletal models, organ specimens, stainless steel dissection stations, and high-magnification microscopes. Capacity: 45 students.
                        </div>
                        <div class="buttons">
                            <button type="button" class="reserve-btn btn-make-reservation" data-facility="anatomy-lab">RESERVE NOW</button>
                            <button type="button" class="details-btn btn-view-details" data-facility="anatomy-lab">SEE DETAILS</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thumbnail Navigation Bar -->
            <div class="thumbnail">
                <div class="item">
                    <img src="assets/images/gym.png" alt="KLD Gymnasium">
                    <div class="content">
                        <div class="title">KLD Gymnasium</div>
                        <div class="description">Sports Arena</div>
                    </div>
                </div>
                <div class="item">
                    <img src="assets/images/avr.png" alt="Audio Visual Room">
                    <div class="content">
                        <div class="title">Audio Visual Room</div>
                        <div class="description">AVR & Seminar</div>
                    </div>
                </div>
                <div class="item">
                    <img src="assets/images/CB2_AVR_2.png" alt="Nursing Lecture Hall">
                    <div class="content">
                        <div class="title">Nursing Lecture Hall</div>
                        <div class="description">Lecture Amphitheater</div>
                    </div>
                </div>
                <div class="item">
                    <img src="assets/images/dwd_hall.jpg" alt="College Learning Commons">
                    <div class="content">
                        <div class="title">Learning Commons</div>
                        <div class="description">Study & Collaboration</div>
                    </div>
                </div>
                <div class="item">
                    <img src="assets/images/comlab.png" alt="Computer Laboratory">
                    <div class="content">
                        <div class="title">Computer Lab</div>
                        <div class="description">Computing Facility</div>
                    </div>
                </div>
                <div class="item">
                    <img src="assets/images/psychology_lab.jpg" alt="Anatomy & Physiology Lab">
                    <div class="content">
                        <div class="title">Anatomy Lab</div>
                        <div class="description">Health Sciences</div>
                    </div>
                </div>
            </div>

            <!-- Carousel Navigation Controls -->
            <div class="arrows">
                <button type="button" id="prev">&lt;</button>
                <button type="button" id="next">&gt;</button>
            </div>

            <!-- Time Progress Indicator -->
            <div class="time"></div>
        </div>
    </section>

    <!-- ==========================================
         4. HOW TO RESERVE SECTION (SERVICES)
         ========================================== -->
    <section class="steps-section" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">Step-by-Step Guide</span>
                <h2 class="section-title">How Reservation Works</h2>
                <p class="section-subtitle">
                    Booking campus venues is simple, fast, and transparent through our digital portal.
                </p>
            </div>

            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h4>Browse Facilities</h4>
                    <p>Select your required venue, check specifications, seating capacity, and equipment availability.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">2</div>
                    <h4>Log In or Register</h4>
                    <p>Sign in using your institutional KLD ID account or register if you are a first-time applicant.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">3</div>
                    <h4>Submit Reservation</h4>
                    <p>Select date, preferred time slot, event objectives, and any required administrative clearance.</p>
                </div>

                <div class="step-card">
                    <div class="step-number">4</div>
                    <h4>Receive Approval</h4>
                    <p>Track request status in real-time and download your verified reservation pass upon approval.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Shared Minimalist Modern App Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- ==========================================
         5. AUTH REQUIRED POPUP MODAL (Strict Guest Interceptor)
         ========================================== -->
    <div class="modal-backdrop" id="authRequiredModal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
        <div class="modal-window">
            <div class="modal-header-accent">
                <div class="modal-icon-badge"><?php echo icon('lock', 22); ?></div>
                <h3 class="modal-header-title" id="authModalTitle">Reservation Access</h3>
                <button type="button" class="modal-close-btn" data-close-modal aria-label="Close modal"><?php echo icon('x', 18); ?></button>
            </div>
            <div class="modal-body">
                <h4 class="modal-title">Please Log In or Register</h4>
                <p class="modal-text">
                    You need to log in or register an account with your institutional KLD ID before you can make a facility reservation.
                </p>
                <div class="modal-action-btns">
                    <a href="register.php" class="btn-modal-signup" id="modalSignUpBtn">
                        Sign Up for an Account
                    </a>
                    <a href="login.php" class="btn-modal-login">
                        Already have an account? Log In
                    </a>
                    <button type="button" class="btn-modal-dismiss" data-close-modal>
                        Cancel & Return
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         6. FACILITY QUICK VIEW DETAIL MODAL
         ========================================== -->
    <div class="modal-backdrop" id="facilityDetailModal" role="dialog" aria-modal="true" aria-labelledby="modalFacTitle">
        <div class="modal-window modal-window-lg">
            <div class="modal-details-banner">
                <img id="modalFacImg" src="assets/images/campus_bg.png" alt="Facility Image">
                <button type="button" class="modal-close-btn" data-close-modal aria-label="Close modal"><?php echo icon('x', 18); ?></button>
                <div class="modal-details-overlay">
                    <span id="modalFacCategory" class="category-tag">Category</span>
                    <h3 id="modalFacTitle" class="modal-header-title">Facility Title</h3>
                </div>
            </div>
            <div class="modal-details-content">
                <div class="modal-meta-row">
                    <span id="modalFacStatus" class="status-badge">Available</span>
                    <span class="modal-hours-text" id="modalFacHours">Mon-Sat: 8AM-5PM</span>
                </div>

                <p id="modalFacDesc" class="modal-desc-text">
                    Facility description goes here.
                </p>

                <div class="facility-specs">
                    <div class="spec-item">
                        <?php echo icon('map-pin', 16); ?> <span id="modalFacLocation">Location</span>
                    </div>
                    <div class="spec-item">
                        <?php echo icon('users', 16); ?> <span id="modalFacCapacity">Capacity</span>
                    </div>
                    <div class="spec-item">
                        <?php echo icon('wind', 16); ?> <span>Air-Conditioned</span>
                    </div>
                </div>

                <h5 class="modal-section-heading">
                    Included Equipment & Amenities
                </h5>
                <ul id="modalFacAmenities" class="modal-amenities-list">
                    <!-- Populated dynamically via JS -->
                </ul>

                <h5 class="modal-section-heading">
                    Reservation Policy & Guidelines
                </h5>
                <p id="modalFacRules" class="modal-policy-box">
                    Prior department endorsement required. Ensure equipment is properly handled during use.
                </p>

                <div class="modal-btn-row">
                    <button type="button" class="btn-modal-close-outline" data-close-modal>
                        Close
                    </button>
                    <button type="button" class="pill-btn btn-make-reservation btn-modal-reserve-primary" id="modalFacBookBtn">
                        Make Reservation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Script -->
    <script src="js/main.js"></script>
    <script src="js/script.js"></script>
    <script src="js/carousel.js"></script>
</body>
</html>
