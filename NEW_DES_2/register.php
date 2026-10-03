<?php
require_once 'includes/auth_helper.php';
$activePage = 'register';

$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $msg = "Security token expired. Please try again.";
        $msgType = "error";
    } else {
        $role            = $_POST['role'] ?? 'Student';
        $idNumber        = trim($_POST['id_number'] ?? '');
        $fullName        = trim($_POST['fullname'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $contact         = trim($_POST['contact'] ?? '');
        $department      = trim($_POST['department'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Whitelist role — never accept Admin from POST
        $allowedRoles = ['Student', 'Faculty', 'Student Organization'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Student';
        }

        $termsAgreed = isset($_POST['terms']);
        $nameLen = mb_strlen($fullName);

        if (!$termsAgreed) {
            $msg = "You must agree to the Terms of Service and Data Privacy Policy.";
            $msgType = "error";
        } elseif (empty($idNumber) || !preg_match('/^[A-Za-z0-9\-]{4,30}$/', $idNumber)) {
            $msg = "Please enter a valid KLD ID number (4-30 alphanumeric characters or hyphens).";
            $msgType = "error";
        } elseif ($nameLen < 2 || $nameLen > 150) {
            $msg = "Full name must be between 2 and 150 characters.";
            $msgType = "error";
        } elseif (!isKldEmail($email)) {
            $msg = "Only @kld.edu.ph institutional email addresses are allowed.";
            $msgType = "error";
        } elseif (empty($contact) || !preg_match('/^[0-9+\-\s]{7,20}$/', $contact)) {
            $msg = "Please enter a valid contact number (7-20 digits).";
            $msgType = "error";
        } elseif ($password !== $confirmPassword) {
            $msg = "Passwords do not match. Please ensure both fields are identical.";
            $msgType = "error";
        } elseif (!isStrongPassword($password)) {
            $msg = "Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.";
            $msgType = "error";
        } else {
            // Register user in database with hashed password
            $result = registerUser([
                'id_number'  => $idNumber,
                'fullname'   => $fullName,
                'email'      => $email,
                'contact'    => $contact,
                'department' => $department,
                'role'       => $role,
                'password'   => $password
            ]);

            if ($result['success']) {
                $newUserId = (int) $result['user']['id'];
                $_SESSION['pending_verify_user_id'] = $newUserId;
                $_SESSION['pending_verify_at'] = time();
                unset(
                    $_SESSION['pending_login_user_id'],
                    $_SESSION['pending_login_at'],
                    $_SESSION['pending_login_redirect']
                );

                require_once __DIR__ . '/includes/mailer.php';
                $code = createOtp($newUserId, 'signup');
                $subject = "Verify Your KLD Facility Portal Account";
                $htmlBody = "<p>Your KLD Facility Reservation verification code is: <strong>" . htmlspecialchars($code) . "</strong></p>"
                    . "<p>This code expires in 10 minutes.</p>"
                    . "<p>If you did not request this, ignore this email.</p>";

                $sent = sendMail($email, $fullName, $subject, $htmlBody);
                if ($sent) {
                    auditLog($newUserId, 'otp_sent');
                    header("Location: verify.php");
                } else {
                    header("Location: verify.php?mail_error=1");
                }
                exit;
            } else {
                $msg = $result['message'];
                $msgType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Registration & Password Verification | KLD Facility Portal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="auth-page-body">

    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php">
                <img src="assets/images/kld_logo.png" alt="KLD Logo" class="auth-logo">
            </a>
            <h2>Create an Account</h2>
            <p>Kolehiyo ng Lungsod ng Dasmariñas — Facility Portal</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.92rem; text-align: center; <?php echo $msgType === 'success' ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

            <!-- ID & Full Name -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="idNumber">KLD ID / Student No.</label>
                    <input type="text" id="idNumber" name="id_number" class="form-control" placeholder="e.g. 2024-10942" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Juan Dela Cruz" required>
                </div>
            </div>

            <!-- Email & Contact -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Institutional Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="juan.delacruz@kld.edu.ph" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact" class="form-control" placeholder="0917-123-4567" required>
                </div>
            </div>


            <!-- Password & Confirm Password with Real-time "Check Password" -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create strong password" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" aria-label="Toggle password visibility" onclick="togglePasswordVisibility('password', this)" title="Toggle password visibility">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat password" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" aria-label="Toggle confirm password visibility" onclick="togglePasswordVisibility('confirm_password', this)" title="Toggle password visibility">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Real-time Password Strength Meter -->
            <div class="password-strength-container">
                <div class="strength-bar-track">
                    <div class="strength-bar-fill" id="passwordStrengthBar"></div>
                </div>
                <div class="strength-text-row">
                    <span class="strength-label">Password Strength:</span>
                    <span class="strength-value" id="passwordStrengthText">None</span>
                </div>
            </div>

            <!-- Real-time Password Requirements Checklist -->
            <div class="password-requirements-card">
                <div class="password-requirements-title">Password Security Requirements (Check Password)</div>
                <ul class="password-rules-list">
                    <li class="rule-item rule-unmet" id="ruleLength"><span class="rule-icon"></span> Minimum 8 characters</li>
                    <li class="rule-item rule-unmet" id="ruleUpper"><span class="rule-icon"></span> Uppercase letter (A-Z)</li>
                    <li class="rule-item rule-unmet" id="ruleLower"><span class="rule-icon"></span> Lowercase letter (a-z)</li>
                    <li class="rule-item rule-unmet" id="ruleNumber"><span class="rule-icon"></span> Number (0-9)</li>
                    <li class="rule-item rule-unmet" id="ruleSpecial"><span class="rule-icon"></span> Symbol (!@#$%^&*)</li>
                </ul>
                <div id="passwordMatchFeedback" class="password-match-feedback"></div>
            </div>

            <!-- Terms & Privacy Agreement -->
            <div class="form-check">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    I agree to the <button type="button" class="tos-link-btn" id="openTosModalBtn" aria-haspopup="dialog">Terms of Service</button> and acknowledge the <a href="privacy.php" target="_blank">Data Privacy Policy</a> of Kolehiyo ng Lungsod ng Dasmariñas.
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-auth-submit" id="btnRegisterSubmit">
                Complete Registration & Open Dashboard →
            </button>
        </form>

        <div class="auth-footer-links">
            Already have an account? <a href="login.php">Sign In here</a>
            <br>
            <a href="index.php" class="back-to-home">← Back to Main Landing Page</a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/main.js"></script>

    <!-- ==========================================
         TERMS OF SERVICE MODAL
         ========================================== -->
    <div class="tos-modal-backdrop" id="tosModal" role="dialog" aria-modal="true" aria-labelledby="tosModalTitle" style="display:none;">
        <div class="tos-modal-window">
            <div class="tos-modal-header">
                <h3 id="tosModalTitle">Terms of Service</h3>
                <button type="button" class="tos-modal-close" id="closeTosModalBtn" aria-label="Close Terms of Service modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="tos-modal-meta">
                <span><strong>Effective Date:</strong> September 15, 2026</span>
                <span><strong>Applicability:</strong> All Campus Venues &amp; Laboratories</span>
            </div>
            <div class="tos-modal-body">
                <h4>1. Acceptance of Terms &amp; Eligibility</h4>
                <p>By creating an account, accessing, or submitting a reservation request through the <strong>Kolehiyo ng Lungsod ng Dasmariñas (KLD) Facility Reservation System</strong>, you agree to be bound by these Terms of Service, university student handbooks, faculty manuals, and all applicable Philippine laws.</p>
                <p>Eligibility to book campus facilities is extended exclusively to:</p>
                <ul>
                    <li>Currently enrolled KLD undergraduate and diploma students.</li>
                    <li>Active full-time and part-time faculty members and academic departments.</li>
                    <li>Accredited Recognized Student Organizations (RSOs) with faculty adviser endorsement.</li>
                    <li>University administrative offices and authorized local government partners.</li>
                </ul>

                <h4>2. User Account Security &amp; Accountability</h4>
                <p>Users are responsible for maintaining the strict confidentiality of their portal credentials (ID Number, Institutional Email, and Password). Any reservation submitted through your authenticated account shall be deemed authorized and endorsed by you.</p>
                <div class="tos-callout"><strong>Security Advisory:</strong> Sharing account passwords or using credentials of another student or faculty member is considered an offense under the KLD Student Code of Conduct.</div>

                <h4>3. Booking Procedure &amp; Minimum Lead Times</h4>
                <p>All reservation requests must adhere to the following mandatory lead times:</p>
                <ul>
                    <li><strong>Standard Lecture Halls, Classrooms &amp; Study Commons:</strong> Minimum of three (3) working days.</li>
                    <li><strong>Computer, Science &amp; Clinical Simulation Laboratories:</strong> Minimum of five (5) working days with lab custodian concurrence.</li>
                    <li><strong>KLD Gymnasium, Main Auditorium &amp; Audio-Visual Rooms:</strong> Minimum of seven (7) to ten (10) working days.</li>
                </ul>

                <h4>4. Official Printable Reservation Slips</h4>
                <p>Upon approval, an <strong>Official Reservation Slip</strong> with a unique tracking code is generated on the applicant's User Dashboard. The applicant must present a printed or digital copy to the Campus Safety &amp; Security Office and the facility custodian upon entering the venue.</p>

                <h4>5. Cancellation, Rescheduling &amp; No-Show Policies</h4>
                <p>If a scheduled activity cannot proceed, the applicant must cancel the booking at least <strong>twenty-four (24) hours</strong> before the reserved time slot. Failing to cancel and not occupying the reserved venue (No-Show) more than twice in a single academic semester may result in a thirty (30) day suspension of facility reservation privileges.</p>

                <h4>6. Venue Care, Cleanliness &amp; Code of Conduct</h4>
                <p>Users are expected to uphold the university standard of <strong>Clean As You Go (CLAYGO)</strong>. At the conclusion of any reservation, all furniture and equipment must be returned to default arrangement, and all waste must be properly disposed of.</p>

                <h4>7. Equipment Handling &amp; Liability for Damages</h4>
                <p>The applicant and organizing department assume full responsibility for any loss, breakage, or physical damage to university property caused by negligence or misuse during the reservation window. Repair or replacement costs will be assessed by the KLD General Services Office.</p>

                <h4>8. Prohibited Items &amp; Activities</h4>
                <ul>
                    <li>Open flames, pyrotechnics, or hazardous substances without written approval from the Fire Safety Officer.</li>
                    <li>Alcoholic beverages, vape devices, e-cigarettes, and illegal substances.</li>
                    <li>Attaching adhesives, nails, or paint to walls, projector screens, or acoustic panels.</li>
                    <li>Unauthorized commercial merchandising, non-academic soliciting, or gambling.</li>
                </ul>

                <h4>9. Violations, Penalties &amp; System Disclaimers</h4>
                <p>KLD reserves the right to immediately cancel or revoke any approved reservation in the event of unforeseen university emergencies, institutional calendar priority events, or flagrant violations of these terms. Disciplinary action will be handled in accordance with the Student Discipline Board.</p>
            </div>
            <div class="tos-modal-footer">
                <button type="button" class="tos-btn-close-footer" id="closeTosModalBtnFooter">Close</button>
                <button type="button" class="tos-btn-agree" id="agreeTosBtn">I Understand &amp; Agree</button>
            </div>
        </div>
    </div>

    <script>
        // Terms of Service Modal
        const tosModal = document.getElementById('tosModal');
        const openTosBtn = document.getElementById('openTosModalBtn');
        const closeTosBtn = document.getElementById('closeTosModalBtn');
        const closeTosFooterBtn = document.getElementById('closeTosModalBtnFooter');
        const agreeTosBtn = document.getElementById('agreeTosBtn');
        const termsCheckbox = document.getElementById('terms');

        function openTosModal() {
            tosModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            closeTosBtn.focus();
        }

        function closeTosModal() {
            tosModal.style.display = 'none';
            document.body.style.overflow = '';
        }

        if (openTosBtn) openTosBtn.addEventListener('click', openTosModal);
        if (closeTosBtn) closeTosBtn.addEventListener('click', closeTosModal);
        if (closeTosFooterBtn) closeTosFooterBtn.addEventListener('click', closeTosModal);
        if (agreeTosBtn) agreeTosBtn.addEventListener('click', function() {
            if (termsCheckbox) termsCheckbox.checked = true;
            closeTosModal();
        });

        // Close on backdrop click
        tosModal.addEventListener('click', function(e) {
            if (e.target === tosModal) closeTosModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && tosModal.style.display === 'flex') closeTosModal();
        });
    </script>
</body>
</html>
