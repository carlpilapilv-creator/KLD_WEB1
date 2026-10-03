/**
 * Kolehiyo ng Lungsod ng Dasmariñas (KLD) - Facility Reservation System
 * Interactive Logic, Modals, Filtering, Password Checker, Dashboard & FAQs
 */

document.addEventListener('DOMContentLoaded', () => {
    try { initNavbarScroll(); } catch(e) { console.warn('Navbar scroll init:', e); }
    try { initMobileMenu(); } catch(e) { console.warn('Mobile menu init:', e); }
    try { initModals(); } catch(e) { console.warn('Modals init:', e); }
    try { initFiltersAndSearch(); } catch(e) { console.warn('Filters init:', e); }
    try { initFaqSystem(); } catch(e) { console.warn('FAQ init:', e); }
    try { initSmoothScroll(); } catch(e) { console.warn('Smooth scroll init:', e); }
    try { initPasswordChecker(); } catch(e) { console.warn('Password checker init:', e); }
    try { initPasswordToggles(); } catch(e) { console.warn('Password toggles init:', e); }
    try { initDashboardInteractions(); } catch(e) { console.warn('Dashboard init:', e); }
    try { initForgotPasswordWizard(); } catch(e) { console.warn('Forgot password init:', e); }
});

/* ==========================================================================
   1. TRANSPARENT NAVBAR SCROLL BEHAVIOR & MOBILE MENU
   ========================================================================== */
function initNavbarScroll() {
    const navbar = document.querySelector('.header-navbar');
    if (!navbar) return;

    function handleScroll() {
        if (window.scrollY > 40) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
}

function initMobileMenu() {
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('mainNavLinks');

    if (toggleBtn && navLinks) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.classList.toggle('mobile-open');
            toggleBtn.innerHTML = navLinks.classList.contains('mobile-open') ? '✕' : '☰';
        });
    }
}

/* ==========================================================================
   2. PASSWORD CHECKER & STRENGTH METER (Real-time Validation)
   ========================================================================== */
function initPasswordChecker() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    const matchFeedback = document.getElementById('passwordMatchFeedback');

    if (!passwordInput) return;

    // Rules Elements
    const ruleLength = document.getElementById('ruleLength');
    const ruleUpper = document.getElementById('ruleUpper');
    const ruleLower = document.getElementById('ruleLower');
    const ruleNumber = document.getElementById('ruleNumber');
    const ruleSpecial = document.getElementById('ruleSpecial');

    function evaluatePassword() {
        const val = passwordInput.value || '';

        // Check individual rules
        const hasLength = val.length >= 8;
        const hasUpper = /[A-Z]/.test(val);
        const hasLower = /[a-z]/.test(val);
        const hasNumber = /[0-9]/.test(val);
        const hasSpecial = /[^A-Za-z0-9]/.test(val);

        if (ruleLength) updateRule(ruleLength, hasLength);
        if (ruleUpper) updateRule(ruleUpper, hasUpper);
        if (ruleLower) updateRule(ruleLower, hasLower);
        if (ruleNumber) updateRule(ruleNumber, hasNumber);
        if (ruleSpecial) updateRule(ruleSpecial, hasSpecial);

        // Calculate score
        let score = 0;
        if (hasLength) score++;
        if (val.length >= 12) score++;
        if (hasUpper) score++;
        if (hasLower) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        if (strengthBar && strengthText) {
            strengthBar.className = 'strength-bar-fill';
            strengthText.className = 'strength-value';

            if (val.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'None';
                strengthText.className = 'strength-value';
            } else if (score <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak';
                strengthText.classList.add('weak');
            } else if (score <= 4) {
                strengthBar.classList.add('strength-fair');
                strengthText.textContent = 'Fair';
                strengthText.classList.add('fair');
            } else if (score <= 5) {
                strengthBar.classList.add('strength-good');
                strengthText.textContent = 'Good';
                strengthText.classList.add('good');
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong';
                strengthText.classList.add('strong');
            }
        }

        checkPasswordMatch();
    }

    function checkPasswordMatch() {
        if (!confirmInput || !matchFeedback) return;
        const pVal = passwordInput.value || '';
        const cVal = confirmInput.value || '';

        if (cVal.length === 0) {
            matchFeedback.className = 'password-match-feedback';
            matchFeedback.textContent = '';
            matchFeedback.style.display = 'none';
            confirmInput.classList.remove('is-valid', 'is-invalid');
        } else if (pVal === cVal) {
            matchFeedback.className = 'password-match-feedback matched';
            matchFeedback.textContent = '✓ Passwords match successfully';
            matchFeedback.style.display = 'block';
            confirmInput.classList.add('is-valid');
            confirmInput.classList.remove('is-invalid');
        } else {
            matchFeedback.className = 'password-match-feedback mismatched';
            matchFeedback.textContent = '✕ Passwords do not match yet';
            matchFeedback.style.display = 'block';
            confirmInput.classList.add('is-invalid');
            confirmInput.classList.remove('is-valid');
        }
    }

    function updateRule(element, isMet) {
        if (isMet) {
            element.classList.remove('rule-unmet');
            element.classList.add('rule-met');
        } else {
            element.classList.remove('rule-met');
            element.classList.add('rule-unmet');
        }
    }

    // Attach listeners
    ['input', 'keyup', 'change', 'paste', 'focus'].forEach(evt => {
        passwordInput.addEventListener(evt, evaluatePassword);
        if (confirmInput) {
            confirmInput.addEventListener(evt, checkPasswordMatch);
        }
    });

    // Run initial evaluation
    evaluatePassword();
}

