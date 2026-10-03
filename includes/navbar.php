<?php
/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Component: Shared Header Navigation Bar
 * 
 * ACADEMIC DEFENSE NOTES:
 * 1. Role & Flow Separation: Public landing and informational pages display standard
 *    institutional navigation. The off-canvas navigation hamburger button is placed
 *    exclusively for authenticated dashboard workflows ($activePage === 'dashboard').
 * 2. Strict Decoupling: Zero inline styling. CSS classes (.nav-user-pill, .nav-logout-btn)
 *    are styled cleanly in navbar.css and style.css.
 * 3. Session Handling: Evaluates user authentication state via auth_helper.php.
 */

if (!isset($activePage)) {
    $activePage = 'home';
}

require_once __DIR__ . '/icons.php';

$userLoggedIn = isLoggedIn();
$currentUser = getCurrentUser();
?>
<header class="header-navbar">
    <div class="container nav-container">
        <!-- Off-Canvas Toggle: Exclusively rendered on authenticated Dashboard/Reservation module -->
        <?php if ($activePage === 'dashboard'): ?>
            <button type="button" class="offcanvas-trigger-btn" id="offcanvasToggleBtn" aria-label="Open Navigation Sidebar" title="Open Navigation Menu">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
        <?php endif; ?>

        <!-- Brand / University Identity -->
        <a href="index.php" class="brand-section">
            <img src="assets/images/kld_logo.png" alt="KLD University Seal" class="school-logo">
            <span class="school-name">KOLEHIYO NG LUNGSOD NG DASMARIÑAS</span>
        </a>

        <!-- Main Desktop Navigation Links -->
        <ul class="nav-links" id="mainNavLinks">
            <li><a href="index.php" class="nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a></li>
            <li><a href="about.php" class="nav-link <?php echo $activePage === 'about' ? 'active' : ''; ?>">About Us</a></li>
            <li><a href="faq.php" class="nav-link <?php echo $activePage === 'faq' ? 'active' : ''; ?>">FAQs</a></li>
        </ul>

        <!-- User Authentication & Profile Quick-Action Area -->
        <div class="nav-auth">
            <?php if ($userLoggedIn): ?>
                <div class="nav-auth-group">
                    <a href="main.php" class="nav-user-pill" title="Access Facility Portal">
                        <?php echo icon('user', 17); ?>
                        <span class="nav-user-name"><?php echo htmlspecialchars(explode(' ', $currentUser['fullname'])[0]); ?></span>
                    </a>
                    <a href="logout.php" class="nav-logout-btn" title="Sign Out of Portal">
                        <?php echo icon('log-out', 17); ?>
                        <span class="nav-logout-text">Log Out</span>
                    </a>
                </div>
            <?php else: ?>
                <a href="login.php" class="auth-btn-link <?php echo in_array($activePage, ['login', 'register']) ? 'active' : ''; ?>">
                    Login / Register
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Navigation Menu Toggle -->
        <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle navigation links">
            <?php echo icon('menu', 22); ?>
        </button>
    </div>
</header>

