/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * VERIFY OTP INTERACTION & RESEND COUNTDOWN (js/verify.js)
 * ==========================================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    // Auto-focus and numeric-only filtering for OTP code input
    const otpInput = document.getElementById('otp_code');
    if (otpInput) {
        otpInput.focus();
        otpInput.addEventListener('input', (e) => {
            // Strip any non-digit character and cap at 6 digits
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
        });
    }

    // Resend Cooldown Countdown Timer
    const resendBtn = document.getElementById('btnResendSubmit');
    if (!resendBtn) return;

    let remaining = parseInt(resendBtn.dataset.resendWait, 10);
    if (isNaN(remaining) || remaining < 0) {
        remaining = 0;
    }

    const defaultLabel = resendBtn.dataset.label || 'Resend verification code';

    if (remaining > 0) {
        resendBtn.disabled = true;
        resendBtn.textContent = `Resend code in ${remaining}s`;

        const countdownInterval = setInterval(() => {
            remaining--;
            if (remaining > 0) {
                resendBtn.textContent = `Resend code in ${remaining}s`;
            } else {
                clearInterval(countdownInterval);
                resendBtn.disabled = false;
                resendBtn.textContent = defaultLabel;
                resendBtn.dataset.resendWait = '0';
            }
        }, 1000);
    } else {
        resendBtn.disabled = false;
        resendBtn.textContent = defaultLabel;
    }
});