/* Toggle Password Visibility (Eye Button / SVG icon-eye + icon-eye-off pair) */
function togglePasswordVisibility(targetId, btnElement) {
    let input = targetId ? document.getElementById(targetId) : null;

    // Fallback: find input sibling inside the same wrapper
    if (!input && btnElement) {
        const wrapper = btnElement.closest('.input-with-icon');
        if (wrapper) {
            input = wrapper.querySelector('input[type="password"], input[type="text"]');
        }
    }

    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    if (btnElement) {
        // SVG two-icon approach: .icon-eye (visible) / .icon-eye-off (hidden)
        const iconEye    = btnElement.querySelector('.icon-eye');
        const iconEyeOff = btnElement.querySelector('.icon-eye-off');

        if (iconEye && iconEyeOff) {
            iconEye.style.display    = isPassword ? 'none' : '';
            iconEyeOff.style.display = isPassword ? ''     : 'none';
        } else {
            // Legacy emoji fallback
            btnElement.innerHTML = isPassword ? '🙈' : '👁️';
        }

        btnElement.title = isPassword ? 'Hide password' : 'Show password';
        btnElement.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    }
}

// Expose globally for inline onclick handlers
window.togglePasswordVisibility = togglePasswordVisibility;

function initPasswordToggles() {
    document.querySelectorAll('.btn-toggle-password').forEach(btn => {
        btn.type = 'button';
        // Skip buttons that already declare inline onclick — they call the global directly
        if (btn.hasAttribute('onclick')) return;
        btn.onclick = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            const targetId = this.getAttribute('data-target') || null;
            togglePasswordVisibility(targetId, this);
            return false;
        };
    });
}

/* ==========================================================================
   3. FACILITY DATA & MODAL CONTROLLERS
   ========================================================================== */
