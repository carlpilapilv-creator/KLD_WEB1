/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) - FACILITY RESERVATION SYSTEM
 * SCRIPT: Core Interactive Logic, Guest Interceptor, Off-Canvas Sidebar,
 *         Conflict Detection Engine, File Uploads, & Modals (js/script.js)
 * ==========================================================================
 * 
 * ACADEMIC DEFENSE ARCHITECTURE GUIDE:
 * 1. Public vs. Authenticated Workflows:
 *    - On public pages (index.php), unauthenticated guests can freely preview
 *      facility specifications. Any click on a "Make Reservation" trigger
 *      intercepts the flow and launches the #authRequiredModal.
 *    - On authenticated pages (dashboard.php), users have full access to the
 *      off-canvas navigation drawer, facility selection grid, real-time conflict
 *      detection engine, document upload tracking, and summarization verification.
 * 2. Real-Time Conflict Detection Engine:
 *    - Dynamically queries existing institutional bookings from embedded JSON data.
 *    - Inspects venue ID, date, and time intervals to warn of double bookings.
 * 3. Strict Database Hold Compliance:
 *    - Processes and previews booking submissions client-side/in-session
 *      without executing unauthorized MySQL insertions.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Guest Reservation Interceptor (Public index.php)
    initGuestReservationInterceptor();

    // 2. Initialize Off-Canvas Navigation Sidebar (Authenticated dashboard.php)
    initOffcanvasSidebar();

    // 3. Initialize Public Facility Preview Filtering & Modals (index.php)
    initPublicFacilityPreview();

    // 4. Initialize Authenticated Facility Grid & Category Tabs (dashboard.php)
    initDashboardFacilityCatalog();

    // 5. Initialize Real-Time Conflict Indicator Engine (dashboard.php)
    initDashboardConflictEngine();

    // 6. Initialize Pre-Event File Upload Trackers (dashboard.php)
    initDashboardUploadHandlers();

    // 7. Initialize Booking Form & Summarization Modal Workflow (dashboard.php)
    initDashboardReservationWorkflow();

    // 8. Initialize Universal Modal Close Triggers
    initUniversalModalControllers();
});

/* ==========================================================================
   MODULE 1: GUEST RESERVATION INTERCEPTOR (Public index.php)
   ========================================================================== */

/**
 * Ensures that clicking any "Make Reservation" trigger on public pages
 * prompts unauthenticated visitors with the login/register requirement modal.
 */
function initGuestReservationInterceptor() {
    const isUserLoggedIn = document.body.getAttribute('data-logged-in') === 'true';
    const authModal = document.getElementById('authRequiredModal');
    const reserveButtons = document.querySelectorAll('.btn-make-reservation');

    reserveButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (isUserLoggedIn) {
                // If authenticated, redirect to the main facility catalog portal
                window.location.href = 'main.php';
            } else {
                // Yield facilityDetailModal so authRequiredModal takes focus cleanly in the foreground
                const facModal = document.getElementById('facilityDetailModal');
                if (facModal) {
                    facModal.classList.remove('active');
                }
                // If unauthenticated guest, launch the Auth Required modal
                if (authModal) {
                    openModal(authModal);
                } else {
                    window.location.href = 'login.php';
                }
            }
        });
    });
}

/* ==========================================================================
   MODULE 2: OFF-CANVAS NAVIGATION SIDEBAR (dashboard.php)
   ========================================================================== */

/**
 * Handles the responsive upper-left hamburger button and sliding sidebar drawer.
 */
function initOffcanvasSidebar() {
    const hamburgerBtn = document.getElementById('offcanvasToggleBtn');
    const closeBtn = document.getElementById('offcanvasCloseBtn');
    const sidebar = document.getElementById('offcanvasNavSidebar');
    const backdrop = document.getElementById('offcanvasBackdrop');

    if (!sidebar || !backdrop) return;

    function openSidebar() {
        sidebar.classList.add('active');
        backdrop.classList.add('active');
        sidebar.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        backdrop.classList.remove('active');
        sidebar.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openSidebar();
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeSidebar();
        });
    }

    backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    const navPills = sidebar.querySelectorAll('.sidebar-nav-pill');
    navPills.forEach(pill => {
        pill.addEventListener('click', closeSidebar);
    });
}

