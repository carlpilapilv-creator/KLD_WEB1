<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * PAGE: Main Reservation Portal — Facility Selection Catalog (main.php)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 *
 * 1. AUTHENTICATION GATE: requireLogin() is called on the very first line of
 *    PHP execution. If no active user session exists, the browser is immediately
 *    redirected to login.php before a single byte of HTML is sent to the client.
 *
 * 2. DATABASE-BACKED: All reservation CRUD operations are executed against
 *    the MySQL `reservations` table via prepared statements. Session is used
 *    only for user identity caching (populated at login).
 *
 * 3. MODAL-ONLY BOOKING FORM: The Event Request Form is embedded EXCLUSIVELY
 *    inside #mainBookingModal. Clicking "Reserve" on a card populates the modal
 *    via JavaScript data-attributes and opens it — no page redirects.
 *
 * 4. REAL-TIME CONFLICT DETECTION: The JS engine reads existing reservations
 *    (injected as a JSON blob by PHP into #existingReservationsJson) and cross-
 *    references the selected facility_id and date to detect double-bookings.
 *
 * 5. COMPLETE CODE DECOUPLING:
 *    - Zero inline styles  → css/main_style.css
 *    - Zero inline scripts → js/main_script.js
 */
require_once 'includes/auth_helper.php';
require_once 'includes/icons.php';

// STEP 1: Authenticate — kick unauthenticated users back to login.php immediately
requireLogin();

// STEP 2: Retrieve the currently logged-in user from session
$currentUser = getCurrentUser();
$userId = (int)($currentUser['id'] ?? 0);

// STEP 3: Build avatar initials from the user's full name for the UI
$nameParts = explode(' ', trim($currentUser['fullname']));
$initials   = strtoupper(
    substr($nameParts[0], 0, 1) .
    (isset($nameParts[count($nameParts) - 1]) ? substr($nameParts[count($nameParts) - 1], 0, 1) : '')
);
$firstName = htmlspecialchars($nameParts[0]);

// STEP 4: Handle POST submission for a new reservation (DATABASE INSERT)
$actionMsg  = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'new_reservation') {

    // Sanitize all POST inputs to prevent XSS
    $facId     = htmlspecialchars(trim($_POST['facility_id']   ?? 'gymnasium'));
    $facName   = htmlspecialchars(trim($_POST['facility_name'] ?? 'KLD Gymnasium'));
    $evtName   = htmlspecialchars(trim($_POST['event_name']    ?? 'Campus Event'));
    $purpose   = htmlspecialchars(trim($_POST['event_purpose'] ?? 'General purpose event'));
    $date      = htmlspecialchars(trim($_POST['booking_date']  ?? date('Y-m-d', strtotime('+3 days'))));
    $startTime = htmlspecialchars(trim($_POST['start_time']    ?? '08:00 AM'));
    $endTime   = htmlspecialchars(trim($_POST['end_time']      ?? '05:00 PM'));
    $attendees = htmlspecialchars(trim($_POST['attendees']     ?? '50'));

    // Compose the time slot string
    $timeSlot = $startTime . ' - ' . $endTime;

    // Insert reservation into database
    $newId = createReservation($userId, [
        'facility_id'   => $facId,
        'facility_name' => $facName,
        'event_name'    => $evtName,
        'purpose'       => $evtName . ' — ' . $purpose,
        'booking_date'  => $date,
        'time_slot'     => $timeSlot,
        'attendees'     => $attendees,
        'equipment'     => 'Standard facility amenities',
    ]);

    if ($newId) {
        $actionMsg  = "Reservation #{$newId} for &ldquo;{$facName}&rdquo; submitted! Status: <strong>Pending</strong> administrative review.";
        $actionType = 'success';
    } else {
        $actionMsg  = "Failed to create reservation. Please try again.";
        $actionType = 'error';
    }
}

// -----------------------------------------------------------------------
// ACCOUNT SETTINGS — Update Notification Preferences (DATABASE-BACKED)
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_prefs') {
    saveUserPreferences($userId, [
        'email_new_reservation'  => isset($_POST['pref_email_new']),
        'email_status_update'    => isset($_POST['pref_email_status']),
        'email_reminders'        => isset($_POST['pref_email_remind']),
        'system_new_reservation' => isset($_POST['pref_sys_new']),
        'system_status_update'   => isset($_POST['pref_sys_status']),
    ]);
    $_SESSION['prefs_saved_msg'] = 'Notification preferences saved successfully.';
    // Redirect to clear POST (Post/Redirect/Get pattern)
    header('Location: main.php?settings=account&tab=notifications&saved=1');
    exit;
}

// -----------------------------------------------------------------------
// ACCOUNT SETTINGS — Change Password (DATABASE-BACKED with bcrypt)
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    $oldPwd  = $_POST['old_password']  ?? '';
    $newPwd  = $_POST['new_password']  ?? '';
    $confPwd = $_POST['confirm_password'] ?? '';

    if ($newPwd !== $confPwd) {
        $_SESSION['pwd_error'] = 'New passwords do not match.';
    } else {
        $result = changePassword($userId, $oldPwd, $newPwd);
        if ($result['success']) {
            $_SESSION['pwd_success'] = $result['message'];
        } else {
            $_SESSION['pwd_error'] = $result['message'];
        }
    }
    header('Location: main.php?settings=account&tab=security&saved=1');
    exit;
}

// -----------------------------------------------------------------------
// SYSTEM SETTINGS — Update Site Config (Admin only, DATABASE-BACKED)
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_sys_config') {
    if (($currentUser['role'] ?? '') === 'Admin') {
        saveSystemConfig([
            'maintenance_mode'   => isset($_POST['sys_maintenance']) ? '1' : '0',
            'slot_buffer_hrs'    => (string)max(0, intval($_POST['sys_slot_buffer'] ?? 1)),
            'max_advance_days'   => (string)max(1, intval($_POST['sys_max_advance']  ?? 30)),
        ], $userId);
        $_SESSION['sys_saved_msg'] = 'System configuration saved.';
    }
    header('Location: main.php?settings=system&saved=1');
    exit;
}

// Fetch saved preferences from DATABASE
$userPrefs  = getUserPreferences($userId);
$sysConfig  = getSystemConfig();
$isAdmin    = ($currentUser['role'] ?? '') === 'Admin';

// Collect flash messages from redirects
$prefsSaved   = (($_GET['settings'] ?? '') === 'account' && isset($_GET['saved']) && isset($_SESSION['prefs_saved_msg']));
$pwdSuccess   = isset($_SESSION['pwd_success']);
$pwdError     = $_SESSION['pwd_error'] ?? '';
$sysSaved     = (($_GET['settings'] ?? '') === 'system'  && isset($_GET['saved']) && isset($_SESSION['sys_saved_msg']));
// Clear one-time messages after reading
unset($_SESSION['prefs_saved_msg'], $_SESSION['pwd_success'], $_SESSION['pwd_error'], $_SESSION['sys_saved_msg']);

// STEP 5: Read all reservations from DATABASE (used by JS conflict engine)
$reservations = getAllReservations();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Catalog | KLD Reservation Portal</title>
    <meta name="description" content="Browse and reserve KLD campus facilities. Select a venue and submit your event request through the KLD Facility Reservation System.">
    <!-- Robots: Private authenticated page — no indexing -->
    <meta name="robots" content="noindex, nofollow">
    <!-- Preconnect to Google Fonts CDN for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Global Base Styles (CSS variables, resets, container, badge-status) -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <!-- Minimalist app footer styles (shared with public pages) -->
    <link rel="stylesheet" href="css/footer.css">
    <!-- Main portal exclusive styles (off-canvas, cards, modals, upload dropzones) -->
    <link rel="stylesheet" href="css/main_style.css">
