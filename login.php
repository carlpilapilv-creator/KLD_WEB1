<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * PAGE: Institutional Login — DATABASE-BACKED Authentication
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. Authentication is validated against the `users` table using bcrypt.
 * 2. CSRF token protects the login form.
 * 3. On success, session_regenerate_id(true) prevents session fixation.
 * 4. Redirect target validated via safeRedirect() — local .php paths only.
 * 5. Audit logs written on login_success and login_failed.
 */
require_once 'includes/auth_helper.php';
$activePage = 'login';

$msg = "";
$msgType = "";

// Check if already logged in
if (isLoggedIn() && !isset($_GET['reauth'])) {
    header('Location: main.php');
    exit;
}

// Flash messages
if (isset($_GET['logged_out'])) {
    $msg = "You have been successfully logged out from the KLD Facility Reservation Portal.";
    $msgType = "success";
} elseif (isset($_GET['verified'])) {
    $msg = "Email verified successfully! You can now log in to your account.";
    $msgType = "success";
} elseif (isset($_GET['reset_success'])) {
    $msg = "Password reset successfully! You can now log in with your new password.";
    $msgType = "success";
} elseif (isset($_GET['registered'])) {
    $msg = "Registration successful! Welcome to KLD. Please log in to your account.";
    $msgType = "success";
} elseif (isset($_GET['session_expired'])) {
    $msg = "Your session has expired due to inactivity. Please log in again.";
    $msgType = "error";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $msg = "Security token expired. Please try again.";
        $msgType = "error";
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!empty($identifier) && !empty($password)) {
            $identHash = hash('sha256', strtolower($identifier));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // Check failure count: same identifier_hash >= 5 OR same IP >= 25 in last 15 mins
            $identFailures = dbFetchOne(
                "SELECT COUNT(*) AS `cnt` FROM `login_attempts`
                 WHERE `identifier_hash` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                's',
                [$identHash]
            );

            $ipFailures = dbFetchOne(
                "SELECT COUNT(*) AS `cnt` FROM `login_attempts`
                 WHERE `ip_address` = ? AND `created_at` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)",
                's',
                [$ipAddress]
            );

            $isThrottled = ($identFailures && (int)$identFailures['cnt'] >= 5)
                || ($ipFailures && (int)$ipFailures['cnt'] >= 25);

            if ($isThrottled) {
                auditLog(null, 'login_throttled');
                $msg = "Too many attempts. Try again in 15 minutes.";
                $msgType = "error";
            } else {
                $user = authenticateUser($identifier, $password);
                if ($user) {
                    // Clear that identifier's failed attempts on successful authentication
                    dbExecute("DELETE FROM `login_attempts` WHERE `identifier_hash` = ?", 's', [$identHash]);

                    // Intercept unverified accounts before creating session
                    if (isset($user['email_verified']) && (int)$user['email_verified'] === 0) {
                        $_SESSION['pending_verify_user_id'] = (int)$user['id'];
                        $_SESSION['pending_verify_at'] = time();
                        unset(
                            $_SESSION['pending_login_user_id'],
                            $_SESSION['pending_login_at'],
                            $_SESSION['pending_login_redirect']
                        );

                        if (canResendOtp((int)$user['id'], 'signup')) {
                            require_once __DIR__ . '/includes/mailer.php';
                            $code = createOtp((int)$user['id'], 'signup');
                            $subject = "Verify Your KLD Facility Portal Account";
                            $htmlBody = "<p>Your KLD Facility Reservation verification code is: <strong>" . htmlspecialchars($code) . "</strong></p>"
                                . "<p>This code expires in 10 minutes.</p>"
                                . "<p>If you did not request this, ignore this email.</p>";
                            $sent = sendMail($user['email'], $user['fullname'], $subject, $htmlBody);
                            if ($sent) {
                                auditLog((int)$user['id'], 'otp_sent');
                            }
                        }

                        header("Location: verify.php");
                        exit;
                    }

                    // Direct login for verified accounts
                    unset(
                        $_SESSION['pending_verify_user_id'],
                        $_SESSION['pending_verify_at'],
                        $_SESSION['pending_login_user_id'],
                        $_SESSION['pending_login_at'],
                        $_SESSION['pending_login_redirect']
                    );
                    session_regenerate_id(true);
                    unset($user['password_hash']);
                    $_SESSION['user'] = $user;
                    $_SESSION['last_activity'] = time();
                    auditLog((int)$user['id'], 'login_success');

                    header("Location: " . safeRedirect($_GET['redirect'] ?? ''));
                    exit;
                } else {
                    // Record failed attempt
                    dbExecute(
                        "INSERT INTO `login_attempts` (`identifier_hash`, `ip_address`) VALUES (?, ?)",
                        'ss',
                        [$identHash, $ipAddress]
                    );

                    // Clean up login_attempts rows older than 1 day
                    dbExecute("DELETE FROM `login_attempts` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 1 DAY)");

                    // Audit log (user_id unknown on failure)
                    auditLog(null, 'login_failed');

                    $msg = "Invalid credentials. Please check your KLD Email/ID and password.";
                    $msgType = "error";
                }
            }
        } else {
            $msg = "Please enter your KLD ID / Institutional Email and password.";
            $msgType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | KLD Facility Reservation System</title>
    <!-- Google Fonts: Plus Jakarta Sans + Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="auth-page-body">

    <div class="auth-card" style="max-width: 460px;">
        <div class="auth-header">
            <a href="index.php">
                <img src="assets/images/kld_logo.png" alt="KLD Logo" class="auth-logo">
            </a>
            <h2>Welcome Back</h2>
            <p>Sign in to your KLD Facility Portal account</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="auth-alert <?php echo $msgType; ?>" role="alert">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo !empty($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : ''; ?>" method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">

            <div class="form-group">
                <label class="form-label" for="identifier">KLD Email or ID Number</label>
                <input type="text" id="identifier" name="identifier" class="form-control" placeholder="juan.delacruz@kld.edu.ph" required autocomplete="username" value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                    <a href="forgot-password.php" style="font-size: 0.82rem; color: var(--kld-green-primary); font-weight: 600;">Forgot password?</a>
                </div>
                <div class="input-with-icon">
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="btn-toggle-password" id="togglePasswordBtn" aria-label="Toggle password visibility" onclick="togglePasswordVisibility('password', this)" title="Show password">
                        <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
            </div>

            <div class="form-check" style="justify-content: flex-end;">
                <a href="faq.php" style="font-size: 0.82rem; color: var(--text-muted);">Need help?</a>
            </div>

            <button type="submit" class="btn-auth-submit">
                Sign In to Portal →
            </button>
        </form>

        <div class="auth-footer-links">
            Don't have an account yet? <a href="register.php">Create an Account</a>
            <br>
            <a href="index.php" class="back-to-home">← Back to Main Page</a>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/main.js"></script>
</body>
</html>
