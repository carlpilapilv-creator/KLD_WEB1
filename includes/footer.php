<?php
/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Component: Minimalist Modern App Footer
 * 
 * ACADEMIC DEFENSE NOTES:
 * 1. Minimalist App Footer: Replaces heavy, bulky multi-column web footers with a
 *    sleek, modern app-style bar optimized for web application workflows.
 * 2. Visual Hierarchy: Groups identity branding on the left, quick legal/utility links
 *    in the center, and live operational status indicator on the right.
 * 3. Accessibility & Semantics: Implements valid HTML5 <footer>, <nav>, and aria-labels.
 * 4. Zero Inline Styles: All layout, typography, and color rules are maintained in footer.css.
 */
?>
<footer class="app-minimal-footer">
    <div class="container app-footer-container">
        <!-- Left: Brand Identity & Copyright -->
        <div class="app-footer-brand">
            <img src="assets/images/kld_logo.png" alt="KLD Seal" class="app-footer-logo">
            <div class="app-footer-text-group">
                <span class="app-footer-title">Kolehiyo ng Lungsod ng Dasmariñas</span>
                <span class="app-footer-copy">&copy; <?php echo date('Y'); ?> Facility Reservation System &bull; All Rights Reserved</span>
            </div>
        </div>

        <!-- Center: Subtle Utility Navigation -->
        <nav class="app-footer-nav" aria-label="Footer utility links">
            <a href="about.php" class="app-footer-link">About Developers</a>
            <span class="app-footer-divider">|</span>
            <a href="faq.php" class="app-footer-link">FAQs</a>
            <span class="app-footer-divider">|</span>
            <a href="privacy.php" class="app-footer-link">Privacy Policy</a>
            <span class="app-footer-divider">|</span>
            <a href="terms.php" class="app-footer-link">Terms of Service</a>
        </nav>

        <!-- Right: Real-time System Status Indicator -->
        <div class="app-footer-status">
            <span class="status-pulse-dot" aria-hidden="true"></span>
            <span class="status-label-text">System Operational</span>
        </div>
    </div>
</footer>
