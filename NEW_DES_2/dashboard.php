<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * PAGE: User Dashboard — Booking Overview & Reservation History (dashboard.php)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 *
 * 1. AUTHENTICATION GATE: requireLogin() is the very first callable. Any
 *    unauthenticated HTTP request is immediately redirected to login.php.
 *
 * 2. DATABASE-BACKED: All reservation reads and cancellations are executed
 *    against the MySQL `reservations` table via prepared statements.
 *
 * 3. NAVIGATION: Off-canvas sidebar (hamburger top-left) matches main.php.
 *
 * 4. RESERVATION TABLE: Reads from `reservations` table and renders color-coded
 *    status badges for each row.
 *
 * 5. CANCEL ACTION: Pending reservations can be cancelled via an inline POST form.
 *    The status is updated to "Cancelled" in the database.
 *
 * 6. COMPLETE CODE DECOUPLING:
 *    - Zero inline styles  → css/dashboard_style.css
 *    - Zero inline scripts → js/main_script.js (sidebar) + js/script.js (table)
 */
require_once 'includes/auth_helper.php';
require_once 'includes/icons.php';

// STEP 1: Authenticate — kick unauthenticated visitors to login.php
requireLogin();

// STEP 2: Load the authenticated user from session
$currentUser = getCurrentUser();
$userId = (int)($currentUser['id'] ?? 0);

// STEP 3: Build avatar initials (First + Last name)
$nameParts = explode(' ', trim($currentUser['fullname']));
$initials   = strtoupper(
    substr($nameParts[0], 0, 1) .
    (isset($nameParts[count($nameParts) - 1]) ? substr($nameParts[count($nameParts) - 1], 0, 1) : '')
);
$firstName = htmlspecialchars($nameParts[0]);

// STEP 4: Handle the "Cancel Reservation" POST action (DATABASE UPDATE)
$actionMsg  = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_reservation') {
    $cancelId = htmlspecialchars(trim($_POST['res_id'] ?? ''));
    $cancelled = cancelReservation($cancelId, $userId);
    if ($cancelled) {
        $actionMsg  = "Reservation #{$cancelId} has been successfully cancelled.";
        $actionType = 'warning';
    } else {
        $actionMsg  = "Unable to cancel reservation #{$cancelId}. It may already be processed.";
        $actionType = 'error';
    }
}