/* ==========================================================================
   MODULE 3: PUBLIC FACILITY PREVIEW FILTERING & QUICK VIEW (index.php)
   ========================================================================== */

/**
 * Public catalog facility database for quick specifications modal.
 */
const publicFacilityDetails = {
    gymnasium: {
        title: "KLD Gymnasium",
        category: "Sports & Athletics",
        hours: "Mon - Sat: 7:00 AM – 9:00 PM",
        location: "KLD Sports Complex, Ground Level",
        capacity: "1,200 Persons",
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
        hours: "Mon - Sat: 8:00 AM – 6:00 PM",
        location: "Main Academic Building, 2nd Floor",
        capacity: "180 Persons",
        description: "High-tier presentation amphitheater equipped with cutting-edge multimedia projection, studio acoustics, and tiered theater seating.",
        amenities: [
            "4K Laser Projection System & Motorized Screen",
            "Wireless Lapel & Handheld Microphones",
            "Tiered Plush Theater Seating",
            "Dedicated Audio-Visual Control Booth",
            "Stage Lectern with Touch Screen Controls"
        ],
        rules: "No open food or drinks permitted. MIS staff technician must be on standby."
    },
    "nursing-lecture": {
        title: "Nursing Amphitheater Hall",
        category: "Lecture Hall",
        hours: "Mon - Sat: 7:30 AM – 6:30 PM",
        location: "Institute of Health Sciences, 1st Floor",
        capacity: "140 Persons",
        description: "Modern lecture theater configured for clinical demonstrations, medical case presentations, and multidisciplinary symposiums.",
        amenities: [
            "Interactive Smart Display Board",
            "Audio Lectern with Dual Microphones",
            "Tiered Continuous Bench Seating with Power Outlets",
            "High-Speed Campus Wi-Fi 6 Access",
            "Panoramic Dry-Erase Whiteboards"
        ],
        rules: "Priority assigned to Institute of Health Sciences departmental academic classes."
    },
    "learning-commons": {
        title: "College Learning Commons",
        category: "Study & Commons",
        hours: "Mon - Sat: 8:00 AM – 7:00 PM",
        location: "University Library Building, Ground Floor",
        capacity: "200 Persons",
        description: "Open collaborative study space with group discussion areas, individual study stations, and digital learning resources.",
        amenities: [
            "Modular Team Collaboration Tables",
            "Individual Quiet Study Carrels",
            "Digital Reference Terminals & Print Station",
            "Dedicated AC Power & USB Charging Outlets",
            "Whiteboard Brainstorming Walls"
        ],
        rules: "Maintain moderate conversational tone in collaborative zones; quiet study in carrels."
    },
    "computer-lab": {
        title: "Computer Laboratory 301",
        category: "Laboratory",
        hours: "Mon - Fri: 7:30 AM – 7:30 PM",
        location: "Institute of Computing Studies, 3rd Floor",
        capacity: "50 Workstations",
        description: "Specialized ICT laboratory with modern high-performance computers, gigabit local area network, and dedicated programming environments.",
        amenities: [
            "50x Core-i7 Workstations with Dual Monitors",
            "Gigabit Fiber Uplink & Isolated Testing Subnet",
            "Instructor Master Broadcast Display",
            "Central UPS Backup System",
            "Development Tools (VS Code, Python, XAMPP, MySQL)"
        ],
        rules: "Food and sugary drinks strictly prohibited. External USB storage subject to security scans."
    },
    "anatomy-lab": {
        title: "Anatomy & Physiology Laboratory",
        category: "Laboratory",
        hours: "Mon - Sat: 8:00 AM – 5:00 PM",
        location: "Health Sciences Wing, Ground Floor",
        capacity: "45 Students",
        description: "Equipped with full anatomical skeletal models, organ specimens, and biological dissection tools for nursing students.",
        amenities: [
            "Anatomical 3D Models & Skeletons",
            "Stainless Steel Dissection Stations",
            "Safety Eyewash & Chemical Showers",
            "High-Magnification Microscopes",
            "Secure Biological Specimen Storage"
        ],
        rules: "Laboratory coats, gloves, and protective gear required at all times."
    }
};

