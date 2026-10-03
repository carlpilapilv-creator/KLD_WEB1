<?php
require_once 'includes/auth_helper.php';
require_once 'includes/mailer.php';
$activePage = 'login';

// Handle AJAX requests from wizard (send_otp, verify_otp, reset_password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!csrf_verify()) {
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please reload the page.']);
        exit;
    }

    $action = $_POST['action'];

    // ─── STEP 1: Send OTP to Institutional Email ────────────────────────────
    if ($action === 'send_otp') {
        $identifier = trim($_POST['identifier'] ?? '');
        if (empty($identifier)) {
            echo json_encode(['success' => false, 'message' => 'Please provide your KLD Institutional Email or ID.']);
            exit;
        }

        // Clear any previous verified state whenever a new code is requested
        unset($_SESSION['reset_verified_user_id'], $_SESSION['reset_verified_at']);

        $user = dbFetchOne(
            "SELECT `id`, `fullname`, `email`, `archived_at` FROM `users` WHERE `email` = ? OR `id_number` = ? LIMIT 1",
            'ss',
            [$identifier, $identifier]
        );

        if ($user && $user['archived_at'] === null) {
            $_SESSION['reset_user_id'] = (int)$user['id'];
            auditLog((int)$user['id'], 'password_reset_requested');

            // Only actually send when canResendOtp allows it
            if (canResendOtp((int)$user['id'], 'reset')) {
                $code = createOtp((int)$user['id'], 'reset');
                $subject = "KLD Facility Portal - Password Reset Code";
                $htmlBody = "<p>Your password reset code is: <strong>" . htmlspecialchars($code) . "</strong></p>"
                    . "<p>This code expires in 10 minutes.</p>"
                    . "<p>If you did not request this, ignore this email.</p>";

                $sent = sendMail($user['email'], $user['fullname'], $subject, $htmlBody);
                if ($sent) {
                    auditLog((int)$user['id'], 'otp_sent');
                } else {
                    error_log("Failed to send password reset email to user ID " . $user['id']);
                }
            }
        } else {
            $_SESSION['reset_user_id'] = 0;
        }

        // Masked email: never derive from DB.
        // If input contains no "@", return "your registered KLD email".
        // If it contains "@", mask only the typed value the same way for existing and non-existing accounts.
        $atPos = strrpos($identifier, '@');
        if ($atPos === false) {
            $masked = 'your registered KLD email';
        } else {
            $userPart = substr($identifier, 0, $atPos);
            $domainPart = substr($identifier, $atPos);
            $masked = substr($userPart, 0, 1) . '***' . $domainPart;
        }

        // Random delay to mask timing differences
        usleep(random_int(150000, 400000));

        // Generic success response whether account exists or not
        echo json_encode([
            'success' => true,
            'message' => 'If that account exists, a code was sent.',
            'masked_email' => $masked,
            'resend_wait' => 60
        ]);
        exit;
    }

    // ─── STEP 2: Verify 6-Digit OTP ─────────────────────────────────────────
    if ($action === 'verify_otp') {
        if (!isset($_SESSION['reset_user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Session expired or invalid. Please request a new code.']);
            exit;
        }

        $userId = (int)$_SESSION['reset_user_id'];
        $otpCode = trim($_POST['otp_code'] ?? '');

        if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
            usleep(random_int(150000, 400000));
            echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
            exit;
        }

        if ($userId > 0 && verifyOtp($userId, 'reset', $otpCode)) {
            auditLog($userId, 'otp_verified');
            $_SESSION['reset_verified_user_id'] = $userId;
            $_SESSION['reset_verified_at'] = time();
            usleep(random_int(150000, 400000));
            echo json_encode(['success' => true]);
        } else {
            if ($userId > 0) {
                auditLog($userId, 'otp_failed');
            }
            usleep(random_int(150000, 400000));
            echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code.']);
        }
        exit;
    }

    // ─── STEP 3: Update Password ────────────────────────────────────────────
    if ($action === 'reset_password') {
        if (empty($_SESSION['reset_verified_user_id']) || empty($_SESSION['reset_verified_at'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please verify your OTP code first.']);
            exit;
        }

        // Enforce 10-minute expiry on verified reset state
        if (time() - $_SESSION['reset_verified_at'] > 600) {
            unset($_SESSION['reset_verified_user_id'], $_SESSION['reset_verified_at'], $_SESSION['reset_user_id']);
            echo json_encode(['success' => false, 'message' => 'Password reset session expired. Please start over.']);
            exit;
        }

        $userId = (int)$_SESSION['reset_verified_user_id'];
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match. Please ensure both fields are identical.']);
            exit;
        }

        if (!isStrongPassword($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.']);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $ok = dbExecute("UPDATE `users` SET `password_hash` = ? WHERE `id` = ?", 'si', [$hash, $userId]);

        if ($ok !== false) {
            // Mark all unused 'reset' otp rows for that user used=1 (UPDATE, no DELETE)
            dbExecute("UPDATE `otp_codes` SET `used` = 1 WHERE `user_id` = ? AND `purpose` = 'reset' AND `used` = 0", 'i', [$userId]);

            // INSERT a row into password_resets with used=1
            $token = bin2hex(random_bytes(16));
            dbExecute(
                "INSERT INTO `password_resets` (`user_id`, `token`, `expires_at`, `used`) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), 1)",
                'is',
                [$userId, $token]
            );

            auditLog($userId, 'password_reset');

            unset(
                $_SESSION['reset_verified_user_id'],
                $_SESSION['reset_verified_at'],
                $_SESSION['reset_user_id']
            );

            echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
        } else {
            error_log("Failed to update password for user ID {$userId}");
            echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password & Account Recovery | KLD Facility Reservation</title>
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

    <div class="auth-card" style="max-width: 480px;">
        
        <!-- Header -->
        <div class="auth-header">
            <a href="index.php">
                <img src="assets/images/kld_logo.png" alt="KLD Logo" class="auth-logo">
            </a>
            <h2>Account Recovery</h2>
            <p>Reset your KLD Facility Portal password</p>
        </div>

        <!-- Wizard Progress Indicator -->
        <div class="forgot-step-indicator">
            <div class="step-dot active" id="stepDot1">1</div>
            <div class="step-line" id="stepLine1"></div>
            <div class="step-dot" id="stepDot2">2</div>
            <div class="step-line" id="stepLine2"></div>
            <div class="step-dot" id="stepDot3">3</div>
        </div>

        <!-- STEP 1: Enter Institutional Email / ID -->
        <div id="forgotCardStep1">
            <p style="font-size: 0.9rem; color: var(--text-muted); text-align: center; margin-bottom: 18px;">
                Enter your registered KLD Institutional Email address or Student/Employee ID to receive a secure password reset code.
            </p>

            <form id="forgotStep1Form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="form-group">
                    <label class="form-label" for="forgotEmail">KLD Email</label>
                    <input type="text" id="forgotEmail" class="form-control" placeholder="juan.delacruz@kld.edu.ph" required>
                </div>

                <button type="submit" class="btn-auth-submit">
                    Send Verification Code →
                </button>
            </form>
        </div>

        <!-- STEP 2: Enter Verification OTP Code -->
        <div id="forgotCardStep2" style="display: none;">
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 0.88rem; color: #166534; text-align: center;">
                A 6-digit verification code has been dispatched to <strong id="otpTargetEmail">juan.delacruz@kld.edu.ph</strong>.
            </div>

            <form id="forgotStep2Form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="form-group">
                    <label class="form-label" for="otpCode">Enter 6-Digit OTP Code</label>
                    <input type="text" id="otpCode" class="form-control" placeholder="• • • • • •" maxlength="6" style="text-align: center; font-size: 1.4rem; letter-spacing: 6px; font-weight: 700; font-family: monospace;" required>
                </div>

                <div style="text-align: center; margin-bottom: 18px; font-size: 0.82rem; color: var(--text-muted);">
                    Didn't receive the code? <a href="#" id="resendOtpBtn" style="color: var(--kld-green-primary); font-weight: 600;">Resend OTP</a>
                </div>

                <button type="submit" class="btn-auth-submit">
                    Verify Code & Continue →
                </button>
            </form>
        </div>

        <!-- STEP 3: Create New Password with Live Checker -->
        <div id="forgotCardStep3" style="display: none;">
            <p style="font-size: 0.9rem; color: var(--text-muted); text-align: center; margin-bottom: 18px;">
                Create a new secure password for your KLD Facility Portal account.
            </p>

            <form id="forgotStep3Form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="password" class="form-control" placeholder="Enter new password" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" aria-label="Toggle password visibility" onclick="togglePasswordVisibility('password', this)" title="Toggle password visibility">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <div class="input-with-icon">
                        <input type="password" id="confirm_password" class="form-control" placeholder="Repeat new password" required autocomplete="new-password">
                        <button type="button" class="btn-toggle-password" aria-label="Toggle confirm password visibility" onclick="togglePasswordVisibility('confirm_password', this)" title="Toggle password visibility">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Password Strength Progress Meter -->
                <div class="password-strength-container">
                    <div class="strength-bar-track">
                        <div class="strength-bar-fill" id="passwordStrengthBar"></div>
                    </div>
                    <div class="strength-text-row">
                        <span class="strength-label">Password Strength:</span>
                        <span class="strength-value" id="passwordStrengthText">None</span>
                    </div>
                </div>

                <!-- Live Checklist -->
                <div class="password-requirements-card">
                    <div class="password-requirements-title">Security Checklist</div>
                    <ul class="password-rules-list">
                        <li class="rule-item rule-unmet" id="ruleLength"><span class="rule-icon"></span> 8+ characters</li>
                        <li class="rule-item rule-unmet" id="ruleUpper"><span class="rule-icon"></span> Uppercase (A-Z)</li>
                        <li class="rule-item rule-unmet" id="ruleLower"><span class="rule-icon"></span> Lowercase (a-z)</li>
                        <li class="rule-item rule-unmet" id="ruleNumber"><span class="rule-icon"></span> Number (0-9)</li>
                        <li class="rule-item rule-unmet" id="ruleSpecial"><span class="rule-icon"></span> Symbol (!@#$)</li>
                    </ul>
                    <div id="passwordMatchFeedback" class="password-match-feedback"></div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    Reset & Update Password
                </button>
            </form>
        </div>

        <!-- STEP 4: Success Message -->
        <div id="forgotCardStep4" style="display: none; text-align: center; padding: 20px 0;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
                ✓
            </div>
            <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--kld-green-dark); margin-bottom: 8px;">
                Password Reset Successfully!
            </h3>
            <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 24px;">
                Your new password has been updated. You can now log in securely to access your facility reservations.
            </p>
            <a href="login.php?reset_success=1" class="btn-auth-submit" style="display: block; text-decoration: none;">
                Proceed to Log In →
            </a>
        </div>

        <div class="auth-footer-links">
            Remembered your credentials? <a href="login.php">Sign In</a>
            <br>
            <a href="index.php" class="back-to-home">← Back to Main Landing Page</a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/main.js"></script>
</body>
</html>
