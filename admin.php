<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * PAGE: Administrator Review — Pending Requests (admin.php)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. ACCESS CONTROL: Protected by requireLogin() and isPrivilegedUser(). Any
 *    unauthorized attempt triggers an audit log and redirects to main.php.
 * 2. SEPARATION OF DUTIES: Database-level checks prevent administrators from
 *    reviewing or approving their own reservation requests.
 * 3. TRANSACTION INTEGRITY: Every review operation executes inside a MySQL
 *    transaction with facility row-locking and real-time conflict re-checks.
 * 4. COMPLETE CODE DECOUPLING: Zero inline CSS and zero inline JavaScript.
 *    Reuses shared stylesheet classes and components.
 */
require_once 'includes/auth_helper.php';
require_once 'includes/icons.php';

// STEP 1: Authenticate user
requireLogin();

// STEP 2: Authorization gate — strictly privileged users only
$currentUser = getCurrentUser();
$userId = (int)($currentUser['id'] ?? 0);

if (!isPrivilegedUser($currentUser)) {
    setFlash('error', 'You do not have permission to access the administration page.');
    auditLog($userId, 'admin_page:denied');
    header('Location: main.php');
    exit;
}

// STEP 3: Handle Review Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Guard 1: Verify CSRF token
    if (!csrf_verify()) {
        auditLog($userId, $action . ':csrf');
        setFlash('error', 'Invalid security token. Please try again.');
        header('Location: admin.php');
        exit;
    }

    // Guard 2: Privilege re-check from database
    $adminUser = dbFetchOne(
        "SELECT `id`, `role`, `access_level`, `archived_at` FROM `users` WHERE `id` = ? LIMIT 1",
        'i',
        [$userId]
    );
    if (!$adminUser || !empty($adminUser['archived_at']) || !isPrivilegedUser($adminUser)) {
        setFlash('error', 'You do not have permission to review reservations.');
        auditLog($userId, $action . ':denied');
        header('Location: admin.php');
        exit;
    }

    // Guard 3: Validate reservation ID format
    $resId = trim((string)($_POST['res_id'] ?? ''));
    if (!preg_match('/^KLD-RES-\d{4}-\d{3,}$/', $resId)) {
        setFlash('error', 'Invalid reservation ID.');
        auditLog($userId, $action . ':refused');
        header('Location: admin.php');
        exit;
    }

    // Action 1: Approve reservation
    if ($action === 'approve_reservation') {
        $result = approveReservation($resId, $userId);
        if ($result['success']) {
            setFlash('success', $result['message']);
            auditLog($userId, 'reservation_approved:' . $resId);
        } else {
            setFlash('error', $result['message']);
            auditLog($userId, 'approve_reservation:refused');
        }
        header('Location: admin.php');
        exit;
    }

    // Action 2: Reject reservation
    if ($action === 'reject_reservation') {
        $note = trim((string)($_POST['review_note'] ?? ''));
        $result = rejectReservation($resId, $userId, $note);
        if ($result['success']) {
            setFlash('success', $result['message']);
            auditLog($userId, 'reservation_rejected_by_admin:' . $resId);
        } else {
            setFlash('error', $result['message']);
            auditLog($userId, 'reject_reservation:refused');
        }
        header('Location: admin.php');
        exit;
    }

    // Fallback for unrecognized action
    setFlash('error', 'Invalid action requested.');
    header('Location: admin.php');
    exit;
}

// STEP 4: Retrieve flash messages and data for the page
$flash      = getFlash();
$actionMsg  = $flash['text'] ?? '';
$actionType = $flash['type'] ?? '';

$pendingRequests  = getPendingReservations();
$stats            = getReservationStats();
$recentlyReviewed = getRecentlyReviewed();

// STEP 5: Build avatar initials (same pattern as dashboard.php)
$nameParts = explode(' ', trim($currentUser['fullname']));
$initials  = strtoupper(
    substr($nameParts[0], 0, 1) .
    (isset($nameParts[count($nameParts) - 1]) ? substr($nameParts[count($nameParts) - 1], 0, 1) : '')
);
$firstName = htmlspecialchars($nameParts[0]);