/**
 * Handles category filtering, real-time search, and detail modal for public index.php.
 */
function initPublicFacilityPreview() {
    const tabs = document.querySelectorAll('.category-tabs .tab-btn');
    const search = document.getElementById('facilitySearchInput');
    const cards = document.querySelectorAll('.facilities-section .facility-card');
    const modal = document.getElementById('facilityDetailModal');

    let currentFilter = 'all';
    let currentQuery = '';

    function applyFilters() {
        cards.forEach(card => {
            const cat = card.getAttribute('data-category') || '';
            const title = (card.querySelector('.facility-card-title')?.textContent || '').toLowerCase();
            const desc = (card.querySelector('.facility-card-desc')?.textContent || '').toLowerCase();

            const matchCat = (currentFilter === 'all') || (cat === currentFilter);
            const matchQuery = !currentQuery || title.includes(currentQuery) || desc.includes(currentQuery);

            card.style.display = (matchCat && matchQuery) ? 'flex' : 'none';
        });
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    if (search) {
        search.addEventListener('input', (e) => {
            currentQuery = e.target.value.trim().toLowerCase();
            applyFilters();
        });
    }

    // "View Details" click handler
    const detailButtons = document.querySelectorAll('.btn-view-details');
    detailButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const facKey = btn.getAttribute('data-facility');
            const data = publicFacilityDetails[facKey];
            if (!data || !modal) return;

            const title = document.getElementById('modalFacTitle');
            const cat = document.getElementById('modalFacCategory');
            const hours = document.getElementById('modalFacHours');
            const desc = document.getElementById('modalFacDesc');
            const loc = document.getElementById('modalFacLocation');
            const cap = document.getElementById('modalFacCapacity');
            const amenities = document.getElementById('modalFacAmenities');
            const rules = document.getElementById('modalFacRules');

            if (title) title.textContent = data.title;
            if (cat) cat.textContent = data.category;
            if (hours) hours.textContent = data.hours;
            if (desc) desc.textContent = data.description;
            if (loc) loc.textContent = data.location;
            if (cap) cap.textContent = data.capacity;
            if (rules) rules.textContent = data.rules;

            if (amenities) {
                amenities.innerHTML = '';
                data.amenities.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'modal-amenity-item';
                    li.innerHTML = `<span>✓</span> <span>${item}</span>`;
                    amenities.appendChild(li);
                });
            }

            openModal(modal);
        });
    });
}

/* ==========================================================================
   MODULE 4: AUTHENTICATED FACILITY GRID (dashboard.php)
   ========================================================================== */

/**
 * Handles category filtering and card selection within the dashboard workflow.
 */