const facilityData = {
    gymnasium: {
        title: "KLD Gymnasium",
        category: "Sports & Athletics",
        status: "Available for Booking",
        image: "assets/images/campus_bg.png",
        location: "KLD Campus, Sports Complex",
        capacity: "1,200 Persons",
        aircon: "Standard High-Volume Ventilation",
        hours: "Monday – Saturday: 7:00 AM – 9:00 PM",
        description: "Multi-purpose university gymnasium designed for championship tournaments, student assemblies, athletic conditioning, and major institutional gatherings.",
        amenities: [
            "Regulation Basketball & Volleyball Hardwood Court",
            "Retractable Bleachers & VIP Box",
            "Acoustic Array PA Sound System",
            "Male & Female Team Locker Rooms with Showers",
            "Electronic Scoreboard & Timing Console"
        ],
        rules: "Non-marking rubber shoes required. Prior Department of Physical Education endorsement required."
    },
    avr: {
        title: "Audio Visual Room (AVR)",
        category: "AVR & Seminar",
        status: "Available for Booking",
        image: "assets/images/campus_bg.png",
        location: "KLD Main Academic Building, 2nd Floor",
        capacity: "180 Persons",
        aircon: "Fully Air-Conditioned",
        hours: "Monday – Saturday: 8:00 AM – 6:00 PM",
        description: "High-tier presentation amphitheater equipped with cutting-edge multimedia projection, studio acoustics, and theater seating.",
        amenities: [
            "4K Laser Projection System & Motorized Screen",
            "Wireless Lapel & Handheld Microphones",
            "Tiered Plush Theater Seating",
            "Dedicated Audio-Visual Control Booth",
            "Stage Lectern with Touch Screen Controls"
        ],
        rules: "No food or open drinks allowed. Multimedia operations must be supervised by MIS staff."
    },
    "nursing-lecture": {
        title: "Nursing Amphitheater Lecture Hall",
        category: "Lecture Hall",
        status: "Available for Booking",
        image: "assets/images/campus_bg.png",
        location: "Institute of Health Sciences, 1st Floor",
        capacity: "140 Persons",
        aircon: "Fully Air-Conditioned",
        hours: "Monday – Saturday: 7:30 AM – 6:30 PM",
        description: "Modern lecture theater configured for clinical demonstrations, medical case presentations, and multidisciplinary symposiums.",
        amenities: [
            "Interactive Smart Display Board",
            "Audio Lectern with Dual Microphones",
            "Tiered Continuous Bench Seating with Power Ports",
            "High-Speed Campus Wi-Fi 6 Access",
            "Panoramic Dry-Erase Whiteboards"
        ],
        rules: "Priority assigned to Institute of Health Sciences departmental classes."
    },
    "learning-commons": {
        title: "College Learning Commons",
        category: "Study & Commons",
        status: "Available for Booking",
        image: "assets/images/campus_bg.png",
        location: "University Library Building, Ground Floor",
        capacity: "200 Persons",
        aircon: "Fully Air-Conditioned",
        hours: "Monday – Saturday: 8:00 AM – 7:00 PM",
        description: "Collaborative research and digital study facility equipped with modular breakout stations, high-speed workstations, and group discussion pods.",
        amenities: [
            "Modular Team Collaboration Tables",
            "Individual Quiet Study Carrels",
            "Digital Reference Terminals & Print Station",
            "Dedicated AC Power & USB Charging Outlets",
            "Whiteboard Brainstorming Walls"
        ],
        rules: "Maintain moderate conversational tone in collaborative zones; silence in study pods."
    },
    "computer-lab": {
        title: "Computer Laboratory 301",
        category: "Laboratory",
        status: "Available for Booking",
        image: "assets/images/campus_bg.png",
        location: "Institute of Computing Studies, 3rd Floor",
        capacity: "50 Workstations",
        aircon: "Fully Air-Conditioned",
        hours: "Monday – Friday: 7:30 AM – 7:30 PM",
        description: "Specialized ICT laboratory with modern high-performance computers, gigabit local area network, and dedicated programming environments.",
        amenities: [
            "50x Core-i7 Workstations with Dual Monitors",
            "Gigabit Fiber Uplink & Isolated Testing Subnet",
            "Instructor Master Broadcast Display",
            "Central UPS Backup System",
            "Development Tools (VS Code, Python, XAMPP, MySQL)"
        ],
        rules: "Food and sugary drinks strictly prohibited. External USB drives subject to automatic security scan."
    }
};

function initModals() {
    // Facility Detail Modal Triggers
    document.querySelectorAll('.btn-view-details').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const facilityId = btn.getAttribute('data-facility');
            openFacilityModal(facilityId);
        });
    });

    // Make Reservation Buttons
    document.querySelectorAll('.btn-make-reservation').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetFacility = btn.getAttribute('data-facility') || 'gymnasium';
            const userLogged = document.body.getAttribute('data-logged-in') === 'true';

            if (userLogged) {
                const resModal = document.getElementById('newReservationModal');
                if (resModal) {
                    const select = document.getElementById('resFacilitySelect');
                    if (select) select.value = targetFacility;
                    openModal('newReservationModal');
                } else {
                    window.location.href = 'dashboard.php?book=' + encodeURIComponent(targetFacility);
                }
            } else {
                const authModal = document.getElementById('authRequiredModal');
                if (authModal) {
                    openModal('authRequiredModal');
                } else {
                    window.location.href = 'login.php';
                }
            }
        });
    });

    // Close Modal Triggers
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    // Backdrop Click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeAllModals();
            }
        });
    });

    // ESC Key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllModals();
    });
}