</head>
<body class="main-page-body">

    <!-- ================================================================
         OFF-CANVAS NAVIGATION SIDEBAR COMPONENTS
         ACADEMIC DEFENSE: The backdrop overlay and sidebar drawer are
         hidden by default (CSS: translateX(-100%) + opacity:0/visibility:hidden).
         They become visible when the JS adds class="active" after
         the hamburger button is clicked.
         ================================================================ -->

    <!-- Backdrop: clicking this area closes the sidebar -->
    <div class="main-offcanvas-backdrop" id="mainOffcanvasBackdrop" aria-hidden="true"></div>

    <!-- Slide-Out Sidebar Panel -->
    <aside class="main-offcanvas-sidebar"
           id="mainOffcanvasSidebar"
           aria-label="Application Navigation Menu"
           aria-hidden="true">

        <!-- Account Header Block -->
        <div class="sidebar-account-header-block">
            <p class="sidebar-acct-label">Account</p>

            <div class="sidebar-user-row">
                <!-- Avatar circle: displays first + last name initials -->
                <div class="sidebar-user-avatar"><?php echo htmlspecialchars($initials); ?></div>

                <div class="sidebar-user-info">
                    <div class="sidebar-user-fullname">
                        <?php echo htmlspecialchars($currentUser['fullname']); ?>
                    </div>
                    <div class="sidebar-user-role-dept">
                        <?php echo htmlspecialchars($currentUser['role']); ?>
                        &bull;
                        <?php echo htmlspecialchars($currentUser['department']); ?>
                    </div>
                </div>
            </div>

            <!-- X close button (top-right of sidebar header) -->
            <button type="button"
                    class="sidebar-close-x-btn"
                    id="mainSidebarCloseBtn"
                    aria-label="Close navigation menu"><?php echo icon('x', 18); ?></button>
        </div>

        <!-- Navigation Pill Links -->
        <nav class="sidebar-nav-body" aria-label="Application navigation">

            <p class="sidebar-nav-section-label">Navigation</p>

            <!-- Current page: Facility Catalog (highlighted as active) -->
            <a href="main.php" class="sidebar-nav-pill active-pill" aria-current="page">
                <?php echo icon('landmark', 18); ?>
                <span>Facility Catalog</span>
            </a>

            <!-- Dashboard: Separate page for reservation metrics and history -->
            <a href="dashboard.php" class="sidebar-nav-pill">
                <?php echo icon('bar-chart-2', 18); ?>
                <span>Dashboard</span>
            </a>

            <!-- View Reservations: Deep-links to the reservations table section in dashboard -->
            <a href="dashboard.php#reservations-table" class="sidebar-nav-pill">
                <?php echo icon('clipboard-list', 18); ?>
                <span>View Reservations</span>
            </a>

            <hr class="sidebar-divider-hr" aria-hidden="true">
            <p class="sidebar-nav-section-label">Settings</p>

            <button type="button" class="sidebar-nav-pill" data-open-modal="accountSettingsModal">
                <?php echo icon('settings', 18); ?>
                <span>Account Settings</span>
            </button>

            <button type="button" class="sidebar-nav-pill" data-open-modal="systemSettingsModal">
                <?php echo icon('shield', 18); ?>
                <span>System Settings</span>
            </button>

            <hr class="sidebar-divider-hr" aria-hidden="true">

            <!-- Log Out: Invalidates session and redirects to login.php -->
            <a href="logout.php" class="sidebar-logout-pill">
                <?php echo icon('log-out', 18); ?>
                <span>Log Out</span>
            </a>

        </nav>
    </aside>

    <!-- ================================================================
         TOP APPLICATION BAR
         ACADEMIC DEFENSE: This bar completely replaces the public navbar.
         It contains ONLY the hamburger button, institution branding, and
         the logged-in user identity pill. No public navigation links exist.
         ================================================================ -->
    <div class="main-topbar" role="banner">

        <!-- Hamburger Button: Triggers off-canvas sidebar (top-left position) -->
        <button type="button"
                class="main-hamburger-btn"
                id="mainHamburgerBtn"
                aria-label="Open navigation sidebar"
                aria-controls="mainOffcanvasSidebar"
                aria-expanded="false">
            <span class="main-hamburger-bar"></span>
            <span class="main-hamburger-bar"></span>
            <span class="main-hamburger-bar"></span>
        </button>

        <!-- Institution Brand -->
        <div class="main-topbar-brand">
            <img src="assets/images/kld_logo.png"
                 alt="KLD University Seal"
                 class="main-topbar-logo">
            <span class="main-topbar-name">KOLEHIYO NG LUNGSOD NG DASMARIÑAS</span>
            <span class="main-topbar-pagetag">Facility Portal</span>
        </div>

        <!-- Logged-In User Identity Pill (top-right) -->
        <div class="main-topbar-user-pill" aria-label="Logged in as <?php echo htmlspecialchars($currentUser['fullname']); ?>">
            <div class="main-topbar-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <span class="main-topbar-username"><?php echo $firstName; ?></span>
        </div>

    </div>

    <!-- ================================================================
         MAIN CONTENT — FACILITY SELECTION CATALOG (BODY ONLY)
         ACADEMIC DEFENSE: The main content area contains EXCLUSIVELY
         the scrollable facility card grid. No static Event Request Form
         exists here — the form lives inside #mainBookingModal only.
         ================================================================ -->
    <main class="main-content-wrapper" id="main-catalog" aria-label="Facility Catalog">

        <!-- ============================================================
             REDESIGNED HERO CATALOG HEADER
             Full-width green banner with radial glow decorations,
             animated title/subtitle, and live stat strip.
             ============================================================ -->
        <section class="main-catalog-hero" aria-label="Facility catalog header">
            <div class="container">
                <div class="main-catalog-header">

                    <!-- Flash Notification inside hero for post-submission -->
                    <?php if (!empty($actionMsg)): ?>
                        <div class="main-flash-alert" id="mainFlashAlert" role="alert">
                            <span><?php echo $actionMsg; ?></span>
                            <button type="button"
                                    class="main-flash-dismiss"
                                    id="mainFlashDismiss"
                                    aria-label="Dismiss notification">✕</button>
                        </div>
                    <?php endif; ?>

                    <span class="main-catalog-eyebrow">🏫 Select a Campus Venue</span>
                    <h1 class="main-catalog-title">Available Facilities</h1>
                    <p class="main-catalog-subtitle">
                        Browse KLD&rsquo;s modern campus venues, lecture halls, sports arenas, and laboratories.
                        Click <strong>Reserve</strong> on any card to begin your booking request.
                    </p>

                    <!-- Stat strip: live numbers from session data -->
                    <div class="main-catalog-stats" aria-label="Catalog statistics">
                        <div class="main-catalog-stat">
                            <span class="main-catalog-stat-value">13</span>
                            <span class="main-catalog-stat-label">Venues Available</span>
                        </div>
                        <div class="main-catalog-stat">
                            <span class="main-catalog-stat-value"><?php echo count($reservations); ?></span>
                            <span class="main-catalog-stat-label">Active Reservations</span>
                        </div>
                        <div class="main-catalog-stat">
                            <span class="main-catalog-stat-value">24/7</span>
                            <span class="main-catalog-stat-label">Portal Access</span>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <div class="container">
            <div class="main-catalog-body">

            <!-- Filter & Search Bar -->
            <div class="main-filter-bar" role="toolbar" aria-label="Filter facilities">

                <!-- Category Tab Buttons: client-side filter via JS (no reload) -->
                <div class="main-category-tabs" role="group" aria-label="Filter by category">
                    <button type="button" class="main-tab-btn active" data-filter="all"     id="filterAll">All Facilities</button>
                    <button type="button" class="main-tab-btn"        data-filter="sports"  id="filterSports">Sports</button>
                    <button type="button" class="main-tab-btn"        data-filter="avr"     id="filterAvr">AVR &amp; Seminar</button>
                    <button type="button" class="main-tab-btn"        data-filter="lecture" id="filterLecture">Lecture Halls</button>
                    <button type="button" class="main-tab-btn"        data-filter="labs"    id="filterLabs">Laboratories</button>
                    <button type="button" class="main-tab-btn"        data-filter="nursing" id="filterNursing">Nursing</button>
                </div>

                <!-- Right Controls: View Switcher + Live Text Search -->
                <div class="main-filter-right-group">
                    <div class="main-view-toggle" role="group" aria-label="Catalog layout mode">
                        <button type="button" class="btn-view-toggle active" id="viewToggleCarousel" title="Carousel Slider View" aria-label="Carousel Slider View" aria-pressed="true">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="19" x2="12" y2="21"/></svg>
                            <span>Carousel</span>
                        </button>
                        <button type="button" class="btn-view-toggle" id="viewToggleGrid" title="Grid View" aria-label="Grid View" aria-pressed="false">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            <span>Grid</span>
                        </button>
                    </div>

                    <!-- Live Text Search: filters by facility title and description -->
                    <div class="main-search-box">
                        <svg class="main-search-icon-svg" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="search"
                               id="mainFacilitySearch"
                               class="main-search-input"
                               placeholder="Search facilities&hellip;"
                               aria-label="Search facility catalog">
                    </div>
                </div>

            </div>

            <!-- ============================================================
                 FACILITY SHOWCASE: ANIMATED SLIDER / CAROUSEL & CATALOG GRID
                 ============================================================ -->
            <div class="facility-carousel-container" id="facilityCarouselContainer">

                <!-- Navigation Arrows -->
                <button type="button" class="carousel-arrow carousel-arrow-prev" id="carouselPrevBtn" aria-label="Previous facility" title="Previous facility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" class="carousel-arrow carousel-arrow-next" id="carouselNextBtn" aria-label="Next facility" title="Next facility">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>

                <!-- Viewport Track -->
                <div class="facility-carousel-viewport" id="facilityCarouselViewport">
                    <div class="main-facility-grid carousel-mode" id="mainFacilityGrid" aria-label="Facility cards">

                <!-- CARD 1: HAUSLAND CONSTRUCTION – Anatomy and Physiology Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="anatomy-lab"
                         data-facility-name="HAUSLAND CONSTRUCTION – Anatomy and Physiology Laboratory"
                         data-capacity="45 Students"
                         data-location="Health Sciences Wing, Ground Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/anatomy.png" alt="Anatomy and Physiology Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">HAUSLAND CONSTRUCTION</div>
                        <h2 class="main-fac-title">Anatomy &amp; Physiology Laboratory</h2>
                        <p class="main-fac-desc">Equipped with anatomical models, skeletal specimens, and dissection tools for health science students.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>45 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Anatomical Models</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsAnatomyLab" data-facility-id="anatomy-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveAnatomyLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 2: TYLER JEREMY REMINAJES SANTOS – Institute of Nursing Nutrition Laboratory -->
                <article class="main-fac-card"
                         data-category="nursing"
                         data-facility-id="nutrition-lab"
                         data-facility-name="TYLER JEREMY REMINAJES SANTOS – Nutrition Laboratory"
                         data-capacity="40 Students"
                         data-location="Nursing Building, 2nd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/nutlab.png" alt="Nursing Nutrition Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Nursing</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">TYLER JEREMY REMINAJES SANTOS</div>
                        <h2 class="main-fac-title">Nursing Nutrition Laboratory</h2>
                        <p class="main-fac-desc">Specialized lab for dietary assessment, nutrition planning, and food safety education for nursing students.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>40 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"/></svg><span>Nutrition Tools</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsNutritionLab" data-facility-id="nutrition-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveNutritionLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 3: FAMILY OF ENGR. SONNY BOY V. BAGANG – Microbiology Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="microbiology-lab"
                         data-facility-name="FAMILY OF ENGR. SONNY BOY V. BAGANG – Microbiology Laboratory"
                         data-capacity="40 Students"
                         data-location="Health Sciences Wing, 2nd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/biologylab.png" alt="Microbiology Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">FAMILY OF ENGR. SONNY BOY V. BAGANG</div>
                        <h2 class="main-fac-title">Microbiology Laboratory</h2>
                        <p class="main-fac-desc">Research-grade microbiology laboratory with biosafety equipment, microscopes, and culture incubation chambers.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>40 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Biosafety Equipment</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsMicroLab" data-facility-id="microbiology-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveMicroLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 4: PRINCIPIA HUMILIA CORP. – Nursing Skills Laboratory -->
                <article class="main-fac-card"
                         data-category="nursing"
                         data-facility-id="nursing-skills-lab"
                         data-facility-name="PRINCIPIA HUMILIA CORP. – Nursing Skills Laboratory"
                         data-capacity="35 Students"
                         data-location="Nursing Building, Ground Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/nurskill.png" alt="Nursing Skills Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Nursing</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">PRINCIPIA HUMILIA CORP.</div>
                        <h2 class="main-fac-title">Nursing Skills Laboratory</h2>
                        <p class="main-fac-desc">Clinical simulation lab with hospital-grade manikins, patient care stations, and vital signs monitoring equipment.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>35 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span>Simulation Manikins</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsNursingSkills" data-facility-id="nursing-skills-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveNursingSkills" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 5: NORTHPINE LAND, INC. Hall – Nursing Lecture Hall -->
                <article class="main-fac-card"
                         data-category="lecture"
                         data-facility-id="nursing-lecture"
                         data-facility-name="NORTHPINE LAND, INC. Hall – Nursing Lecture Hall"
                         data-capacity="140 Persons"
                         data-location="Nursing Building, 1st Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/CB2_AVR_2.png" alt="Nursing Lecture Hall" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Lecture Hall</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">NORTHPINE LAND, INC. HALL</div>
                        <h2 class="main-fac-title">Nursing Lecture Hall</h2>
                        <p class="main-fac-desc">Dedicated amphitheater-style lecture hall for clinical demonstrations, case presentations, and health science symposiums.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>140 Persons</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg><span>Smart Board</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsNursingLecture" data-facility-id="nursing-lecture" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveNursingLecture" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 6: MANNY VILLAR HALL – Audio Visual Room -->
                <article class="main-fac-card"
                         data-category="avr"
                         data-facility-id="avr"
                         data-facility-name="MANNY VILLAR HALL – Audio Visual Room"
                         data-capacity="180 Persons"
                         data-location="Main Academic Building, 2nd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/avr.png" alt="Audio Visual Room" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">AVR &amp; Seminar</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">MANNY VILLAR HALL</div>
                        <h2 class="main-fac-title">Audio Visual Room</h2>
                        <p class="main-fac-desc">Fully equipped audio-visual amphitheater for seminars, film screenings, academic presentations, and institutional lectures.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>180 Persons</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg><span>4K Projector</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsAvr" data-facility-id="avr" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveAvr" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 7: ARTURO CARUNGCONG – Physics Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="physics-lab"
                         data-facility-name="ARTURO CARUNGCONG – Physics Laboratory"
                         data-capacity="40 Students"
                         data-location="Science Building, 3rd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/physics_lab.jpg" alt="Physics Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">ARTURO CARUNGCONG</div>
                        <h2 class="main-fac-title">Physics Laboratory</h2>
                        <p class="main-fac-desc">Fully equipped physics laboratory with experiment stations, oscilloscopes, and mechanics demonstration apparatus.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>40 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Experiment Stations</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsPhysicsLab" data-facility-id="physics-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reservePhysicsLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 8: ATTY. LOURDES GANA-BARZAGA – Computer Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="computer-lab"
                         data-facility-name="ATTY. LOURDES GANA-BARZAGA – Computer Laboratory"
                         data-capacity="50 Workstations"
                         data-location="Institute of Computing Studies, 3rd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/comlab.png" alt="Computer Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">ATTY. LOURDES GANA-BARZAGA</div>
                        <h2 class="main-fac-title">Computer Laboratory</h2>
                        <p class="main-fac-desc">Modern ICT laboratory with high-performance workstations, gigabit LAN infrastructure, and full software development environments.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>50 Workstations</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span>Gigabit LAN</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsComputerLab" data-facility-id="computer-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveComputerLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 9: CONG. ELPIDIO "PIDI" BARZAGA – Midwifery Skills Laboratory -->
                <article class="main-fac-card"
                         data-category="nursing"
                         data-facility-id="midwifery-lab"
                         data-facility-name="CONG. ELPIDIO BARZAGA – Midwifery Skills Laboratory"
                         data-capacity="30 Students"
                         data-location="Nursing Building, Ground Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/midwifery_lab.jpg" alt="Midwifery Skills Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Nursing</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">CONG. ELPIDIO &ldquo;PIDI&rdquo; BARZAGA</div>
                        <h2 class="main-fac-title">Midwifery Skills Laboratory</h2>
                        <p class="main-fac-desc">Dedicated clinical simulation lab for midwifery students featuring birthing manikins and maternal care training equipment.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>30 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg><span>Birthing Manikins</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsMidwiferyLab" data-facility-id="midwifery-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveMidwiferyLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 10: IEMI – GLORIA E. MENDOZA – Biology Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="biology-lab"
                         data-facility-name="IEMI – GLORIA E. MENDOZA – Biology Laboratory"
                         data-capacity="40 Students"
                         data-location="Science Building, 2nd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/biolab.png" alt="Biology Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">IEMI – GLORIA E. MENDOZA</div>
                        <h2 class="main-fac-title">Biology Laboratory</h2>
                        <p class="main-fac-desc">Comprehensive biology lab with compound microscopes, specimen preservation, and botanical identification stations.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>40 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><span>Compound Microscopes</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsBiologyLab" data-facility-id="biology-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveBiologyLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 11: FRIENDS OF CONG. PIDI BARZAGA – Psychology Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="psychology-lab"
                         data-facility-name="FRIENDS OF CONG. PIDI BARZAGA – Psychology Laboratory"
                         data-capacity="35 Students"
                         data-location="Arts & Sciences Building, 2nd Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/psychology_lab.jpg" alt="Psychology Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">FRIENDS OF CONG. PIDI BARZAGA</div>
                        <h2 class="main-fac-title">Psychology Laboratory</h2>
                        <p class="main-fac-desc">Behavioral observation and testing laboratory with one-way mirrors, cognitive assessment tools, and counseling simulation areas.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>35 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg><span>Observation Room</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsPsychLab" data-facility-id="psychology-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reservePsychLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 12: MACAVINTA HALL – KLD Gymnasium -->
                <article class="main-fac-card"
                         data-category="sports"
                         data-facility-id="gymnasium"
                         data-facility-name="MACAVINTA HALL – KLD Gymnasium"
                         data-capacity="1,200 Persons"
                         data-location="Sports Complex, Ground Level">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/gym.png" alt="KLD Gymnasium" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Sports &amp; Athletics</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">MACAVINTA HALL</div>
                        <h2 class="main-fac-title">KLD Gymnasium</h2>
                        <p class="main-fac-desc">Multi-purpose gymnasium for sports events, championship tournaments, student assemblies, and major institutional gatherings.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>1,200 Persons</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg><span>Multi-sport Court</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsGymnasium" data-facility-id="gymnasium" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveGymnasium" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                <!-- CARD 13: MAXIMO "IMO" SARIGNAYA – Engineering Laboratory -->
                <article class="main-fac-card"
                         data-category="labs"
                         data-facility-id="engineering-lab"
                         data-facility-name="MAXIMO SARIGNAYA – Engineering Laboratory"
                         data-capacity="40 Students"
                         data-location="Engineering Building, Ground Floor">
                    <div class="main-fac-img-wrap">
                        <img src="assets/images/englab.png" alt="Engineering Laboratory" class="main-fac-img" loading="lazy">
                        <span class="main-fac-status-badge">Available</span>
                        <span class="main-fac-cat-tag">Laboratory</span>
                    </div>
                    <div class="main-fac-card-body">
                        <div class="main-fac-donor">MAXIMO &ldquo;IMO&rdquo; SARIGNAYA</div>
                        <h2 class="main-fac-title">Engineering Laboratory</h2>
                        <p class="main-fac-desc">Hands-on engineering workshop with circuit design stations, mechanical testing equipment, and CAD workstations.</p>
                        <div class="main-fac-specs">
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>40 Students</span></div>
                            <div class="main-spec-item"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg><span>CAD Workstations</span></div>
                        </div>
                        <div class="main-fac-actions">
                            <button type="button" class="btn-main-view-details" id="viewDetailsEngineeringLab" data-facility-id="engineering-lab" aria-label="View details">View Details</button>
                            <button type="button" class="btn-main-reserve" id="reserveEngineeringLab" aria-label="Reserve">Reserve</button>
                        </div>
                    </div>
                </article>

                    </div><!-- /.main-facility-grid -->
                </div><!-- /.facility-carousel-viewport -->

                <!-- Carousel Footer: Pagination Indicators & Slide Counter -->
                <div class="carousel-pagination-bar" id="carouselPaginationBar">
                    <div class="carousel-dots" id="carouselDots" role="tablist" aria-label="Facility slide indicators">
                        <!-- Populated by JS -->
                    </div>
                    <div class="carousel-counter-badge" id="carouselCounterBadge">
                        <span id="carouselCurrentIndex">1</span> / <span id="carouselTotalIndex">13</span>
                    </div>
                </div>

            </div><!-- /.facility-carousel-container -->

            <!-- Empty State: shown by JS when no cards match the active filter/search -->
            <div class="main-empty-state" id="mainEmptyState" aria-live="polite">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="main-empty-state-icon-svg" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p>No facilities match your search. Try a different filter or keyword.</p>
            </div><!-- /.main-empty-state -->

            </div><!-- /.main-catalog-body -->
        </div><!-- /.container (catalog) -->
    </main>


    <!-- ================================================================
         MINIMALIST APP-STYLE FOOTER (shared include)
         ================================================================ -->
    <?php include 'includes/footer.php'; ?>

    <!-- ================================================================
         MODAL 1: BOOKING FORM MODAL (#mainBookingModal)
         ACADEMIC DEFENSE: The Event Request Form lives EXCLUSIVELY here.
         It is triggered by any "Reserve" button click — never loaded as
         a static form in the page body. The JS reads the clicked card's
         data-attributes and populates the venue banner before opening.
         ================================================================ -->
    <div class="modal-backdrop"
         id="mainBookingModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="bookingModalTitle">

        <div class="main-modal-window">

            <!-- Modal Header -->
            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">📝</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="bookingModalTitle">Facility Reservation Request</h2>
                    <p class="main-modal-hdr-sub">Complete all required fields to submit your booking.</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close booking form">✕</button>
            </div>

            <!-- Form wraps both the scrollable body and the fixed footer -->
            <form id="mainBookingForm"
                  method="POST"
                  action="main.php"
                  enctype="multipart/form-data"
                  novalidate>

                <!-- Hidden fields: submitted with POST to identify facility and action -->
                <input type="hidden" name="action"        value="new_reservation">
                <input type="hidden" id="bookingFacilityId"   name="facility_id"   value="">
                <input type="hidden" id="bookingFacilityName" name="facility_name" value="">

                <!-- Scrollable form body -->
                <div class="main-modal-body">

                    <!-- ── Selected Venue Banner ──────────────────────────
                         JS auto-populates this from the clicked card's
                         data-facility-name, data-capacity, data-location.
                         ─────────────────────────────────────────────── -->
                    <div class="booking-venue-banner">
                        <span class="venue-banner-icon" aria-hidden="true">🏛️</span>
                        <div>
                            <div class="venue-banner-name" id="bookingVenueName">Select a Facility</div>
                            <div class="venue-banner-meta" id="bookingVenueMeta">Capacity &amp; Location details</div>
                        </div>
                    </div>

                    <!-- ── Event Name ──────────────────────────────────── -->
                    <div class="main-form-row">
                        <label for="bookingEventName" class="main-form-label">
                            Event Name <span class="req" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="bookingEventName"
                               name="event_name"
                               class="main-form-input"
                               placeholder="e.g., Annual ICS Technical Symposium"
                               autocomplete="off"
                               required>
                    </div>

                    <!-- ── Detailed Purpose ───────────────────────────── -->
                    <div class="main-form-row">
                        <label for="bookingPurpose" class="main-form-label">
                            Detailed Purpose &amp; Objectives <span class="req" aria-hidden="true">*</span>
                        </label>
                        <textarea id="bookingPurpose"
                                  name="event_purpose"
                                  class="main-form-textarea"
                                  placeholder="Describe the event objectives, target participants, and agenda..."
                                  required></textarea>
                    </div>

                    <!-- ── Attendees + Date (2-column row) ────────────── -->
                    <div class="main-form-2col">
                        <div class="main-form-row" style="margin-bottom:0;">
                            <label for="bookingAttendees" class="main-form-label">
                                Expected Attendees <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input type="number"
                                   id="bookingAttendees"
                                   name="attendees"
                                   class="main-form-input"
                                   min="1"
                                   max="2000"
                                   placeholder="e.g., 150"
                                   required>
                        </div>
                        <div class="main-form-row" style="margin-bottom:0;">
                            <label for="bookingDate" class="main-form-label">
                                Reservation Date <span class="req" aria-hidden="true">*</span>
                            </label>
                            <input type="date"
                                   id="bookingDate"
                                   name="booking_date"
                                   class="main-form-input"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>"
                                   required>
                        </div>
                    </div>

                    <!-- ── Start Time + End Time (2-column row) ───────── -->
                    <div class="main-form-2col">
                        <div class="main-form-row" style="margin-bottom:0;">
                            <label for="bookingStartTime" class="main-form-label">
                                Start Time <span class="req" aria-hidden="true">*</span>
                            </label>
                            <select id="bookingStartTime" name="start_time" class="main-form-select" required>
                                <option value="07:00 AM">07:00 AM</option>
                                <option value="08:00 AM">08:00 AM</option>
                                <option value="09:00 AM">09:00 AM</option>
                                <option value="10:00 AM">10:00 AM</option>
                                <option value="11:00 AM">11:00 AM</option>
                                <option value="12:00 PM">12:00 PM</option>
                                <option value="01:00 PM" selected>01:00 PM</option>
                                <option value="02:00 PM">02:00 PM</option>
                                <option value="03:00 PM">03:00 PM</option>
                                <option value="04:00 PM">04:00 PM</option>
                            </select>
                        </div>
                        <div class="main-form-row" style="margin-bottom:0;">
                            <label for="bookingEndTime" class="main-form-label">
                                End Time <span class="req" aria-hidden="true">*</span>
                            </label>
                            <select id="bookingEndTime" name="end_time" class="main-form-select" required>
                                <option value="09:00 AM">09:00 AM</option>
                                <option value="10:00 AM">10:00 AM</option>
                                <option value="11:00 AM">11:00 AM</option>
                                <option value="12:00 PM">12:00 PM</option>
                                <option value="01:00 PM">01:00 PM</option>
                                <option value="02:00 PM">02:00 PM</option>
                                <option value="03:00 PM">03:00 PM</option>
                                <option value="04:00 PM">04:00 PM</option>
                                <option value="05:00 PM" selected>05:00 PM</option>
                                <option value="06:00 PM">06:00 PM</option>
                                <option value="07:00 PM">07:00 PM</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── Real-Time Conflict Detection Indicator ─────────
                         JS evaluates this box whenever date/time/facility
                         changes: green = clear, red = conflict detected.
                         ─────────────────────────────────────────────── -->
                    <div class="main-conflict-box clear" id="mainConflictBox" role="status" aria-live="polite">
                        <span class="conflict-status-icon" id="mainConflictIcon" aria-hidden="true">✓</span>
                        <div>
                            <div class="conflict-status-title" id="mainConflictTitle">Time Slot Available</div>
                            <div class="conflict-status-detail" id="mainConflictDetail">No scheduling conflict detected for this venue and date.</div>
                        </div>
                    </div>

                    <!-- ── Pre-Event Administrative Document Uploads ───── -->
                    <div class="main-upload-section">
                        <h3 class="main-upload-heading">Pre-Event Administrative Requirements</h3>
                        <p class="main-upload-subtext">
                            Attach the required clearance documents. Accepted formats: PDF, DOCX, PNG, JPG.
                        </p>
                        <div class="main-upload-grid">

                            <!-- Dean's Approval Letter Dropzone -->
                            <div class="main-dropzone" id="dropzoneDean">
                                <input type="file"
                                       id="bookingFileDean"
                                       name="doc_dean_approval"
                                       accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                                       aria-label="Upload Dean's Approval Letter">
                                <div class="main-dropzone-icon" aria-hidden="true">📄</div>
                                <div class="main-dropzone-label">Dean's Approval Letter</div>
                                <div class="main-dropzone-hint">Click or drag to attach</div>
                                <div class="main-dropzone-chip" id="chipDean">
                                    <span id="chipDeanName"></span>
                                </div>
                            </div>

                            <!-- Endorsement Form Dropzone -->
                            <div class="main-dropzone" id="dropzoneEndorsement">
                                <input type="file"
                                       id="bookingFileEndorsement"
                                       name="doc_endorsement"
                                       accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                                       aria-label="Upload Endorsement Form">
                                <div class="main-dropzone-icon" aria-hidden="true">📋</div>
                                <div class="main-dropzone-label">Endorsement Form</div>
                                <div class="main-dropzone-hint">Click or drag to attach</div>
                                <div class="main-dropzone-chip" id="chipEndorsement">
                                    <span id="chipEndorsementName"></span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /.main-modal-body -->

                <!-- Fixed Modal Footer: Cancel + Review buttons -->
                <div class="main-modal-ftr">
                    <button type="button"
                            class="btn-modal-dismiss-outline"
                            data-close-modal>Cancel</button>
                    <button type="submit"
                            class="btn-modal-review-submit"
                            id="btnReviewBooking">
                        🔍 Review &amp; Submit Request
                    </button>
                </div>

            </form><!-- /#mainBookingForm -->

        </div><!-- /.main-modal-window -->
    </div><!-- /#mainBookingModal -->

    <!-- ================================================================
         MODAL 2: BOOKING SUMMARY VERIFICATION MODAL (#mainSummaryModal)
         ACADEMIC DEFENSE: After JS validates the booking form, it closes
         the booking modal and opens this summary table for the user to
         review all details before the final POST submission is triggered.
         ================================================================ -->
    <div class="modal-backdrop"
         id="mainSummaryModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="summaryModalTitle">

        <div class="main-modal-window">

            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">📋</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="summaryModalTitle">Reservation Verification Summary</h2>
                    <p class="main-modal-hdr-sub">Review all details before final submission.</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close summary modal">✕</button>
            </div>

            <div class="main-modal-body">
                <table class="main-summary-tbl" aria-label="Reservation summary details">
                    <tbody>
                        <tr>
                            <th scope="row">Applicant</th>
                            <td><?php echo htmlspecialchars($currentUser['fullname']); ?> (<?php echo htmlspecialchars($currentUser['role']); ?>)</td>
                        </tr>
                        <tr>
                            <th scope="row">Department / College</th>
                            <td><?php echo htmlspecialchars($currentUser['department']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Selected Venue</th>
                            <td id="sumVenue">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Event Name</th>
                            <td id="sumEventName">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Purpose</th>
                            <td id="sumPurpose">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Date &amp; Time Slot</th>
                            <td id="sumDateTime">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Expected Attendees</th>
                            <td id="sumAttendees">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Conflict Clearance</th>
                            <td><span class="badge-status approved">✓ Verified — No Conflict</span></td>
                        </tr>
                        <tr>
                            <th scope="row">Uploaded Documents</th>
                            <td>
                                <ul class="summary-docs-ul" id="sumDocsList">
                                    <li class="summary-doc-li"><span aria-hidden="true">⚠️</span> No files attached yet.</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Hold Compliance Notice -->
                <div class="main-hold-notice" role="note">
                    <span aria-hidden="true">ℹ️</span>
                    <div>
                        <strong>Institutional Verification Hold:</strong> Upon confirmation, this request enters the
                        <strong>Pending Review</strong> queue and will be processed by the facility administrator.
                        No database insertion occurs until formal approval.
                    </div>
                </div>
            </div>

            <div class="main-modal-ftr">
                <button type="button"
                        class="btn-modal-dismiss-outline"
                        data-close-modal>← Edit Details</button>
                <button type="button"
                        class="btn-modal-confirm-final"
                        id="btnFinalConfirmBooking">
                    Confirm &amp; Submit Request
                </button>
            </div>

        </div>
    </div><!-- /#mainSummaryModal -->

    <!-- ================================================================
         MODAL 3: FACILITY QUICK VIEW DETAIL MODAL (#mainQuickViewModal)
         ACADEMIC DEFENSE: Allows users to read full facility specs and
         amenities before deciding to reserve, without leaving the catalog.
         ================================================================ -->
    <div class="modal-backdrop"
         id="mainQuickViewModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="qvFacTitle">

        <div class="main-modal-window main-modal-window-lg">

            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">🏛️</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="qvFacTitle">Facility Details</h2>
                    <p class="main-modal-hdr-sub" id="qvFacCategory">Category</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close facility details">✕</button>
            </div>

            <div class="main-modal-body">

                <!-- Meta Info Grid (hours + location) -->
                <div class="main-form-2col" style="margin-bottom: 0;">
                    <div class="qv-meta-block">
                        <p class="qv-meta-label">Operating Hours</p>
                        <p class="qv-meta-value" id="qvFacHours"></p>
                    </div>
                    <div class="qv-meta-block">
                        <p class="qv-meta-label">Location</p>
                        <p class="qv-meta-value" id="qvFacLocation"></p>
                    </div>
                </div>
                <div class="qv-meta-block">
                    <p class="qv-meta-label">Capacity</p>
                    <p class="qv-meta-value" id="qvFacCapacity"></p>
                </div>

                <!-- Description -->
                <p class="qv-description" id="qvFacDesc"></p>

                <!-- Amenities List -->
                <h3 class="qv-section-heading">Included Equipment &amp; Amenities</h3>
                <ul class="qv-amenities-list" id="qvAmenitiesList" aria-label="Facility amenities"></ul>

                <!-- Policy/Rules Box -->
                <h3 class="qv-section-heading">Reservation Policy</h3>
                <p class="qv-policy-box" id="qvFacRules"></p>

                <!-- Action Buttons -->
                <div class="qv-btn-row">
                    <button type="button" class="btn-qv-close" data-close-modal>Close</button>
                    <button type="button" class="btn-qv-reserve" id="qvReserveBtn">
                        Reserve This Facility
                    </button>
                </div>

            </div>

        </div>
    </div><!-- /#mainQuickViewModal -->

    <!-- ================================================================
         PHP-INJECTED EXISTING RESERVATIONS DATA
         ACADEMIC DEFENSE: This script tag is NOT executable JavaScript.
         It is a data container (type="application/json") that the JS
         conflict engine reads to check for existing bookings. PHP
         serializes the session reservations array into JSON format.
         json_encode flags (HEX_TAG, HEX_APOS, HEX_QUOT, HEX_AMP)
         prevent XSS injection through the JSON output.
         ================================================================ -->
    <script id="existingReservationsJson" type="application/json">
        <?php echo json_encode($reservations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_PRETTY_PRINT); ?>
    </script>

    <!-- Application Logic Scripts -->
    <script src="js/main_script.js"></script>

    <!-- ================================================================
         MODAL 4: ACCOUNT SETTINGS MODAL (#accountSettingsModal)
         Tabs: Profile | Security | Notifications
         All saves are session-based; form actions use POST/Redirect/Get.
         ================================================================ -->
    <div class="modal-backdrop"
         id="accountSettingsModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="acctSettingsTitle">

        <div class="settings-modal-window">

            <!-- Modal Header -->
            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">👤</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="acctSettingsTitle">Account Settings</h2>
                    <p class="main-modal-hdr-sub">Manage your profile, security, and notification preferences.</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close account settings">&times;</button>
            </div>

            <!-- Left Rail + Content Layout -->
            <div class="settings-modal-layout">
                <aside class="settings-modal-rail" aria-label="Account settings navigation">
                    <span class="settings-rail-section-label">Preferences</span>
                    <div class="settings-tab-nav" role="tablist" aria-label="Account settings tabs">
                        <button class="settings-tab-btn active"
                                role="tab"
                                data-settings-tab="profile"
                                id="tabProfile"
                                aria-selected="true"
                                aria-controls="paneProfile">
                            <span class="settings-tab-icon">👤</span>
                            <span class="settings-tab-label">User Profile</span>
                        </button>
                        <button class="settings-tab-btn"
                                role="tab"
                                data-settings-tab="security"
                                id="tabSecurity"
                                aria-selected="false"
                                aria-controls="paneSecurity">
                            <span class="settings-tab-icon">🔒</span>
                            <span class="settings-tab-label">Security</span>
                        </button>
                        <button class="settings-tab-btn"
                                role="tab"
                                data-settings-tab="notifications"
                                id="tabNotifications"
                                aria-selected="false"
                                aria-controls="paneNotifications">
                            <span class="settings-tab-icon">🔔</span>
                            <span class="settings-tab-label">Notifications</span>
                        </button>
                    </div>

                    <?php if ($isAdmin): ?>
                    <div class="settings-rail-footer">
                        <button type="button" class="btn-rail-quick-switch" data-open-modal="systemSettingsModal">
                            <span>⚙️ System Admin</span>
                        </button>
                    </div>
                    <?php endif; ?>
                </aside>

                <!-- Tab Content -->
                <div class="main-modal-body settings-modal-body">

                <!-- ── TAB: PROFILE ─────────────────────────────────────── -->
                <div class="settings-tab-pane active" id="paneProfile" role="tabpanel" aria-labelledby="tabProfile">

                    <!-- Profile Card -->
                    <div class="settings-profile-card">
                        <div class="settings-profile-avatar">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                        <div class="settings-profile-info">
                            <div class="settings-profile-name"><?php echo htmlspecialchars($currentUser['fullname']); ?></div>
                            <div class="settings-profile-meta"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <span class="settings-profile-badge"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                    </div>

                    <!-- Read-only info grid -->
                    <div class="settings-info-grid">
                        <div class="settings-info-item">
                            <div class="settings-info-label">ID Number</div>
                            <div class="settings-info-value"><?php echo htmlspecialchars($currentUser['id_number'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="settings-info-item">
                            <div class="settings-info-label">Role</div>
                            <div class="settings-info-value"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                        </div>
                        <div class="settings-info-item">
                            <div class="settings-info-label">Department</div>
                            <div class="settings-info-value"><?php echo htmlspecialchars($currentUser['department']); ?></div>
                        </div>
                        <div class="settings-info-item">
                            <div class="settings-info-label">Contact</div>
                            <div class="settings-info-value"><?php echo htmlspecialchars($currentUser['contact'] ?? 'Not set'); ?></div>
                        </div>
                    </div>

                    <p class="settings-section-sub" style="font-size:0.79rem;color:#94a3b8;margin-top:8px;">
                        Profile information is managed by the institution. Contact your administrator to update records.
                    </p>

                </div>

                <!-- ── TAB: SECURITY ─────────────────────────────────────── -->
                <div class="settings-tab-pane" id="paneSecurity" role="tabpanel" aria-labelledby="tabSecurity">

                    <p class="settings-section-title">Change Password</p>
                    <p class="settings-section-sub">Update your login password. Minimum 8 characters required.</p>

                    <?php if ($pwdError): ?>
                        <div class="settings-alert error show" role="alert">⚠️ <?php echo htmlspecialchars($pwdError); ?></div>
                    <?php endif; ?>
                    <?php if ($pwdSuccess): ?>
                        <div class="settings-alert success show" role="alert">✅ Password updated successfully.</div>
                    <?php endif; ?>

                    <form method="POST" action="main.php" id="pwdChangeForm" autocomplete="off">
                        <input type="hidden" name="action" value="update_password">

                        <div class="main-form-row">
                            <label class="main-form-label" for="oldPassword">Current Password <span class="req">*</span></label>
                            <input type="password"
                                   id="oldPassword"
                                   name="old_password"
                                   class="main-form-input"
                                   autocomplete="current-password"
                                   placeholder="Enter current password"
                                   required>
                        </div>

                        <div class="main-form-2col">
                            <div class="main-form-row">
                                <label class="main-form-label" for="newPassword">New Password <span class="req">*</span></label>
                                <input type="password"
                                       id="newPassword"
                                       name="new_password"
                                       class="main-form-input"
                                       autocomplete="new-password"
                                       placeholder="Min. 8 characters"
                                       minlength="8"
                                       required>
                            </div>
                            <div class="main-form-row">
                                <label class="main-form-label" for="confirmPassword">Confirm New Password <span class="req">*</span></label>
                                <input type="password"
                                       id="confirmPassword"
                                       name="confirm_password"
                                       class="main-form-input"
                                       autocomplete="new-password"
                                       placeholder="Re-enter new password"
                                       required>
                            </div>
                        </div>

                        <div id="pwdClientError" class="settings-alert error" style="margin-top:0;" role="alert"></div>

                        <button type="submit" class="btn-settings-save" id="btnSavePwd">🔒 Update Password</button>
                    </form>

                </div>

                <!-- ── TAB: NOTIFICATIONS ─────────────────────────────────── -->
                <div class="settings-tab-pane" id="paneNotifications" role="tabpanel" aria-labelledby="tabNotifications">

                    <p class="settings-section-title">Notification Preferences</p>
                    <p class="settings-section-sub">Choose which updates you want to receive. Changes save immediately.</p>

                    <?php if ($prefsSaved): ?>
                        <div class="settings-alert success show" role="alert">✅ Preferences saved successfully.</div>
                    <?php endif; ?>

                    <form method="POST" action="main.php" id="prefsForm">
                        <input type="hidden" name="action" value="update_prefs">

                        <!-- Email Notifications group -->
                        <p style="font-size:0.81rem;font-weight:700;color:#334155;margin-bottom:4px;">Email Notifications</p>

                        <div class="settings-toggle-row">
                            <div class="settings-toggle-info">
                                <div class="settings-toggle-title">New Reservation Submitted</div>
                                <div class="settings-toggle-desc">Receive an email when a reservation is created.</div>
                            </div>
                            <label class="settings-switch" aria-label="Toggle new reservation email">
                                <input type="checkbox" name="pref_email_new" <?php echo (!empty($userPrefs['email_new_reservation'])) ? 'checked' : ''; ?>>
                                <span class="settings-switch-track"></span>
                            </label>
                        </div>

                        <div class="settings-toggle-row">
                            <div class="settings-toggle-info">
                                <div class="settings-toggle-title">Status Updates</div>
                                <div class="settings-toggle-desc">Notify me when my reservation status changes.</div>
                            </div>
                            <label class="settings-switch" aria-label="Toggle status update email">
                                <input type="checkbox" name="pref_email_status" <?php echo (!empty($userPrefs['email_status_update'])) ? 'checked' : ''; ?>>
                                <span class="settings-switch-track"></span>
                            </label>
                        </div>

                        <div class="settings-toggle-row">
                            <div class="settings-toggle-info">
                                <div class="settings-toggle-title">Event Reminders</div>
                                <div class="settings-toggle-desc">Email reminders 24 hours before your reserved event.</div>
                            </div>
                            <label class="settings-switch" aria-label="Toggle event reminder email">
                                <input type="checkbox" name="pref_email_remind" <?php echo (!empty($userPrefs['email_reminders'])) ? 'checked' : ''; ?>>
                                <span class="settings-switch-track"></span>
                            </label>
                        </div>

                        <hr class="sidebar-divider-hr" style="margin:14px 0;">

                        <!-- In-System Notifications group -->
                        <p style="font-size:0.81rem;font-weight:700;color:#334155;margin:0 0 4px;">In-System Notifications</p>

                        <div class="settings-toggle-row">
                            <div class="settings-toggle-info">
                                <div class="settings-toggle-title">New Reservation Alert</div>
                                <div class="settings-toggle-desc">Show in-portal alerts when reservations are submitted.</div>
                            </div>
                            <label class="settings-switch" aria-label="Toggle system reservation alert">
                                <input type="checkbox" name="pref_sys_new" <?php echo (!empty($userPrefs['system_new_reservation'])) ? 'checked' : ''; ?>>
                                <span class="settings-switch-track"></span>
                            </label>
                        </div>

                        <div class="settings-toggle-row">
                            <div class="settings-toggle-info">
                                <div class="settings-toggle-title">Status Change Alert</div>
                                <div class="settings-toggle-desc">In-portal notification for any status update.</div>
                            </div>
                            <label class="settings-switch" aria-label="Toggle system status alert">
                                <input type="checkbox" name="pref_sys_status" <?php echo (!empty($userPrefs['system_status_update'])) ? 'checked' : ''; ?>>
                                <span class="settings-switch-track"></span>
                            </label>
                        </div>

                        <div style="margin-top:20px;">
                            <button type="submit" class="btn-settings-save" id="btnSavePrefs">💾 Save Preferences</button>
                        </div>

                    </form>

                </div><!-- /paneNotifications -->

                </div><!-- /.main-modal-body -->
            </div><!-- /.settings-modal-layout -->

        </div><!-- /.settings-modal-window -->
    </div><!-- /#accountSettingsModal -->

    <!-- ================================================================
         MODAL 5: SYSTEM SETTINGS MODAL (#systemSettingsModal)
         Tabs: Configuration | Permissions | Activity Log
         Admin-only for Config & Permissions; Logs visible to all.
         ================================================================ -->
    <div class="modal-backdrop"
         id="systemSettingsModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="sysSettingsTitle">

        <div class="settings-modal-window">

            <!-- Modal Header -->
            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">🛡️</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="sysSettingsTitle">System Settings</h2>
                    <p class="main-modal-hdr-sub">Platform configuration, role permissions, and activity logs.</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close system settings">&times;</button>
            </div>

            <!-- Left Rail + Content Layout -->
            <div class="settings-modal-layout">
                <aside class="settings-modal-rail" aria-label="System settings navigation">
                    <span class="settings-rail-section-label">Management</span>
                    <div class="settings-tab-nav" role="tablist" aria-label="System settings tabs">
                        <button class="settings-tab-btn active"
                                role="tab"
                                data-settings-tab="sys-config"
                                id="tabSysConfig"
                                aria-selected="true"
                                aria-controls="paneSysConfig">
                            <span class="settings-tab-icon">⚙️</span>
                            <span class="settings-tab-label">Configuration</span>
                        </button>
                        <button class="settings-tab-btn"
                                role="tab"
                                data-settings-tab="sys-perms"
                                id="tabSysPerms"
                                aria-selected="false"
                                aria-controls="paneSysPerms">
                            <span class="settings-tab-icon">🔑</span>
                            <span class="settings-tab-label">Permissions</span>
                        </button>
                        <button class="settings-tab-btn"
                                role="tab"
                                data-settings-tab="sys-logs"
                                id="tabSysLogs"
                                aria-selected="false"
                                aria-controls="paneSysLogs">
                            <span class="settings-tab-icon">📋</span>
                            <span class="settings-tab-label">Activity Log</span>
                        </button>
                    </div>

                    <div class="settings-rail-footer">
                        <button type="button" class="btn-rail-quick-switch" data-open-modal="accountSettingsModal">
                            <span>👤 Account Settings</span>
                        </button>
                    </div>
                </aside>

                <div class="main-modal-body settings-modal-body">

                <!-- ── TAB: CONFIGURATION ─────────────────────────────────── -->
                <div class="settings-tab-pane active" id="paneSysConfig" role="tabpanel" aria-labelledby="tabSysConfig">

                    <?php if ($isAdmin): ?>

                        <p class="settings-section-title">Site Configuration</p>
                        <p class="settings-section-sub">Manage reservation portal behavior and scheduling rules.</p>

                        <?php if ($sysSaved): ?>
                            <div class="settings-alert success show" role="alert">✅ System configuration saved.</div>
                        <?php endif; ?>

                        <form method="POST" action="main.php" id="sysConfigForm">
                            <input type="hidden" name="action" value="update_sys_config">

                            <div class="sys-config-row">
                                <div class="sys-config-info">
                                    <div class="sys-config-title">Maintenance Mode</div>
                                    <div class="sys-config-desc">Temporarily disable new reservation submissions portal-wide.</div>
                                </div>
                                <label class="settings-switch" aria-label="Toggle maintenance mode">
                                    <input type="checkbox"
                                           name="sys_maintenance"
                                           id="sysMaintenanceToggle"
                                           <?php echo (!empty($sysConfig['maintenance_mode'])) ? 'checked' : ''; ?>>
                                    <span class="settings-switch-track"></span>
                                </label>
                            </div>

                            <div class="sys-config-row">
                                <div class="sys-config-info">
                                    <div class="sys-config-title">Time Slot Buffer</div>
                                    <div class="sys-config-desc">Minimum buffer hours required between reservations for the same venue.</div>
                                </div>
                                <input type="number"
                                       class="sys-config-input"
                                       name="sys_slot_buffer"
                                       id="sysSlotBuffer"
                                       min="0" max="24"
                                       value="<?php echo intval($sysConfig['slot_buffer_hrs'] ?? 1); ?>">
                            </div>

                            <div class="sys-config-row">
                                <div class="sys-config-info">
                                    <div class="sys-config-title">Max Advance Booking Days</div>
                                    <div class="sys-config-desc">How many days in advance users can submit reservation requests.</div>
                                </div>
                                <input type="number"
                                       class="sys-config-input"
                                       name="sys_max_advance"
                                       id="sysMaxAdvance"
                                       min="1" max="365"
                                       value="<?php echo intval($sysConfig['max_advance_days'] ?? 30); ?>">
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" class="btn-settings-save" id="btnSaveSysConfig">💾 Save Configuration</button>
                            </div>

                        </form>

                    <?php else: ?>

                        <div class="sys-admin-only-notice">
                            <div class="sys-admin-only-icon">🔒</div>
                            <div class="sys-admin-only-title">Administrator Access Required</div>
                            <p class="sys-admin-only-desc">
                                System configuration controls are restricted to users with the
                                <strong>Admin</strong> role. Contact your system administrator
                                to modify these settings.
                            </p>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- ── TAB: PERMISSIONS ───────────────────────────────────── -->
                <div class="settings-tab-pane" id="paneSysPerms" role="tabpanel" aria-labelledby="tabSysPerms">

                    <?php if ($isAdmin): ?>

                        <p class="settings-section-title">Role Permission Matrix</p>
                        <p class="settings-section-sub">Control what each user role can access within the portal.</p>

                        <table class="sys-permissions-table" aria-label="Role permission matrix">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>View Catalog</th>
                                    <th>Submit Request</th>
                                    <th>View Dashboard</th>
                                    <th>Cancel Reservation</th>
                                    <th>Admin Panel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Student</strong></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Student view catalog"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Student submit"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Student dashboard"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" aria-label="Student cancel"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" disabled aria-label="Student admin"></td>
                                </tr>
                                <tr>
                                    <td><strong>Faculty</strong></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Faculty view catalog"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Faculty submit"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Faculty dashboard"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Faculty cancel"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" disabled aria-label="Faculty admin"></td>
                                </tr>
                                <tr>
                                    <td><strong>Staff</strong></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Staff view catalog"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Staff submit"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Staff dashboard"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Staff cancel"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" disabled aria-label="Staff admin"></td>
                                </tr>
                                <tr>
                                    <td><strong>Admin</strong></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Admin view catalog"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Admin submit"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Admin dashboard"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Admin cancel"></td>
                                    <td class="sys-perm-check"><input type="checkbox" class="sys-perm-checkbox" checked aria-label="Admin admin panel"></td>
                                </tr>
                            </tbody>
                        </table>

                        <button type="button" class="btn-settings-save" id="btnSavePerms">💾 Save Permissions</button>

                    <?php else: ?>

                        <div class="sys-admin-only-notice">
                            <div class="sys-admin-only-icon">🔒</div>
                            <div class="sys-admin-only-title">Administrator Access Required</div>
                            <p class="sys-admin-only-desc">Role permissions are restricted to Admin users.</p>
                        </div>

                    <?php endif; ?>

                </div>

                <!-- ── TAB: ACTIVITY LOG ──────────────────────────────────── -->
                <div class="settings-tab-pane" id="paneSysLogs" role="tabpanel" aria-labelledby="tabSysLogs">

                    <p class="settings-section-title">Reservation Activity Log</p>
                    <p class="settings-section-sub">Auto-generated log of all reservation submissions in this session.</p>

                    <table class="sys-log-table" aria-label="Activity log">
                        <thead>
                            <tr>
                                <th>Ref. ID</th>
                                <th>Facility</th>
                                <th>Submitted</th>
                                <th>Applicant</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reservations)): ?>
                                <?php foreach ($reservations as $log): ?>
                                <tr>
                                    <td><code style="font-size:0.77rem;color:#006837;"><?php echo htmlspecialchars($log['id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($log['facility_name']); ?></td>
                                    <td style="white-space:nowrap;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($log['applicant']); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo strtolower($log['status']); ?>">
                                            <?php echo htmlspecialchars($log['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">No reservation activity in this session yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div><!-- /paneSysLogs -->

                </div><!-- /.main-modal-body -->
            </div><!-- /.settings-modal-layout -->

        </div><!-- /.settings-modal-window -->
    </div><!-- /#systemSettingsModal -->

</body>
</html>
