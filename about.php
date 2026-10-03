<?php
/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Page: About Us / Developers Portfolio (about.php)
 * 
 * ACADEMIC DEFENSE NOTES:
 * 1. Team Composition & Ordering: Strict numerically aligned 1-to-4 grid layout:
 *    - Position 1: Carl Vincent Pilapil    — Lead Full-Stack Developer
 *    - Position 2: Avril Denise Abonita   — System Analyst & Full-Stack Developer
 *    - Position 3: Miguel Andres Maranan  — Back-End / Database Developer
 *    - Position 4: Jimuel Abadiano        — Front-End Developer
 * 2. Visual Balance & Spacing: Includes generous top clearance for the 80px navbar,
 *    preventing any header clipping or awkward overlap.
 * 3. 2x2 Symmetrical Grid: Configured via modern CSS Grid for desktop screens
 *    and responsive single-column collapse on mobile devices.
 * 4. Strict Decoupling: Zero inline styling. Fully styled via css/about.css.
 */
require_once 'includes/auth_helper.php';
$activePage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Developers | Kolehiyo ng Lungsod ng Dasmariñas</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/about.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body data-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">

    <!-- Shared Header Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- About Us Hero Banner Section (Matches Landing Page Visual Branding) -->
    <section class="about-hero-section" style="background-image: url('assets/images/campus_bg.png');">
        <div class="about-hero-overlay">
            <h1 class="about-hero-title">ABOUT US</h1>
            <p class="about-hero-subtitle">
                Learn more about the Kolehiyo ng Lungsod ng Dasmariñas Facility Reservation System and the engineering team behind the platform.
            </p>
        </div>
    </section>

    <!-- Main Content: Centered Around 2x2 Developer Grid -->
    <main class="about-main-wrapper">
        <div class="container">
            
            <!-- Section Header Banner with Clear Spacing -->
            <div class="about-page-header">
                <span class="about-category-badge">INSTITUTE OF COMPUTING STUDIES</span>
                <h2 class="about-page-title">Meet the Development Team</h2>
                <p class="about-page-subtitle">
                    The KLD Facility Reservation System was engineered by student software developers to optimize campus venue management, resolve scheduling bottlenecks, and establish a modern, transparent booking workflow.
                </p>
            </div>

            <!-- Symmetrical 2x2 Developer Profile Grid (Strict 1 to 4 Order) -->
            <div class="dev-grid-2x2">

                <!-- Position 1: Miguel Andres Maranan — Lead Full-Stack Developer -->
                <div class="dev-profile-card">
                    <div class="dev-image-container">
                        <!-- .dev-photo-circle: square pass-through frame ensuring zero clipping -->
                        <div class="dev-photo-circle">
                            <img src="assets/images/developers/dev3.jpg" alt="Miguel Andres Maranan — Lead Full-Stack Developer" class="dev-photo" loading="lazy">
                        </div>
                        <span class="dev-role-chip">Lead Developer</span>
                    </div>
                    <div class="dev-card-info">
                        <h2 class="dev-name">Miguel Andres Maranan</h2>
                        <h3 class="dev-position">Lead Full-Stack Developer</h3>
                        <p class="dev-bio">
                            Oversees end-to-end system architecture, PHP session management, core component decoupling, and full-stack integration of all reservation workflows.
                        </p>

                        <!-- ============================================================
                             STUDENT DETAILS: EMAIL & STUDENT ID (EDIT MANUALLY HERE)
                             Replace placeholder values with the student's actual details:
                             - Email: Replace 'student@email.com'
                             - Student ID: Replace '202X-XXXXX'
                             ============================================================ -->
                        <div class="dev-meta-fields">
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Email:</span>
                                <!-- EDIT EMAIL BELOW -->
                                <span class="dev-meta-value">student@email.com</span>
                            </div>
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Student ID:</span>
                                <!-- EDIT STUDENT ID BELOW -->
                                <span class="dev-meta-value">202X-XXXXX</span>
                            </div>
                        </div>
                        <!-- END STUDENT DETAILS -->

                        <div class="dev-skills-list">
                            <span class="skill-tag">Full-Stack PHP</span>
                            <span class="skill-tag">System Architecture</span>
                            <span class="skill-tag">Security &amp; Auth</span>
                        </div>
                    </div>
                </div>

                <!-- Position 2: Avril Denise Abonita — System Analyst & Full-Stack Developer -->
                <div class="dev-profile-card">
                    <div class="dev-image-container">
                        <div class="dev-photo-circle">
                            <img src="assets/images/developers/dev2.jpg" alt="Avril Denise Abonita — System Analyst and Full-Stack Developer" class="dev-photo" loading="lazy">
                        </div>
                        <span class="dev-role-chip">Systems Analyst</span>
                    </div>
                    <div class="dev-card-info">
                        <h2 class="dev-name">Avril Denise Abonita</h2>
                        <h3 class="dev-position">System Analyst &amp; Full-Stack Developer</h3>
                        <p class="dev-bio">
                            Analyzes institutional workflows, defines system requirements, and bridges frontend and backend development to ensure cohesive system behavior.
                        </p>

                        <!-- ============================================================
                             STUDENT DETAILS: EMAIL & STUDENT ID (EDIT MANUALLY HERE)
                             Replace placeholder values with the student's actual details:
                             - Email: Replace 'student@email.com'
                             - Student ID: Replace '202X-XXXXX'
                             ============================================================ -->
                        <div class="dev-meta-fields">
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Email:</span>
                                <!-- EDIT EMAIL BELOW -->
                                <span class="dev-meta-value">student@email.com</span>
                            </div>
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Student ID:</span>
                                <!-- EDIT STUDENT ID BELOW -->
                                <span class="dev-meta-value">202X-XXXXX</span>
                            </div>
                        </div>
                        <!-- END STUDENT DETAILS -->

                        <div class="dev-skills-list">
                            <span class="skill-tag">Systems Analysis</span>
                            <span class="skill-tag">Full-Stack Dev</span>
                            <span class="skill-tag">Project Management</span>
                        </div>
                    </div>
                </div>

                <!-- Position 3: Carl Vincent Pilapil — Back-End / Database Developer -->
                <div class="dev-profile-card">
                    <div class="dev-image-container">
                        <div class="dev-photo-circle">
                            <img src="assets/images/developers/dev4.jpg" alt="Carl Vincent Pilapil — Back-End and Database Developer" class="dev-photo" loading="lazy">
                        </div>
                        <span class="dev-role-chip">Back-End Lead</span>
                    </div>
                    <div class="dev-card-info">
                        <h2 class="dev-name">Carl Vincent Pilapil</h2>
                        <h3 class="dev-position">Back-End / Database Developer</h3>
                        <p class="dev-bio">
                            Designs relational database schemas, implements scheduling conflict detection algorithms, and manages server-side data validation and clearance logic.
                        </p>

                        <!-- ============================================================
                             STUDENT DETAILS: EMAIL & STUDENT ID (EDIT MANUALLY HERE)
                             Replace placeholder values with the student's actual details:
                             - Email: Replace 'student@email.com'
                             - Student ID: Replace '202X-XXXXX'
                             ============================================================ -->
                        <div class="dev-meta-fields">
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Email:</span>
                                <!-- EDIT EMAIL BELOW -->
                                <span class="dev-meta-value">student@email.com</span>
                            </div>
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Student ID:</span>
                                <!-- EDIT STUDENT ID BELOW -->
                                <span class="dev-meta-value">202X-XXXXX</span>
                            </div>
                        </div>
                        <!-- END STUDENT DETAILS -->

                        <div class="dev-skills-list">
                            <span class="skill-tag">Database Design</span>
                            <span class="skill-tag">Conflict Engine</span>
                            <span class="skill-tag">Data Validation</span>
                        </div>
                    </div>
                </div>

                <!-- Position 4: Jimuel Abadiano — Front-End Developer -->
                <div class="dev-profile-card">
                    <div class="dev-image-container">
                        <div class="dev-photo-circle">
                            <img src="assets/images/developers/dev1.jpg" alt="Jimuel Abadiano — Front-End Developer" class="dev-photo" loading="lazy">
                        </div>
                        <span class="dev-role-chip">Front-End Dev</span>
                    </div>
                    <div class="dev-card-info">
                        <h2 class="dev-name">Jimuel Abadiano</h2>
                        <h3 class="dev-position">Front-End Developer</h3>
                        <p class="dev-bio">
                            Crafts the responsive UI, interactive navigation modals, dynamic form verification workflows, and the KLD Green visual design system across all pages.
                        </p>

                        <!-- ============================================================
                             STUDENT DETAILS: EMAIL & STUDENT ID (EDIT MANUALLY HERE)
                             Replace placeholder values with the student's actual details:
                             - Email: Replace 'student@email.com'
                             - Student ID: Replace '202X-XXXXX'
                             ============================================================ -->
                        <div class="dev-meta-fields">
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Email:</span>
                                <!-- EDIT EMAIL BELOW -->
                                <span class="dev-meta-value">student@email.com</span>
                            </div>
                            <div class="dev-meta-item">
                                <span class="dev-meta-label">Student ID:</span>
                                <!-- EDIT STUDENT ID BELOW -->
                                <span class="dev-meta-value">202X-XXXXX</span>
                            </div>
                        </div>
                        <!-- END STUDENT DETAILS -->

                        <div class="dev-skills-list">
                            <span class="skill-tag">Vanilla JavaScript</span>
                            <span class="skill-tag">Responsive CSS3</span>
                            <span class="skill-tag">UI/UX Design</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- Shared Minimalist Modern App Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Application Script -->
    <script src="js/main.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