function openModal(modalOrId) {
    const modal = typeof modalOrId === 'string' ? document.getElementById(modalOrId) : modalOrId;
    if (!modal) return;
    closeAllModals();
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAllModals() {
    document.querySelectorAll('.modal-backdrop').forEach(m => m.classList.remove('active'));
    document.body.style.overflow = '';
}

function openFacilityModal(id) {
    const data = facilityData[id] || facilityData['gymnasium'];
    const modal = document.getElementById('facilityDetailModal');
    if (!modal) return;

    const titleEl = document.getElementById('modalFacTitle');
    const catEl = document.getElementById('modalFacCategory');
    const statEl = document.getElementById('modalFacStatus');
    const descEl = document.getElementById('modalFacDesc');
    const locEl = document.getElementById('modalFacLocation');
    const capEl = document.getElementById('modalFacCapacity');
    const hrEl = document.getElementById('modalFacHours');
    const rulesEl = document.getElementById('modalFacRules');
    const amenEl = document.getElementById('modalFacAmenities');
    const bookBtn = document.getElementById('modalFacBookBtn');

    if (titleEl) titleEl.textContent = data.title;
    if (catEl) catEl.textContent = data.category;
    if (statEl) statEl.textContent = data.status;
    if (descEl) descEl.textContent = data.description;
    if (locEl) locEl.textContent = data.location;
    if (capEl) capEl.textContent = data.capacity;
    if (hrEl) hrEl.textContent = data.hours;
    if (rulesEl) rulesEl.textContent = data.rules;

    if (amenEl) {
        amenEl.innerHTML = '';
        data.amenities.forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `<span style="color: var(--kld-green-accent); margin-right: 8px;">✓</span> ${item}`;
            li.style.marginBottom = '6px';
            li.style.fontSize = '0.92rem';
            li.style.color = '#334155';
            amenEl.appendChild(li);
        });
    }

    if (bookBtn) {
        bookBtn.setAttribute('data-facility', id);
    }

    openModal('facilityDetailModal');
}

/* ==========================================================================
   4. FACILITY SEARCH & CATEGORY FILTERING (Home & Catalog)
   ========================================================================== */
function initFiltersAndSearch() {
    const filterTabs = document.querySelectorAll('.tab-btn');
    const searchInput = document.getElementById('facilitySearchInput');
    const cards = document.querySelectorAll('.facility-card');

    if (!cards.length) return;

    let currentCategory = 'all';
    let currentSearchTerm = '';

    function filterCards() {
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCategory = card.getAttribute('data-category') || '';
            const titleEl = card.querySelector('.facility-card-title');
            const descEl = card.querySelector('.facility-card-desc');

            const cardTitle = titleEl ? titleEl.textContent.toLowerCase() : '';
            const cardDesc = descEl ? descEl.textContent.toLowerCase() : '';

            const matchesCategory = (currentCategory === 'all' || cardCategory === currentCategory);
            const matchesSearch = cardTitle.includes(currentSearchTerm) || cardDesc.includes(currentSearchTerm);

            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        let noResults = document.getElementById('noResultsMsg');
        if (!noResults && document.querySelector('.facility-grid')) {
            noResults = document.createElement('div');
            noResults.id = 'noResultsMsg';
            noResults.style.textAlign = 'center';
            noResults.style.padding = '40px 20px';
            noResults.style.color = '#64748b';
            noResults.style.fontSize = '1.05rem';
            noResults.style.gridColumn = '1 / -1';
            noResults.innerHTML = '🔍 No matching facilities found. Please try another search keyword or category tab.';
            document.querySelector('.facility-grid').appendChild(noResults);
        }

        if (noResults) {
            noResults.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            filterTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentCategory = tab.getAttribute('data-filter');
            filterCards();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearchTerm = e.target.value.trim().toLowerCase();
            filterCards();
        });
    }
}

/* ==========================================================================
   5. FAQ ACCORDION, CATEGORY FILTER & LIVE SEARCH
   ========================================================================== */
function toggleFaqAccordion(headerBtn) {
    if (!headerBtn) return;
    const item = headerBtn.closest('.faq-accordion-item');
    if (!item) return;

    const isActive = item.classList.contains('active');
    const allItems = document.querySelectorAll('.faq-accordion-item');

    // Close other open items
    allItems.forEach(other => {
        if (other !== item) {
            other.classList.remove('active');
        }
    });

    // Toggle current item
    if (isActive) {
        item.classList.remove('active');
    } else {
        item.classList.add('active');
    }
}
window.toggleFaqAccordion = toggleFaqAccordion;

