<?php
/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * PAGE: Email OTP Verification
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. Verification state is isolated to $_SESSION['pending_verify_user_id']
 *    until code is verified; the user has no authenticated session.
 * 2. OTP hash is verified via password_verify() with attempts rate-limiting.
 * 3. 60-second cooldown is enforced via SQL comparison before resending.
 */
require_once 'includes/auth_helper.php';
require_once 'includes/mailer.php';

// If only pending_login_user_id exists in a session, unset it and redirect to login.php
if (empty($_SESSION['pending_verify_user_id'])) {
    unset(
        $_SESSION['pending_login_user_id'],
        $_SESSION['pending_login_at'],
        $_SESSION['pending_login_redirect']
    );
    header("Location: login.php");
    exit;
}

// Clean up any lingering login session keys
unset(
    $_SESSION['pending_login_user_id'],
    $_SESSION['pending_login_at'],
    $_SESSION['pending_login_redirect']
);

$purpose = 'signup';
$pendingUserId = (int)$_SESSION['pending_verify_user_id'];

// Enforce 10-minute expiry on pending signup
if (empty($_SESSION['pending_verify_at']) || (time() - $_SESSION['pending_verify_at'] > 600)) {
    unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_at']);
    header("Location: login.php?session_expired=1");
    exit;
}

$pendingUser = dbFetchOne("SELECT * FROM `users` WHERE `id` = ? LIMIT 1", 'i', [$pendingUserId]);

if (!$pendingUser || $pendingUser['archived_at'] !== null) {
    unset(
        $_SESSION['pending_verify_user_id'],
        $_SESSION['pending_verify_at']
    );
    header("Location: login.php");
    exit;
}

// If already verified for signup purpose, clear pending key and redirect
if ((int)$pendingUser['email_verified'] === 1) {
    unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_at']);
    header("Location: login.php?verified=1");
    exit;
}

// Mask institutional email address (e.g. j***@kld.edu.ph)
$userEmail = $pendingUser['email'];
$atPos = strrpos($userEmail, '@');
$userPart = substr($userEmail, 0, $atPos);
$domainPart = substr($userEmail, $atPos);
$maskedEmail = substr($userPart, 0, 1) . '***' . $domainPart;

$msg = "";
$msgType = "";

if (isset($_GET['mail_error'])) {
    $msg = "We couldn't send the code. Use Resend.";
    $msgType = "error";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $msg = "Security token expired. Please try again.";
        $msgType = "error";
    } else {
        $action = $_POST['action'] ?? 'verify';

        if ($action === 'verify') {
            $otpCode = trim($_POST['otp_code'] ?? '');
            if (empty($otpCode) || !preg_match('/^[0-9]{6}$/', $otpCode)) {
                $msg = "Please enter a valid 6-digit verification code.";
                $msgType = "error";
            } else {
                if (verifyOtp($pendingUserId, 'signup', $otpCode)) {
                    dbExecute("UPDATE `users` SET `email_verified` = 1 WHERE `id` = ?", 'i', [$pendingUserId]);
                    auditLog($pendingUserId, 'otp_verified');
                    unset($_SESSION['pending_verify_user_id'], $_SESSION['pending_verify_at']);
                    header("Location: login.php?verified=1");
                    exit;
                } else {
                    auditLog($pendingUserId, 'otp_failed');
                    $msg = "Invalid or expired verification code. Please try again.";
                    $msgType = "error";
                }
            }
        } elseif ($action === 'resend') {
            if (isOtpCapped($pendingUserId, 'signup')) {
                $msg = "Too many code requests. Please try again later.";
                $msgType = "error";
            } elseif (!canResendOtp($pendingUserId, 'signup')) {
                $wait = getOtpResendWait($pendingUserId, 'signup');
                $msg = "Please wait before requesting a new code. ({$wait}s remaining)";
                $msgType = "error";
            } else {
                $code = createOtp($pendingUserId, 'signup');
                $subject = "Verify Your KLD Facility Portal Account";
                $htmlBody = "<p>Your KLD Facility Reservation verification code is: <strong>" . htmlspecialchars($code) . "</strong></p>"
                    . "<p>This code expires in 10 minutes.</p>"
                    . "<p>If you did not request this, ignore this email.</p>";

                $sent = sendMail($pendingUser['email'], $pendingUser['fullname'], $subject, $htmlBody);
                if ($sent) {
                    auditLog($pendingUserId, 'otp_sent');
                    $msg = "A new verification code has been sent to your email.";
                    $msgType = "success";
                } else {
                    error_log("Failed to send signup OTP to user ID {$pendingUserId}");
                    $msg = "We couldn't send the code. Use Resend.";
                    $msgType = "error";
                }
            }
        }
    }
}

$isCapped = isOtpCapped($pendingUserId, 'signup');
if ($isCapped && empty($msg)) {
    $msg = "Too many code requests. Please try again later.";
    $msgType = "error";
}

$resendWait = getOtpResendWait($pendingUserId, 'signup');
$canResend = ($resendWait === 0 && !$isCapped);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | KLD Facility Reservation System</title>
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

    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php">
                <img src="assets/images/kld_logo.png" alt="KLD Logo" class="auth-logo">
            </a>
            <h2>Verify Your Email</h2>
            <p>Enter the 6-digit verification code sent to <?php echo htmlspecialchars($maskedEmail); ?></p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="auth-alert <?php echo htmlspecialchars($msgType); ?>" role="alert">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form action="verify.php" method="POST" id="verifyForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="verify">

            <div class="form-group">
                <label class="form-label" for="otp_code">6-Digit Verification Code</label>
                <input type="text" id="otp_code" name="otp_code" class="form-control otp-input-control" placeholder="••••••" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autocomplete="one-time-code" autofocus>
            </div>

            <div class="auth-btn-actions">
                <button type="submit" class="btn-auth-compact btn-auth-primary" id="btnVerifySubmit">
                    Verify Account →
                </button>
            </div>
        </form>

        <form action="verify.php" method="POST" id="resendForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="resend">

            <div class="auth-btn-actions">
                <button type="submit" class="btn-auth-compact btn-auth-secondary" id="btnResendSubmit" data-resend-wait="<?php echo (int) $resendWait; ?>" data-label="Resend verification code" <?php echo !$canResend ? 'disabled' : ''; ?>>
                    <?php echo !$canResend ? 'Resend code in ' . $resendWait . 's' : 'Resend verification code'; ?>
                </button>
            </div>
        </form>

        <div class="auth-footer-links">
            <a href="login.php">Back to login</a>
        </div>
    </div>

    <!-- Verification Script: Auto-focus, digits-only filter, countdown timer -->
    <script src="js/verify.js"></script>
</body>
</html>