// STEP 6: Pre-compute "time passed" flag for each pending request
$nowTs = time();
foreach ($pendingRequests as &$req) {
    $parts = explode('-', $req['time_slot'] ?? '');
    $startStr = trim($parts[0] ?? '');
    $startTs = (!empty($startStr) && !empty($req['booking_date']))
        ? strtotime($req['booking_date'] . ' ' . $startStr)
        : false;
    $req['_time_passed'] = ($startTs === false || $startTs <= $nowTs);
}
unset($req);

$activePage = 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Review | Kolehiyo ng Lungsod ng Dasmariñas</title>
    <meta name="description" content="KLD Facility Reservation System — Admin Review of Reservation Requests.">
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
    <!-- Dashboard styles (reuse topbar, sidebar, metric cards, badges) -->
    <link rel="stylesheet" href="css/dashboard_style.css">
    <!-- Admin-specific styles -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="dash-page-body">

    <!-- ================================================================
         OFF-CANVAS BACKDROP & NAVIGATION SIDEBAR
         ACADEMIC DEFENSE: Identical component to dashboard.php.
         ================================================================ -->

    <!-- Dark backdrop: clicking closes the sidebar -->
    <div class="main-offcanvas-backdrop" id="mainOffcanvasBackdrop" aria-hidden="true"></div>

    <!-- Slide-Out Navigation Sidebar -->
    <aside class="main-offcanvas-sidebar"
           id="mainOffcanvasSidebar"
           aria-label="Application Navigation Menu"
           aria-hidden="true">

        <!-- Account Header Block -->
        <div class="sidebar-account-header-block">
            <p class="sidebar-acct-label">Account</p>

            <div class="sidebar-user-row">
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

            <button type="button"
                    class="sidebar-close-x-btn"
                    id="mainSidebarCloseBtn"
                    aria-label="Close navigation menu"><?php echo icon('x', 18); ?></button>
        </div>

        <!-- Navigation Pill Links -->
        <nav class="sidebar-nav-body" aria-label="Application navigation">

            <p class="sidebar-nav-section-label">Navigation</p>

            <a href="main.php" class="sidebar-nav-pill">
                <?php echo icon('landmark', 18); ?>
                <span>Facility Catalog</span>
            </a>

            <a href="dashboard.php" class="sidebar-nav-pill">
                <?php echo icon('bar-chart-2', 18); ?>
                <span>Dashboard</span>
            </a>

            <a href="admin.php" class="sidebar-nav-pill active-pill" aria-current="page">
                <?php echo icon('clipboard-list', 18); ?>
                <span>Admin Review</span>
            </a>

            <hr class="sidebar-divider-hr" aria-hidden="true">
            <p class="sidebar-nav-section-label">Settings</p>

            <a href="dashboard.php#account-settings" class="sidebar-nav-pill">
                <?php echo icon('settings', 18); ?>
                <span>Account Settings</span>
            </a>

            <hr class="sidebar-divider-hr" aria-hidden="true">

            <a href="logout.php" class="sidebar-logout-pill">
                <?php echo icon('log-out', 18); ?>
                <span>Log Out</span>
            </a>

        </nav>
    </aside>

    <!-- ================================================================
         TOP APPLICATION BAR (Matches dashboard.php — no public navbar)
         ================================================================ -->
    <div class="main-topbar" role="banner">

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

        <div class="main-topbar-brand">
            <img src="assets/images/kld_logo.png"
                 alt="KLD University Seal"
                 class="main-topbar-logo">
            <span class="main-topbar-name">KOLEHIYO NG LUNGSOD NG DASMARIÑAS</span>
            <span class="main-topbar-pagetag">Admin Review</span>
        </div>

        <div class="main-topbar-user-pill"
             aria-label="Logged in as <?php echo htmlspecialchars($currentUser['fullname']); ?>">
            <div class="main-topbar-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <span class="main-topbar-username"><?php echo $firstName; ?></span>
        </div>

    </div>

    <!-- ================================================================
         MAIN CONTENT — ADMIN REVIEW DASHBOARD
         ================================================================ -->
    <main class="dash-content-wrapper" aria-label="Admin Review Dashboard">
        <div class="container">

            <!-- Flash Notification: shown after approve/reject action -->
            <?php if (!empty($actionMsg)): ?>
                <?php
                $flashClass = '';
                $flashIcon  = '✓';
                if ($actionType === 'error') {
                    $flashClass = 'flash-error';
                    $flashIcon  = '⚠️';
                } elseif ($actionType === 'warning') {
                    $flashClass = 'flash-warning';
                    $flashIcon  = '⚠️';
                }
                ?>
                <div class="main-flash-alert <?php echo $flashClass; ?>"
                     id="mainFlashAlert"
                     role="alert">
                    <span>
                        <?php echo $flashIcon; ?>
                        <?php echo htmlspecialchars($actionMsg); ?>
                    </span>
                    <button type="button"
                            class="main-flash-dismiss"
                            id="mainFlashDismiss"
                            aria-label="Dismiss notification">✕</button>
                </div>
            <?php endif; ?>

            <!-- ── (3a) Page Header Banner ─────────────────────────── -->
            <div class="admin-header-banner">
                <div class="admin-header-info">
                    <h1 class="admin-header-title">Admin Review</h1>
                    <p class="admin-header-subtitle">Review, approve, or reject campus facility booking requests.</p>
                </div>
                <div class="admin-header-actions">
                    <a href="main.php" class="admin-header-link">
                        <?php echo icon('landmark', 16); ?> Book a Facility
                    </a>
                    <a href="dashboard.php" class="admin-header-link">
                        <?php echo icon('bar-chart-2', 16); ?> My Reservations
                    </a>
                </div>
            </div>

            <!-- ── (3b) Four Metric Stat Cards ──────────────────────── -->
            <div class="dash-metrics-grid" aria-label="Admin statistics">

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Pending</p>
                        <div class="dash-metric-value dash-val-pending"><?php echo $stats['pending']; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-pending" aria-hidden="true">⏳</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Approved This Month</p>
                        <div class="dash-metric-value dash-val-approved"><?php echo $stats['approved_month']; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-approved" aria-hidden="true">✓</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Rejected This Month</p>
                        <div class="dash-metric-value dash-val-rejected"><?php echo $stats['rejected_month']; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-rejected" aria-hidden="true">✕</div>
                </div>

                <div class="dash-metric-card">
                    <div class="dash-metric-info">
                        <p class="dash-metric-label">Facilities</p>
                        <div class="dash-metric-value"><?php echo $stats['facilities']; ?></div>
                    </div>
                    <div class="dash-metric-icon dash-icon-all" aria-hidden="true">🏛️</div>
                </div>

            </div><!-- /.dash-metrics-grid -->

            <!-- ── (3c) Pending Requests Section ─────────────────────── -->
            <div class="dash-table-section">
                <div class="dash-table-hdr">
                    <div>
                        <h2 class="dash-table-title">Pending Requests</h2>
                        <p class="dash-table-subtitle"><?php echo count($pendingRequests); ?> request(s) awaiting review.</p>
                    </div>
                </div>

                <?php if (empty($pendingRequests)): ?>
                    <div class="dash-empty-state admin-empty-pad">
                        <span class="dash-empty-icon" aria-hidden="true">✓</span>
                        <p>No pending requests. You're all caught up.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-cards-grid admin-cards-pad">
                        <?php foreach ($pendingRequests as $res): ?>
                            <article class="admin-request-card">
                                <!-- Row 1: ID + Requester -->
                                <div class="admin-card-row">
                                    <span class="admin-card-id"><?php echo htmlspecialchars($res['id']); ?></span>
                                    <span class="admin-card-requester"><?php echo htmlspecialchars($res['requester_name']); ?></span>
                                </div>

                                <!-- Row 2: Facility, Date, Time -->
                                <div class="admin-card-details">
                                    <strong>Facility:</strong> <?php echo htmlspecialchars($res['facility_name']); ?><br>
                                    <strong>Date:</strong> <?php echo htmlspecialchars($res['booking_date']); ?> &bull;
                                    <strong>Time:</strong> <?php echo htmlspecialchars($res['time_slot']); ?>
                                </div>

                                <!-- Row 3: Purpose + Event -->
                                <div class="admin-card-details">
                                    <?php if (!empty($res['event_name'])): ?>
                                        <strong>Event:</strong> <?php echo htmlspecialchars($res['event_name']); ?><br>
                                    <?php endif; ?>
                                    <strong>Purpose:</strong> <?php echo htmlspecialchars($res['purpose'] ?: 'None specified'); ?>
                                </div>

                                <!-- Row 4: Attendees -->
                                <div class="admin-card-details">
                                    <strong>Attendees:</strong> <?php echo htmlspecialchars((string)$res['attendees']); ?>
                                </div>

                                <!-- Row 5: Time-passed badge (if applicable) -->
                                <?php if (!empty($res['_time_passed'])): ?>
                                    <div>
                                        <span class="admin-badge-expired">⏱ Time Passed</span>
                                    </div>
                                <?php endif; ?>

                                <!-- Row 6: Action forms (Reject + Approve side by side) -->
                                <div class="admin-card-actions">
                                    <!-- Reject form with optional reason -->
                                    <div class="admin-reject-group">
                                        <form method="POST" action="admin.php" class="inline-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="reject_reservation">
                                            <input type="hidden" name="res_id" value="<?php echo htmlspecialchars($res['id']); ?>">
                                            <input type="text"
                                                   name="review_note"
                                                   id="review_note_<?php echo htmlspecialchars($res['id']); ?>"
                                                   class="admin-reject-input"
                                                   maxlength="255"
                                                   placeholder="Reason (optional)">
                                            <button type="submit" class="admin-btn-reject">Reject</button>
                                        </form>
                                    </div>

                                    <!-- Approve form -->
                                    <form method="POST" action="admin.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="approve_reservation">
                                        <input type="hidden" name="res_id" value="<?php echo htmlspecialchars($res['id']); ?>">
                                        <?php if (!empty($res['_time_passed'])): ?>
                                            <button type="submit"
                                                    class="admin-btn-approve"
                                                    disabled
                                                    title="Time passed, can only be rejected">Approve</button>
                                        <?php else: ?>
                                            <button type="submit" class="admin-btn-approve">Approve</button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><!-- /.dash-table-section (pending) -->

            <!-- ── (3d) Recently Reviewed Section ─────────────────────── -->
            <div class="dash-table-section admin-section-gap">
                <div class="dash-table-hdr">
                    <div>
                        <h2 class="dash-table-title">Recently Reviewed</h2>
                        <p class="dash-table-subtitle">Last 5 approved or rejected reservations.</p>
                    </div>
                </div>

                <?php if (empty($recentlyReviewed)): ?>
                    <div class="dash-empty-state admin-empty-pad">
                        <span class="dash-empty-icon" aria-hidden="true">📋</span>
                        <p>No reviewed reservations yet.</p>
                    </div>
                <?php else: ?>
                    <div class="admin-reviewed-list">
                        <?php foreach ($recentlyReviewed as $rev): ?>
                            <?php
                            $badgeClass = match($rev['status']) {
                                'Approved'  => 'approved',
                                'Rejected'  => 'rejected',
                                default     => 'pending'
                            };
                            ?>
                            <div class="admin-reviewed-item">
                                <div class="admin-reviewed-left">
                                    <span class="admin-reviewed-id"><?php echo htmlspecialchars($rev['id']); ?></span>
                                    <span class="badge-status <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($rev['status']); ?></span>
                                    <span class="admin-reviewed-facility"><?php echo htmlspecialchars($rev['facility_name']); ?></span>
                                </div>
                                <span class="admin-reviewed-date">
                                    <?php echo htmlspecialchars($rev['reviewed_at'] ? date('M j, Y g:i A', strtotime($rev['reviewed_at'])) : '—'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div><!-- /.dash-table-section (reviewed) -->

        </div><!-- /.container -->
    </main>

    <!-- Shared Minimalist App Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts: main_script.js handles off-canvas sidebar open/close -->
    <script src="js/main_script.js"></script>

    <!-- Admin-specific inline-free JS for flash dismiss -->
    <script src="js/script.js"></script>

</body>
</html>