// STEP 5: Calculate overview metric counters from DATABASE
$reservations    = getUserReservations($userId);
$totalCount      = count($reservations);
$approvedCount   = count(array_filter($reservations, fn($r) => $r['status'] === 'Approved'));
$pendingCount    = count(array_filter($reservations, fn($r) => $r['status'] === 'Pending'));
$rejectedCount   = count(array_filter($reservations, fn($r) => ($r['status'] === 'Rejected' || $r['status'] === 'Cancelled')));
$completedCount  = count(array_filter($reservations, fn($r) => $r['status'] === 'Completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | KLD Reservation Portal</title>
    <meta name="description" content="KLD Facility Reservation Dashboard — view your booking history, metrics, and manage active reservations.">
    <!-- Robots: Private authenticated page — no indexing -->
    <meta name="robots" content="noindex, nofollow">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Global base styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <!-- Footer styles (shared) -->
    <link rel="stylesheet" href="css/footer.css">
    <!-- Dashboard-exclusive styles -->
    <link rel="stylesheet" href="css/dashboard_style.css">
</head>
<body class="dash-page-body">

    <!-- ================================================================
         OFF-CANVAS BACKDROP & NAVIGATION SIDEBAR
         ACADEMIC DEFENSE: Identical component architecture to main.php.
         Hidden by default (translateX(-100%) + opacity:0). JS adds the
         .active class when the hamburger button is clicked, sliding it
         into view. The backdrop click-area closes it without navigating.
         ================================================================ -->

    <!-- Dark backdrop: clicking closes the sidebar (no navigation) -->
    <div class="main-offcanvas-backdrop" id="mainOffcanvasBackdrop" aria-hidden="true"></div>

    <!-- Slide-Out Navigation Sidebar -->
    <aside class="main-offcanvas-sidebar"
           id="mainOffcanvasSidebar"
           aria-label="Application Navigation Menu"
           aria-hidden="true">

        <!-- Account Header Block (matches main.php sidebar) -->
        <div class="sidebar-account-header-block">
            <p class="sidebar-acct-label">Account</p>

            <div class="sidebar-user-row">
                <!-- Avatar circle: First + Last initial -->
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

            <!-- X button: closes sidebar without navigating -->
            <button type="button"
                    class="sidebar-close-x-btn"
                    id="mainSidebarCloseBtn"
                    aria-label="Close navigation menu"><?php echo icon('x', 18); ?></button>
        </div>

        <!-- Navigation Pill Links
             ACADEMIC DEFENSE: No link to index.php exists here.
             Logged-in users navigate only between authenticated pages. -->
        <nav class="sidebar-nav-body" aria-label="Application navigation">

            <p class="sidebar-nav-section-label">Navigation</p>

            <!-- Facility Catalog: The primary booking page -->
            <a href="main.php" class="sidebar-nav-pill">
                <?php echo icon('landmark', 18); ?>
                <span>Facility Catalog</span>
            </a>

            <!-- Dashboard: Current page (highlighted active) -->
            <a href="dashboard.php" class="sidebar-nav-pill active-pill" aria-current="page">
                <?php echo icon('bar-chart-2', 18); ?>
                <span>Dashboard</span>
            </a>

            <!-- View Reservations: Jumps to the history table on this page -->
            <a href="dashboard.php#reservations-table" class="sidebar-nav-pill">
                <?php echo icon('clipboard-list', 18); ?>
                <span>View Reservations</span>
            </a>

            <hr class="sidebar-divider-hr" aria-hidden="true">
            <p class="sidebar-nav-section-label">Settings</p>

            <a href="#account-settings" class="sidebar-nav-pill">
                <?php echo icon('settings', 18); ?>
                <span>Account Settings</span>
            </a>

            <a href="#system-settings" class="sidebar-nav-pill">
                <?php echo icon('shield', 18); ?>
                <span>System Settings</span>
            </a>

            <hr class="sidebar-divider-hr" aria-hidden="true">

            <!-- LOGOUT: Sends request to logout.php which destroys
                 the session (session_destroy()) and redirects to login.php.
                 This is the ONLY way a logged-in user exits the system. -->
            <a href="logout.php" class="sidebar-logout-pill">
                <?php echo icon('log-out', 18); ?>
                <span>Log Out</span>
            </a>

        </nav>
    </aside>

    <!-- ================================================================
         TOP APPLICATION BAR (Matches main.php — no public navbar)
         ACADEMIC DEFENSE: This bar replaces the public nav completely.
         Contains only hamburger trigger, institution brand, and user pill.
         ================================================================ -->
    <div class="main-topbar" role="banner">

        <!-- Hamburger Button: opens off-canvas sidebar (top-left) -->
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
            <span class="main-topbar-pagetag">Dashboard</span>
        </div>

        <!-- Logged-In User Identity Pill -->
        <div class="main-topbar-user-pill"
             aria-label="Logged in as <?php echo htmlspecialchars($currentUser['fullname']); ?>">
            <div class="main-topbar-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <span class="main-topbar-username"><?php echo $firstName; ?></span>
        </div>

    </div>

    <!-- ================================================================
         MAIN CONTENT — DASHBOARD OVERVIEW
         ACADEMIC DEFENSE: This page contains ONLY:
         (a) Flash notification
         (b) Welcome/profile banner
         (c) Four metric stat cards
         (d) Reservation history table with color-coded status badges
         There is NO static Event Request Form here.
         Reservations are made exclusively via main.php modals.
         ================================================================ -->
    <main class="dash-content-wrapper" aria-label="User Dashboard">
        <div class="container">

            <!-- Flash Notification: shown after cancel action -->
            <?php if (!empty($actionMsg)): ?>
                <div class="main-flash-alert <?php echo $actionType === 'warning' ? 'flash-warning' : ''; ?>"
                     id="mainFlashAlert"
                     role="alert">
                    <span>
                        <?php echo $actionType === 'warning' ? '⚠️' : '✓'; ?>
                        <?php echo $actionMsg; ?>
                    </span>
                    <button type="button"
                            class="main-flash-dismiss"
                            id="mainFlashDismiss"
                            aria-label="Dismiss notification">✕</button>
                </div>
            <?php endif; ?>

            <!-- ── Welcome & Profile Banner ─────────────────────────── -->
            <div class="dash-welcome-banner">
                <div class="dash-welcome-left">
                    <div class="dash-welcome-avatar"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="dash-welcome-info">
                        <h1 class="dash-welcome-name">
                            Mabuhay, <?php echo htmlspecialchars($currentUser['fullname']); ?>!
                        </h1>
                        <div class="dash-welcome-meta">
                            <span class="dash-role-pill">🎓 <?php echo htmlspecialchars($currentUser['role']); ?></span>
                            <span class="dash-dept-text">🏛️ <?php echo htmlspecialchars($currentUser['department']); ?></span>
                            <span class="dash-dept-text">🆔 <?php echo htmlspecialchars($currentUser['id_number'] ?? 'KLD-00000'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="dash-welcome-right">
                    <!-- Primary CTA: takes user to the Facility Catalog to make a new booking -->
                    <a href="main.php" class="btn-dash-new-booking">
                        <span>+</span> New Reservation
                    </a>
                </div>
            </div>

            <!-- ── Four Metric Stat Cards ──────────────────────────── -->
            <div class="dash-metrics-grid" aria-label="Reservation statistics">

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Total Bookings</p>
                        <div class="dash-metric-value"><?php echo $totalCount; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-all" aria-hidden="true">📋</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Approved Passes</p>
                        <div class="dash-metric-value dash-val-approved"><?php echo $approvedCount; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-approved" aria-hidden="true">✓</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Pending Review</p>
                        <div class="dash-metric-value dash-val-pending"><?php echo $pendingCount; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-pending" aria-hidden="true">⏳</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Rejected / Cancelled</p>
                        <div class="dash-metric-value dash-val-rejected"><?php echo $rejectedCount; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-rejected" aria-hidden="true">✕</div>
                </div>

            </div><!-- /.dash-metrics-grid -->

            <!-- ── Reservation History Table ───────────────────────────
                 ACADEMIC DEFENSE: Color-coded badge legend:
                 - .badge-status.approved  → KLD Green (#006837)
                 - .badge-status.pending   → Soft Yellow (#f59e0b)
                 - .badge-status.rejected  → Crimson (#dc2626)
                 - .badge-status.completed → Slate (#475569)
                 The table filter pills hide/show rows client-side via JS
                 (data-status attribute matching), with no page reload.
                 ─────────────────────────────────────────────────────── -->
            <div class="dash-table-section" id="reservations-table">

                <!-- Table Header + Status Filter Pills -->
                <div class="dash-table-hdr">
                    <div>
                        <h2 class="dash-table-title">My Campus Reservations</h2>
                        <p class="dash-table-subtitle">Full history of your facility reservation requests.</p>
                    </div>
                    <div class="dash-filter-pills" role="group" aria-label="Filter by status">
                        <button type="button" class="dash-filter-pill active"
                                data-status-filter="all"
                                id="filterAll">
                            All (<?php echo $totalCount; ?>)
                        </button>
                        <button type="button" class="dash-filter-pill dash-pill-approved"
                                data-status-filter="Approved"
                                id="filterApproved">
                            Approved (<?php echo $approvedCount; ?>)
                        </button>
                        <button type="button" class="dash-filter-pill dash-pill-pending"
                                data-status-filter="Pending"
                                id="filterPending">
                            Pending (<?php echo $pendingCount; ?>)
                        </button>
                        <button type="button" class="dash-filter-pill dash-pill-rejected"
                                data-status-filter="Rejected"
                                id="filterRejected">
                            Rejected (<?php echo $rejectedCount; ?>)
                        </button>
                    </div>
                </div>

                <!-- Reservation Data Table -->
                <div class="dash-table-responsive">
                    <table class="dash-res-table" aria-label="Reservation history table">
                        <thead>
                            <tr>
                                <th scope="col">Booking ID</th>
                                <th scope="col">Facility</th>
                                <th scope="col">Date &amp; Time</th>
                                <th scope="col">Purpose / Event</th>
                                <th scope="col">Attendees</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reservations)): ?>
                                <!-- Empty state: no reservations in session yet -->
                                <tr class="dash-empty-row">
                                    <td colspan="7">
                                        <div class="dash-empty-state">
                                            <span class="dash-empty-icon" aria-hidden="true">📋</span>
                                            <p>No reservations submitted yet.</p>
                                            <a href="main.php" class="btn-dash-new-booking">
                                                + Make Your First Reservation
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservations as $res): ?>
                                    <?php
                                    // Map status to CSS badge modifier class
                                    $badgeClass = match($res['status']) {
                                        'Approved'  => 'approved',
                                        'Pending'   => 'pending',
                                        'Rejected'  => 'rejected',
                                        'Completed' => 'completed',
                                        default     => 'pending'
                                    };
                                    ?>
                                    <!-- Each row carries data-status for the JS filter to read -->
                                    <tr class="dash-res-row"
                                        data-status="<?php echo htmlspecialchars($res['status']); ?>">

                                        <td>
                                            <strong class="res-id-text">
                                                <?php echo htmlspecialchars($res['id']); ?>
                                            </strong>
                                            <div class="res-date-sub">
                                                Submitted: <?php echo htmlspecialchars($res['created_at']); ?>
                                            </div>
                                        </td>

                                        <td><?php echo htmlspecialchars($res['facility_name']); ?></td>

                                        <td>
                                            <span class="res-date-main">
                                                <?php echo htmlspecialchars($res['booking_date'] ?? $res['date'] ?? ''); ?>
                                            </span>
                                            <div class="res-date-sub">
                                                <?php echo htmlspecialchars($res['time_slot'] ?? ''); ?>
                                            </div>
                                        </td>

                                        <td class="res-purpose-cell">
                                            <?php echo htmlspecialchars($res['purpose'] ?? ''); ?>
                                        </td>

                                        <td><?php echo htmlspecialchars($res['attendees'] ?? ''); ?></td>

                                        <td>
                                            <!-- Color-coded status badge (KLD design standard) -->
                                            <span class="badge-status <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($res['status'] ?? ''); ?>
                                            </span>
                                        </td>

                                        <td class="res-actions-cell">
                                            <!-- View Slip button: opens detail modal via JS -->
                                            <button type="button"
                                                    class="btn-view-slip"
                                                    id="viewSlip_<?php echo htmlspecialchars($res['id'] ?? ''); ?>"
                                                    data-res-id="<?php echo htmlspecialchars($res['id'] ?? ''); ?>"
                                                    data-facility="<?php echo htmlspecialchars($res['facility_name'] ?? ''); ?>"
                                                    data-date="<?php echo htmlspecialchars($res['booking_date'] ?? $res['date'] ?? ''); ?>"
                                                    data-time="<?php echo htmlspecialchars($res['time_slot'] ?? ''); ?>"
                                                    data-purpose="<?php echo htmlspecialchars($res['purpose'] ?? ''); ?>"
                                                    data-status="<?php echo htmlspecialchars($res['status'] ?? ''); ?>"
                                                    data-applicant="<?php echo htmlspecialchars($res['applicant'] ?? ''); ?>"
                                                    data-dept="<?php echo htmlspecialchars($res['dept'] ?? ''); ?>"
                                                    data-attendees="<?php echo htmlspecialchars($res['attendees'] ?? ''); ?>">
                                                View Slip
                                            </button>

                                            <?php if ($res['status'] === 'Pending'): ?>
                                                <!-- Cancel form: POST action updates session status to "Rejected" -->
                                                <form method="POST" action="dashboard.php" class="inline-form">
                                                    <input type="hidden" name="action" value="cancel_reservation">
                                                    <input type="hidden"
                                                           name="res_id"
                                                           value="<?php echo htmlspecialchars($res['id']); ?>">
                                                    <button type="submit"
                                                            class="btn-cancel-res"
                                                            onclick="return confirm('Cancel reservation <?php echo htmlspecialchars($res['id']); ?>? This cannot be undone.');">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div><!-- /.dash-table-responsive -->

            </div><!-- /#reservations-table -->

        </div><!-- /.container -->
    </main>

    <!-- Shared Minimalist App Footer (no index.php links) -->
    <?php include 'includes/footer.php'; ?>

    <!-- ================================================================
         RESERVATION SLIP DETAIL MODAL
         ACADEMIC DEFENSE: Opened by the "View Slip" button in the table.
         JS reads the button's data-attributes and populates the table rows
         inside the modal — no additional server round-trip required.
         ================================================================ -->
    <div class="modal-backdrop"
         id="reservationSlipModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="slipModalTitle">
        <div class="main-modal-window">

            <div class="main-modal-hdr">
                <div class="main-modal-hdr-icon" aria-hidden="true">🎫</div>
                <div class="main-modal-hdr-text">
                    <h2 class="main-modal-hdr-title" id="slipModalTitle">KLD Official Reservation Slip</h2>
                    <p class="main-modal-hdr-sub">Read-only verification record for your booking request.</p>
                </div>
                <button type="button"
                        class="main-modal-close-x"
                        data-close-modal
                        aria-label="Close reservation slip">✕</button>
            </div>

            <div class="main-modal-body">
                <table class="main-summary-tbl" aria-label="Reservation slip details">
                    <tbody>
                        <tr>
                            <th scope="row">Pass Number</th>
                            <td id="slipResId">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Facility</th>
                            <td id="slipFacName">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Schedule</th>
                            <td id="slipDateTime">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Applicant</th>
                            <td id="slipApplicant">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Department / College</th>
                            <td id="slipDept">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Purpose / Event</th>
                            <td id="slipPurpose">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Expected Attendees</th>
                            <td id="slipAttendees">—</td>
                        </tr>
                        <tr>
                            <th scope="row">Current Status</th>
                            <td>
                                <span class="badge-status pending" id="slipStatusBadge">Pending</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="main-modal-ftr">
                <button type="button" class="btn-modal-dismiss-outline" data-close-modal>Close</button>
                <button type="button" class="btn-modal-review-submit" id="btnPrintSlip"
                        onclick="window.print()">
                    🖨️ Print Slip
                </button>
            </div>

        </div>
    </div><!-- /#reservationSlipModal -->

    <!-- Dashboard Scripts:
         - main_script.js: handles off-canvas sidebar open/close + modal controller
         - script.js:      handles table status filter pills + slip modal population -->
    <script src="js/main_script.js"></script>
    <script src="js/script.js"></script>

    <!-- Inline dashboard-specific JS: table filter pills + slip modal population -->
    <script>
    /**
     * DASHBOARD INLINE SCRIPT
     *
     * ACADEMIC DEFENSE:
     * 1. Table Filter Pills: Reads the 'data-status-filter' attribute of each
     *    clicked pill button. Loops through all .dash-res-row table rows and
     *    shows or hides each row by checking its 'data-status' attribute.
     *    No page reload — purely DOM manipulation.
     *
     * 2. Slip Modal Population: When "View Slip" is clicked, the handler reads
     *    every data-* attribute off the clicked button and injects the values
     *    into the corresponding <td> cells inside #reservationSlipModal.
     *    The badge class is dynamically set to match the reservation status.
     *
     * 3. Flash Alert Dismiss: Adds opacity transition before hiding the alert bar.
     */
    document.addEventListener('DOMContentLoaded', () => {

        // ── TABLE STATUS FILTER PILLS ──────────────────────────────────────
        const filterPills = document.querySelectorAll('.dash-filter-pill');
        const resRows     = document.querySelectorAll('.dash-res-row');

        filterPills.forEach(pill => {
            pill.addEventListener('click', () => {
                // Remove active class from all pills, set it on the clicked one
                filterPills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');

                const filterValue = pill.getAttribute('data-status-filter');

                // Show or hide each table row based on the filter value
                resRows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const shouldShow = (filterValue === 'all') || (rowStatus === filterValue);
                    row.style.display = shouldShow ? '' : 'none';
                });
            });
        });

        // ── RESERVATION SLIP MODAL POPULATION ─────────────────────────────
        const slipModal        = document.getElementById('reservationSlipModal');
        const viewSlipButtons  = document.querySelectorAll('.btn-view-slip');

        viewSlipButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // Read all data-attributes off the clicked button
                const resId     = btn.getAttribute('data-res-id')     || '—';
                const facility  = btn.getAttribute('data-facility')    || '—';
                const date      = btn.getAttribute('data-date')        || '—';
                const time      = btn.getAttribute('data-time')        || '—';
                const purpose   = btn.getAttribute('data-purpose')     || '—';
                const status    = btn.getAttribute('data-status')      || 'Pending';
                const applicant = btn.getAttribute('data-applicant')   || '—';
                const dept      = btn.getAttribute('data-dept')        || '—';
                const attendees = btn.getAttribute('data-attendees')   || '—';

                // Populate the slip modal cells
                const setText = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };

                setText('slipResId',     resId);
                setText('slipFacName',   facility);
                setText('slipDateTime',  `${date} | ${time}`);
                setText('slipApplicant', applicant);
                setText('slipDept',      dept);
                setText('slipPurpose',   purpose);
                setText('slipAttendees', attendees);

                // Set the status badge text and dynamic class
                const badge = document.getElementById('slipStatusBadge');
                if (badge) {
                    badge.textContent = status;
                    badge.className = 'badge-status';
                    // Map status to badge class
                    const classMap = {
                        'Approved' : 'approved',
                        'Pending'  : 'pending',
                        'Rejected' : 'rejected',
                        'Completed': 'completed'
                    };
                    badge.classList.add(classMap[status] || 'pending');
                }

                // Open the slip modal
                if (slipModal) {
                    slipModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        });

        // ── FLASH ALERT DISMISS ────────────────────────────────────────────
        const dismissBtn = document.getElementById('mainFlashDismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', () => {
                const flash = document.getElementById('mainFlashAlert');
                if (flash) {
                    flash.style.opacity = '0';
                    flash.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => { flash.style.display = 'none'; }, 300);
                }
            });
        }

    });
    </script>

</body>
</html>