function initFaqSystem() {
    const faqItems = document.querySelectorAll('.faq-accordion-item');
    const searchInput = document.getElementById('faqSearchInput');
    const filterChips = document.querySelectorAll('.faq-chip');

    if (!faqItems.length) return;

    // Accordion Header Click Listeners
    faqItems.forEach(item => {
        const headerBtn = item.querySelector('.faq-item-header');
        if (headerBtn) {
            headerBtn.onclick = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                toggleFaqAccordion(this);
            };
        }
    });

    // Category Filtering
    let currentCategory = 'all';
    let currentSearchTerm = '';

    function filterFaqs() {
        let matchCount = 0;
        faqItems.forEach(item => {
            const itemCat = item.getAttribute('data-faq-category') || '';
            const qText = item.querySelector('.faq-item-question')?.textContent.toLowerCase() || '';
            const aText = item.querySelector('.faq-item-answer')?.textContent.toLowerCase() || '';

            const matchesCategory = (currentCategory === 'all' || itemCat.includes(currentCategory));
            const matchesSearch = qText.includes(currentSearchTerm) || aText.includes(currentSearchTerm);

            if (matchesCategory && matchesSearch) {
                item.style.display = 'block';
                matchCount++;
            } else {
                item.style.display = 'none';
            }
        });

        let noFaqMsg = document.getElementById('noFaqMatchMsg');
        if (!noFaqMsg && document.querySelector('.faq-accordion-container')) {
            noFaqMsg = document.createElement('div');
            noFaqMsg.id = 'noFaqMatchMsg';
            noFaqMsg.style.textAlign = 'center';
            noFaqMsg.style.padding = '40px 20px';
            noFaqMsg.style.color = '#64748b';
            noFaqMsg.style.fontSize = '1.05rem';
            noFaqMsg.innerHTML = '🔍 No matching questions found. Try searching for "reservation", "cancel", "equipment", or "lead time".';
            document.querySelector('.faq-accordion-container').appendChild(noFaqMsg);
        }

        if (noFaqMsg) {
            noFaqMsg.style.display = (matchCount === 0) ? 'block' : 'none';
        }
    }

    filterChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            filterChips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            currentCategory = chip.getAttribute('data-faq-filter') || 'all';
            filterFaqs();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            currentSearchTerm = e.target.value.trim().toLowerCase();
            filterFaqs();
        });
    }
}

/* ==========================================================================
   6. USER DASHBOARD INTERACTIONS & PRINT SLIP
   ========================================================================== */