function initDashboardFacilityCatalog() {
    const tabs = document.querySelectorAll('.dash-category-tabs .dash-tab-btn');
    const cards = document.querySelectorAll('.dash-facility-grid .dash-fac-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const category = tab.getAttribute('data-dash-category') || 'all';

            cards.forEach(card => {
                const cardCat = card.getAttribute('data-dash-category') || '';
                if (category === 'all' || cardCat === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Selecting a facility card updates the active venue indicator
    cards.forEach(card => {
        card.addEventListener('click', () => {
            cards.forEach(c => {
                c.classList.remove('selected-card');
                const btn = c.querySelector('.btn-dash-select-fac');
                if (btn) btn.textContent = 'Select Facility';
            });

            card.classList.add('selected-card');
            const selectBtn = card.querySelector('.btn-dash-select-fac');
            if (selectBtn) selectBtn.textContent = 'Selected Venue';

            const facId = card.getAttribute('data-facility-id');
            const facName = card.getAttribute('data-facility-name');
            const cap = card.getAttribute('data-capacity');
            const loc = card.getAttribute('data-location');

            const hiddenId = document.getElementById('dashFacilityId');
            const hiddenName = document.getElementById('dashFacilityName');
            const dispName = document.getElementById('dashDisplayVenueName');
            const dispMeta = document.getElementById('dashDisplayVenueMeta');

            if (hiddenId) hiddenId.value = facId;
            if (hiddenName) hiddenName.value = facName;
            if (dispName) dispName.textContent = facName;
            if (dispMeta) dispMeta.textContent = `Capacity: ${cap} • ${loc}`;

            // Re-evaluate conflict on facility change
            evaluateDashConflict();
        });
    });
}

/* ==========================================================================
   MODULE 5: REAL-TIME CONFLICT INDICATOR ENGINE (dashboard.php)
   ========================================================================== */

/**
 * Checks whether the chosen venue and time slot clash with existing bookings.
 */
function evaluateDashConflict() {
    const facilityId = document.getElementById('dashFacilityId')?.value || 'gymnasium';
    const dateVal = document.getElementById('dashDate')?.value || '';
    const conflictBox = document.getElementById('dashConflictIndicator');
    const conflictIcon = document.getElementById('dashConflictIcon');
    const conflictTitle = document.getElementById('dashConflictTitle');
    const conflictDetail = document.getElementById('dashConflictDetail');

    if (!conflictBox) return false;

    let reservations = [];
    try {
        const raw = document.getElementById('dashExistingReservationsData')?.textContent || '[]';
        reservations = JSON.parse(raw);
    } catch (e) {
        reservations = [];
    }

    let conflict = null;
    for (let r of reservations) {
        if (r.facility_id === facilityId && r.date === dateVal && r.status !== 'Rejected') {
            conflict = r;
            break;
        }
    }

    if (conflict) {
        conflictBox.className = 'conflict-box conflict';
        if (conflictIcon) conflictIcon.textContent = '⚠️';
        if (conflictTitle) conflictTitle.textContent = 'Time-Slot Conflict Detected!';
        if (conflictDetail) {
            conflictDetail.textContent = `This facility is already reserved for "${conflict.purpose}" (${conflict.time_slot}). Please select a different date or venue.`;
        }
        return true;
    } else {
        conflictBox.className = 'conflict-box clear';
        if (conflictIcon) conflictIcon.textContent = '✓';
        if (conflictTitle) conflictTitle.textContent = 'Time Slot Available';
        if (conflictDetail) {
            conflictDetail.textContent = 'No scheduling conflict detected for this venue and date.';
        }
        return false;
    }
}

function initDashboardConflictEngine() {
    const dateInput = document.getElementById('dashDate');
    const startSelect = document.getElementById('dashStartTime');
    const endSelect = document.getElementById('dashEndTime');

    [dateInput, startSelect, endSelect].forEach(el => {
        if (el) el.addEventListener('change', evaluateDashConflict);
    });

    evaluateDashConflict();
}

/* ==========================================================================
   MODULE 6: PRE-EVENT FILE UPLOADS (dashboard.php)
   ========================================================================== */

function initDashboardUploadHandlers() {
    const deanInput = document.getElementById('dashFileDean');
    const deanChip = document.getElementById('chipDashDean');
    const deanName = document.getElementById('nameDashDean');

    const endInput = document.getElementById('dashFileEndorsement');
    const endChip = document.getElementById('chipDashEndorsement');
    const endName = document.getElementById('nameDashEndorsement');

    if (deanInput && deanChip && deanName) {
        deanInput.addEventListener('change', () => {
            if (deanInput.files && deanInput.files[0]) {
                deanName.textContent = deanInput.files[0].name;
                deanChip.classList.add('active');
            } else {
                deanChip.classList.remove('active');
            }
        });
    }

    if (endInput && endChip && endName) {
        endInput.addEventListener('change', () => {
            if (endInput.files && endInput.files[0]) {
                endName.textContent = endInput.files[0].name;
                endChip.classList.add('active');
            } else {
                endChip.classList.remove('active');
            }
        });
    }
}

/* ==========================================================================
   MODULE 7: DASHBOARD RESERVATION WORKFLOW & SUMMARIZATION MODAL
   ========================================================================== */

function initDashboardReservationWorkflow() {
    const form = document.getElementById('dashReservationForm');
    const modal = document.getElementById('dashSummaryModal');
    const confirmBtn = document.getElementById('btnConfirmFinalDashBooking');
    const dismissFlash = document.getElementById('btnDismissFlash');

    if (dismissFlash) {
        dismissFlash.addEventListener('click', () => {
            const flash = document.getElementById('dashFlashAlert');
            if (flash) flash.style.display = 'none';
        });
    }

    if (!form || !modal) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const eventName = document.getElementById('dashEventName')?.value.trim();
        const eventPurpose = document.getElementById('dashEventPurpose')?.value.trim();
        const attendees = document.getElementById('dashAttendees')?.value.trim();
        const dateVal = document.getElementById('dashDate')?.value;
        const startTime = document.getElementById('dashStartTime')?.value;
        const endTime = document.getElementById('dashEndTime')?.value;
        const venueName = document.getElementById('dashFacilityName')?.value;

        if (!eventName || !eventPurpose || !attendees || !dateVal || !startTime || !endTime) {
            alert('Please fill out all required event fields before proceeding.');
            return;
        }

        const hasConflict = evaluateDashConflict();
        if (hasConflict) {
            alert('Scheduling conflict detected for the selected venue and date. Please choose an alternative slot.');
            return;
        }

        // Populate modal
        const sumVenue = document.getElementById('dashSumVenue');
        const sumName = document.getElementById('dashSumEventName');
        const sumPurpose = document.getElementById('dashSumPurpose');
        const sumDateTime = document.getElementById('dashSumDateTime');
        const sumAttendees = document.getElementById('dashSumAttendees');
        const sumDocs = document.getElementById('dashSumDocsList');

        if (sumVenue) sumVenue.textContent = venueName;
        if (sumName) sumName.textContent = eventName;
        if (sumPurpose) sumPurpose.textContent = eventPurpose;
        if (sumDateTime) sumDateTime.textContent = `${dateVal} | ${startTime} - ${endTime}`;
        if (sumAttendees) sumAttendees.textContent = `${attendees} Persons`;

        if (sumDocs) {
            sumDocs.innerHTML = '';
            const deanFile = document.getElementById('dashFileDean')?.files[0];
            const endFile = document.getElementById('dashFileEndorsement')?.files[0];

            let count = 0;
            if (deanFile) {
                count++;
                const li = document.createElement('li');
                li.className = 'summary-doc-item';
                li.innerHTML = `<span>📎</span> <strong>Dean's Letter:</strong> ${deanFile.name}`;
                sumDocs.appendChild(li);
            }
            if (endFile) {
                count++;
                const li = document.createElement('li');
                li.className = 'summary-doc-item';
                li.innerHTML = `<span>📎</span> <strong>Endorsement:</strong> ${endFile.name}`;
                sumDocs.appendChild(li);
            }
            if (count === 0) {
                const li = document.createElement('li');
                li.className = 'summary-doc-item';
                li.innerHTML = `<span>⚠️</span> No attached administrative clearances.`;
                sumDocs.appendChild(li);
            }
        }

        openModal(modal);
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            const eventName = document.getElementById('dashEventName')?.value.trim();
            const venueName = document.getElementById('dashFacilityName')?.value;

            closeModal(modal);

            // Strict database hold: simulated client confirmation
            alert(`🎉 Success!\n\nYour reservation for "${eventName}" at "${venueName}" has been submitted for administrative review.\n\n(Strict Database Hold: Processed without MySQL query)`);

            form.reset();
            document.getElementById('chipDashDean')?.classList.remove('active');
            document.getElementById('chipDashEndorsement')?.classList.remove('active');
            evaluateDashConflict();
        });
    }
}

/* ==========================================================================
   MODULE 8: UNIVERSAL MODAL CONTROLLER
   ========================================================================== */

function openModal(modal) {
    if (!modal) return;
    const targetModal = typeof modal === 'string' ? document.getElementById(modal) : modal;
    if (!targetModal) return;
    targetModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    if (!modal) return;
    const targetModal = typeof modal === 'string' ? document.getElementById(modal) : modal;
    if (!targetModal) return;
    targetModal.classList.remove('active');
    if (!document.querySelector('.modal-backdrop.active')) {
        document.body.style.overflow = '';
    }
}

function initUniversalModalControllers() {
    const closeButtons = document.querySelectorAll('[data-close-modal]');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) closeModal(modal);
        });
    });

    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModal(backdrop);
            }
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-backdrop.active');
            if (activeModal) closeModal(activeModal);
        }
    });
}