function filterDashboardTable(status, btnElement) {
    const filterBtns = document.querySelectorAll('.table-filter-btn');
    const tableRows = document.querySelectorAll('.dash-reservation-row');

    if (filterBtns.length) {
        filterBtns.forEach(b => b.classList.remove('active'));
    }
    if (btnElement) {
        btnElement.classList.add('active');
    }

    tableRows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
window.filterDashboardTable = filterDashboardTable;

function openReservationSlip(btn) {
    if (!btn) return;
    const resId = btn.getAttribute('data-res-id');
    const fac = btn.getAttribute('data-facility');
    const date = btn.getAttribute('data-date');
    const time = btn.getAttribute('data-time');
    const purpose = btn.getAttribute('data-purpose');
    const status = btn.getAttribute('data-status');
    const applicant = btn.getAttribute('data-applicant');
    const dept = btn.getAttribute('data-dept');
    const attendees = btn.getAttribute('data-attendees');
    const equip = btn.getAttribute('data-equip');

    const slipIdEl = document.getElementById('slipResId');
    const slipFacEl = document.getElementById('slipFacName');
    const slipDtEl = document.getElementById('slipDateTime');
    const slipAppEl = document.getElementById('slipApplicant');
    const slipDeptEl = document.getElementById('slipDept');
    const slipPurpEl = document.getElementById('slipPurpose');
    const slipAttEl = document.getElementById('slipAttendees');
    const slipEquipEl = document.getElementById('slipEquip');
    const slipStatEl = document.getElementById('slipStatusBadge');

    if (slipIdEl) slipIdEl.textContent = resId;
    if (slipFacEl) slipFacEl.textContent = fac;
    if (slipDtEl) slipDtEl.textContent = `${date} | ${time}`;
    if (slipAppEl) slipAppEl.textContent = applicant;
    if (slipDeptEl) slipDeptEl.textContent = dept;
    if (slipPurpEl) slipPurpEl.textContent = purpose;
    if (slipAttEl) slipAttEl.textContent = attendees || '50+';
    if (slipEquipEl) slipEquipEl.textContent = equip || 'Standard facility setup';
    if (slipStatEl) slipStatEl.textContent = status;

    openModal('reservationSlipModal');
}
window.openReservationSlip = openReservationSlip;

function initDashboardInteractions() {
    const filterBtns = document.querySelectorAll('.table-filter-btn');

    if (filterBtns.length) {
        filterBtns.forEach(btn => {
            btn.onclick = function(e) {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const status = this.getAttribute('data-status-filter') || 'all';
                filterDashboardTable(status, this);
            };
        });
    }

    // View Slip Modal Handler
    document.querySelectorAll('.btn-slip-view').forEach(btn => {
        btn.onclick = function(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            openReservationSlip(this);
        };
    });

    // Print slip button
    const printBtn = document.getElementById('btnPrintSlip');
    if (printBtn) {
        printBtn.onclick = function(e) {
            if (e) e.preventDefault();
            window.print();
        };
    }
}

/* ==========================================================================
   7. FORGOT PASSWORD MULTI-STEP WIZARD
   ========================================================================== */
function initForgotPasswordWizard() {
    const step1Form = document.getElementById('forgotStep1Form');
    const step2Form = document.getElementById('forgotStep2Form');
    const step3Form = document.getElementById('forgotStep3Form');

    const cardStep1 = document.getElementById('forgotCardStep1');
    const cardStep2 = document.getElementById('forgotCardStep2');
    const cardStep3 = document.getElementById('forgotCardStep3');
    const cardStep4 = document.getElementById('forgotCardStep4');

    const dot1 = document.getElementById('stepDot1');
    const dot2 = document.getElementById('stepDot2');
    const dot3 = document.getElementById('stepDot3');
    const line1 = document.getElementById('stepLine1');
    const line2 = document.getElementById('stepLine2');

    if (!step1Form) return;

    let resendTimer = null;

    function startResendCountdown(seconds) {
        const resendBtn = document.getElementById('resendOtpBtn');
        if (!resendBtn) return;

        if (resendTimer) {
            clearInterval(resendTimer);
            resendTimer = null;
        }

        let remaining = parseInt(seconds, 10);
        if (isNaN(remaining) || remaining <= 0) {
            resendBtn.classList.remove('is-disabled');
            resendBtn.textContent = 'Resend OTP';
            return;
        }

        resendBtn.classList.add('is-disabled');
        resendBtn.textContent = `Resend OTP in ${remaining}s`;

        resendTimer = setInterval(() => {
            remaining--;
            if (remaining > 0) {
                resendBtn.textContent = `Resend OTP in ${remaining}s`;
            } else {
                clearInterval(resendTimer);
                resendTimer = null;
                resendBtn.classList.remove('is-disabled');
                resendBtn.textContent = 'Resend OTP';
            }
        }, 1000);
    }

    // Step 1: Submit email/ID -> send_otp
    step1Form.addEventListener('submit', (e) => {
        e.preventDefault();
        const emailInput = document.getElementById('forgotEmail');
        const email = emailInput ? emailInput.value.trim() : '';
        const csrfToken = step1Form.querySelector('input[name="csrf_token"]')?.value || '';
        const submitBtn = step1Form.querySelector('button[type="submit"]');

        if (!email) return;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending Code...';
        }

        const formData = new FormData();
        formData.append('action', 'send_otp');
        formData.append('csrf_token', csrfToken);
        formData.append('identifier', email);

        fetch('forgot-password.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Verification Code →';
            }
            if (data.success) {
                const targetEmailEl = document.getElementById('otpTargetEmail');
                if (targetEmailEl) targetEmailEl.textContent = data.masked_email || email;

                if (cardStep1) cardStep1.style.display = 'none';
                if (cardStep2) cardStep2.style.display = 'block';

                if (dot1) { dot1.className = 'step-dot completed'; dot1.innerHTML = '✓'; }
                if (dot2) dot2.className = 'step-dot active';
                if (line1) line1.classList.add('active');

                // Start real countdown from server wait seconds
                startResendCountdown(data.resend_wait || 60);
            } else {
                alert(data.message || 'Failed to send verification code.');
            }
        })
        .catch(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Verification Code →';
            }
            alert('A network error occurred. Please try again.');
        });
    });

    // Step 2: Submit OTP -> verify_otp
    if (step2Form) {
        step2Form.addEventListener('submit', (e) => {
            e.preventDefault();
            const otpInput = document.getElementById('otpCode');
            const otpCode = otpInput ? otpInput.value.trim() : '';
            const csrfToken = step2Form.querySelector('input[name="csrf_token"]')?.value || '';
            const submitBtn = step2Form.querySelector('button[type="submit"]');

            if (!otpCode) return;

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Verifying Code...';
            }

            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('csrf_token', csrfToken);
            formData.append('otp_code', otpCode);

            fetch('forgot-password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Verify Code & Continue →';
                }
                if (data.success) {
                    if (cardStep2) cardStep2.style.display = 'none';
                    if (cardStep3) cardStep3.style.display = 'block';

                    if (dot2) { dot2.className = 'step-dot completed'; dot2.innerHTML = '✓'; }
                    if (dot3) dot3.className = 'step-dot active';
                    if (line2) line2.classList.add('active');
                } else {
                    alert(data.message || 'Invalid or expired OTP code.');
                }
            })
            .catch(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Verify Code & Continue →';
                }
                alert('A network error occurred. Please try again.');
            });
        });

        // Resend OTP link in Step 2 with real countdown
        const resendBtn = document.getElementById('resendOtpBtn');
        if (resendBtn) {
            resendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (resendBtn.classList.contains('is-disabled')) return;
                const emailInput = document.getElementById('forgotEmail');
                const email = emailInput ? emailInput.value.trim() : '';
                const csrfToken = step2Form.querySelector('input[name="csrf_token"]')?.value || '';
                if (!email) return;

                resendBtn.classList.add('is-disabled');
                resendBtn.textContent = 'Sending...';

                const formData = new FormData();
                formData.append('action', 'send_otp');
                formData.append('csrf_token', csrfToken);
                formData.append('identifier', email);

                fetch('forgot-password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'If that account exists, a code was sent.');
                        startResendCountdown(data.resend_wait || 60);
                    } else {
                        alert(data.message || 'Failed to resend code.');
                        startResendCountdown(data.resend_wait || 60);
                    }
                })
                .catch(() => {
                    resendBtn.classList.remove('is-disabled');
                    resendBtn.textContent = 'Resend OTP';
                    alert('A network error occurred. Please try again.');
                });
            });
        }
    }

    // Step 3: Submit New Password -> reset_password
    if (step3Form) {
        step3Form.addEventListener('submit', (e) => {
            e.preventDefault();
            const p1 = document.getElementById('password')?.value || '';
            const p2 = document.getElementById('confirm_password')?.value || '';
            const csrfToken = step3Form.querySelector('input[name="csrf_token"]')?.value || '';
            const submitBtn = step3Form.querySelector('button[type="submit"]');

            if (p1 !== p2) {
                alert('Passwords do not match. Please ensure both fields are identical.');
                return;
            }

            const hasUpper = /[A-Z]/.test(p1);
            const hasLower = /[a-z]/.test(p1);
            const hasNumber = /[0-9]/.test(p1);
            const hasSpecial = /[^a-zA-Z0-9]/.test(p1);

            if (p1.length < 8 || !hasUpper || !hasLower || !hasNumber || !hasSpecial) {
                alert('Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.');
                return;
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Updating Password...';
            }

            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('csrf_token', csrfToken);
            formData.append('new_password', p1);
            formData.append('confirm_password', p2);

            fetch('forgot-password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset & Update Password';
                }
                if (data.success) {
                    if (cardStep3) cardStep3.style.display = 'none';
                    if (cardStep4) cardStep4.style.display = 'block';
                    if (dot3) { dot3.className = 'step-dot completed'; dot3.innerHTML = '✓'; }
                } else {
                    alert(data.message || 'Failed to update password.');
                }
            })
            .catch(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Reset & Update Password';
                }
                alert('A network error occurred. Please try again.');
            });
        });
    }
}

/* ==========================================================================
   8. SMOOTH SCROLLING
   ========================================================================== */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#' || !targetId) return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}
