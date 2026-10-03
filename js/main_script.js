/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) — FACILITY RESERVATION SYSTEM
 * SCRIPT: Main Reservation Portal — Facility Catalog Interactions (js/main_script.js)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE ARCHITECTURE GUIDE:
 * 1. Off-Canvas Sidebar: The primary navigation on main.php is a slide-out
 *    sidebar triggered by the top-left hamburger button. It contains links to
 *    Dashboard, View Reservations, Account Settings, System Settings, and Log Out.
 *
 * 2. Facility Grid Filter & Search: Instantly filters cards client-side by
 *    category tab selection and text search query without a page reload.
 *
 * 3. Reserve Button → Modal Population: Clicking "Reserve" on any facility card
 *    reads the card's data-attributes (facility-id, name, capacity, location)
 *    and populates the #mainBookingModal's venue banner and hidden form fields.
 *
 * 4. Real-Time Conflict Detection Engine: On date/time change, reads the
 *    server-injected JSON dataset (#existingReservationsJson) and checks if
 *    any existing reservation matches the selected facility and date. Displays
 *    a color-coded indicator (green = clear, red = conflict).
 *
 * 5. Upload Dropzone Handlers: Listens for file input changes and shows a
 *    filename chip confirming the selected document attachment.
 *
 * 6. Form Validation → Summary Modal: On form submit, validates all required
 *    fields and runs the conflict check before closing the booking modal and
 *    opening the verification summary modal.
 *
 * 7. Summary Confirm → POST Submission: The "Confirm & Submit" button submits
 *    the actual booking form via programmatic form.submit() to trigger the PHP
 *    POST handler in main.php (strict database hold: session array storage only).
 *
 * 8. Quick-View Detail Modal: Shows full facility specs and amenities for the
 *    "View Details" button without requiring navigation to another page.
 *
 * 9. Universal Modal Controller: Handles all modal close triggers — close buttons,
 *    backdrop click, and keyboard Escape key.
 */

document.addEventListener('DOMContentLoaded', () => {
    try { initMainOffcanvasSidebar();         } catch (e) { console.warn('[Sidebar]',      e); }
    try { initMainFacilityFilter();           } catch (e) { console.warn('[Filter]',       e); }
    try { initReserveButtonHandlers();        } catch (e) { console.warn('[Reserve]',      e); }
    try { initConflictEngine();               } catch (e) { console.warn('[Conflict]',     e); }
    try { initUploadHandlers();               } catch (e) { console.warn('[Upload]',       e); }
    try { initBookingFormWorkflow();          } catch (e) { console.warn('[Booking]',      e); }
    try { initQuickViewHandlers();            } catch (e) { console.warn('[QuickView]',    e); }
    try { initUniversalModalController();     } catch (e) { console.warn('[Modal]',        e); }
    try { initFlashDismiss();                 } catch (e) { console.warn('[Flash]',        e); }
    try { initSettingsModals();               } catch (e) { console.warn('[Settings]',     e); }
    try { initSettingsTabs();                 } catch (e) { console.warn('[SettingsTabs]', e); }
    try { initPasswordFormClient();           } catch (e) { console.warn('[PwdForm]',      e); }
    try { initFacilityCarousel();             } catch (e) { console.warn('[Carousel]',     e); }
});

/* ==========================================================================
   MODULE 1: OFF-CANVAS NAVIGATION SIDEBAR
   ========================================================================== */

/**
 * Controls the hamburger-triggered off-canvas sidebar drawer:
 * open/close transitions, backdrop click-to-close, Escape key support.
 */
function initMainOffcanvasSidebar() {
    const hamburgerBtn = document.getElementById('mainHamburgerBtn');
    const closeSidebarBtn = document.getElementById('mainSidebarCloseBtn');
    const sidebar = document.getElementById('mainOffcanvasSidebar');
    const backdrop = document.getElementById('mainOffcanvasBackdrop');

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

    if (closeSidebarBtn) {
        closeSidebarBtn.addEventListener('click', (e) => {
            e.preventDefault();
            closeSidebar();
        });
    }

    // Clicking the dark backdrop closes the sidebar
    backdrop.addEventListener('click', closeSidebar);

    // Escape key closes the sidebar (and does not close modals simultaneously
    // — that is handled separately in initUniversalModalController)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    // Clicking any navigation link inside the sidebar auto-closes it
    // Note: sidebar-nav-pill buttons with data-open-modal skip close and
    // are handled by initSettingsModals instead.
    sidebar.querySelectorAll('.sidebar-nav-pill:not([data-open-modal]), .sidebar-logout-pill').forEach(link => {
        link.addEventListener('click', closeSidebar);
    });

    // Hamburger X animation state
    if (hamburgerBtn) {
        const originalOpen = openSidebar;
        const originalClose = closeSidebar;
        // Patch open to add is-open class
        sidebar._kldOpen = function() {
            originalOpen();
            hamburgerBtn.classList.add('is-open');
            hamburgerBtn.setAttribute('aria-expanded', 'true');
            hamburgerBtn.setAttribute('aria-label', 'Close navigation sidebar');
        };
        sidebar._kldClose = function() {
            originalClose();
            hamburgerBtn.classList.remove('is-open');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
            hamburgerBtn.setAttribute('aria-label', 'Open navigation sidebar');
        };
        // Re-wire hamburger click to use patched open
        hamburgerBtn.removeEventListener('click', hamburgerBtn._kldListener);
        hamburgerBtn._kldListener = (e) => { e.preventDefault(); sidebar._kldOpen(); };
        hamburgerBtn.addEventListener('click', hamburgerBtn._kldListener);
        // Re-wire close sources
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', () => sidebar._kldClose());
        }
        backdrop.addEventListener('click', () => sidebar._kldClose());
    }
}

/* ==========================================================================
   MODULE 2: FACILITY GRID — CATEGORY FILTER & SEARCH
   ========================================================================== */

/**
 * Provides instant client-side filtering of facility cards by:
 * (a) category tab selection, (b) real-time text search query.
 */
function initMainFacilityFilter() {
    const tabs = document.querySelectorAll('.main-category-tabs .main-tab-btn');
    const searchInput = document.getElementById('mainFacilitySearch');
    const cards = document.querySelectorAll('.main-facility-grid .main-fac-card');
    const emptyState = document.getElementById('mainEmptyState');

    let activeFilter = 'all';
    let searchQuery = '';

    function applyFilters() {
        let visibleCount = 0;

        cards.forEach(card => {
            const cat   = card.getAttribute('data-category') || '';
            const title = (card.querySelector('.main-fac-title')?.textContent || '').toLowerCase();
            const desc  = (card.querySelector('.main-fac-desc')?.textContent  || '').toLowerCase();

            const matchCat    = (activeFilter === 'all') || (cat === activeFilter);
            const matchSearch = !searchQuery || title.includes(searchQuery) || desc.includes(searchQuery);

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (emptyState) {
            emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
        }

        // Notify carousel to recalculate visible slide dots & counter
        window.dispatchEvent(new CustomEvent('kld:facilities-filtered', { detail: { visibleCount } }));
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            activeFilter = tab.getAttribute('data-filter') || 'all';
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            applyFilters();
        });
    }
}

/* ==========================================================================
   MODULE 3: RESERVE BUTTON → BOOKING MODAL POPULATION
   ========================================================================== */

/**
 * When any "Reserve" button is clicked, reads the parent card's data-attributes
 * and injects the venue name/metadata into the booking modal's venue banner.
 * Then opens #mainBookingModal.
 */
function initReserveButtonHandlers() {
    const bookingModal = document.getElementById('mainBookingModal');
    const reserveButtons = document.querySelectorAll('.btn-main-reserve');

    reserveButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const card = btn.closest('.main-fac-card');
            if (!card || !bookingModal) return;

            const facId       = card.getAttribute('data-facility-id')   || '';
            const facName     = card.getAttribute('data-facility-name')  || 'Facility';
            const facCapacity = card.getAttribute('data-capacity')       || '';
            const facLocation = card.getAttribute('data-location')       || '';

            // Populate hidden form inputs (submitted with POST)
            const hiddenFacId   = document.getElementById('bookingFacilityId');
            const hiddenFacName = document.getElementById('bookingFacilityName');
            if (hiddenFacId)   hiddenFacId.value   = facId;
            if (hiddenFacName) hiddenFacName.value = facName;

            // Update the modal venue banner display
            const bannerName = document.getElementById('bookingVenueName');
            const bannerMeta = document.getElementById('bookingVenueMeta');
            if (bannerName) bannerName.textContent = facName;
            if (bannerMeta) bannerMeta.textContent = `Capacity: ${facCapacity} • ${facLocation}`;

            // Re-run conflict check for this newly selected facility
            evaluateConflict();

            openModal(bookingModal);
        });
    });
}

/* ==========================================================================
   MODULE 4: REAL-TIME CONFLICT DETECTION ENGINE
   ========================================================================== */

/**
 * Helper: Parse time string (e.g. "08:00 AM", "05:00 PM") into minutes since midnight.
 * @param {string} timeStr
 * @returns {number|null}
 */
function parseTimeToMinutes(timeStr) {
    if (!timeStr) return null;
    const match = timeStr.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let hours = parseInt(match[1], 10);
    const minutes = parseInt(match[2], 10);
    const meridiem = match[3].toUpperCase();
    if (meridiem === 'PM' && hours < 12) hours += 12;
    if (meridiem === 'AM' && hours === 12) hours = 0;
    return hours * 60 + minutes;
}

/**
 * Reads existing booked slots (injected as JSON by PHP) and checks
 * whether the selected facility + date + time slot conflicts with an existing booking.
 * Conflict formula: new_start < existing_end + B AND new_end + B > existing_start (B = slot_buffer_hrs * 60).
 * Displays a generic feedback message without exposing personal details.
 *
 * @returns {boolean} true if a conflict is found, false if slot is clear.
 */
function evaluateConflict() {
    const facilityId = document.getElementById('bookingFacilityId')?.value || '';
    const dateVal    = document.getElementById('bookingDate')?.value        || '';
    const startTime  = document.getElementById('bookingStartTime')?.value   || '';
    const endTime    = document.getElementById('bookingEndTime')?.value     || '';

    const conflictBox    = document.getElementById('mainConflictBox');
    const conflictIcon   = document.getElementById('mainConflictIcon');
    const conflictTitle  = document.getElementById('mainConflictTitle');
    const conflictDetail = document.getElementById('mainConflictDetail');

    if (!conflictBox) return false;

    // Parse existing booked slots from the PHP-injected script tag
    let existingReservations = [];
    let bufferHrs = 1;
    const jsonTag = document.getElementById('existingReservationsJson');
    if (jsonTag) {
        try {
            existingReservations = JSON.parse(jsonTag.textContent || '[]');
        } catch (parseError) {
            existingReservations = [];
        }
        const b = parseFloat(jsonTag.getAttribute('data-buffer-hrs'));
        if (!isNaN(b) && b >= 0) {
            bufferHrs = b;
        }
    }

    const newStart = parseTimeToMinutes(startTime);
    const newEnd   = parseTimeToMinutes(endTime);
    const bufferMins = bufferHrs * 60;

    let conflict = null;
    if (facilityId && dateVal && newStart !== null && newEnd !== null && newStart < newEnd) {
        conflict = existingReservations.find(r => {
            if (r.facility_id !== facilityId || r.booking_date !== dateVal) {
                return false;
            }
            const parts = (r.time_slot || '').split('-');
            if (parts.length !== 2) return false;
            const exStart = parseTimeToMinutes(parts[0]);
            const exEnd   = parseTimeToMinutes(parts[1]);
            if (exStart === null || exEnd === null) return false;
            return (newStart < (exEnd + bufferMins)) && ((newEnd + bufferMins) > exStart);
        });
    }

    if (conflict) {
        conflictBox.className = 'main-conflict-box conflict';
        if (conflictIcon)   conflictIcon.textContent   = '⚠️';
        if (conflictTitle)  conflictTitle.textContent  = 'Time-Slot Conflict Detected!';
        if (conflictDetail) {
            conflictDetail.textContent =
                `This venue is already booked on ${conflict.booking_date} (${conflict.time_slot}).`;
        }
        return true;
    } else {
        conflictBox.className = 'main-conflict-box clear';
        if (conflictIcon)   conflictIcon.textContent   = '✓';
        if (conflictTitle)  conflictTitle.textContent  = 'Time Slot Available';
        if (conflictDetail) {
            conflictDetail.textContent = 'No scheduling conflict detected for this venue and date.';
        }
        return false;
    }
}

/**
 * Attaches change event listeners to the date and time fields so the conflict
 * engine re-evaluates automatically whenever the user adjusts the schedule.
 */
function initConflictEngine() {
    const dateInput  = document.getElementById('bookingDate');
    const startTime  = document.getElementById('bookingStartTime');
    const endTime    = document.getElementById('bookingEndTime');

    [dateInput, startTime, endTime].forEach(el => {
        if (el) el.addEventListener('change', evaluateConflict);
    });

    // Run once on page load to reflect default date value
    evaluateConflict();
}

/* ==========================================================================
   MODULE 5: FILE UPLOAD DROPZONE HANDLERS
   ========================================================================== */

/**
 * Initializes both document upload dropzones (Dean's Letter + Endorsement).
 * On file selection, adds a filename chip overlay and highlights the dropzone border.
 */
function initUploadHandlers() {
    setupDropzone('bookingFileDean',        'dropzoneDean',        'chipDean',        'chipDeanName');
    setupDropzone('bookingFileEndorsement', 'dropzoneEndorsement', 'chipEndorsement', 'chipEndorsementName');
}

/**
 * @param {string} inputId     - ID of the hidden file input element
 * @param {string} dropzoneId  - ID of the clickable dropzone container
 * @param {string} chipId      - ID of the filename confirmation chip element
 * @param {string} chipNameId  - ID of the <span> inside the chip for the filename text
 */
function setupDropzone(inputId, dropzoneId, chipId, chipNameId) {
    const input    = document.getElementById(inputId);
    const dropzone = document.getElementById(dropzoneId);
    const chip     = document.getElementById(chipId);
    const chipName = document.getElementById(chipNameId);

    if (!input || !dropzone) return;

    input.addEventListener('change', () => {
        if (input.files && input.files[0]) {
            dropzone.classList.add('has-file');
            if (chip && chipName) {
                chipName.textContent = input.files[0].name;
                chip.classList.add('active');
            }
        } else {
            dropzone.classList.remove('has-file');
            if (chip) chip.classList.remove('active');
        }
    });
}

/* ==========================================================================
   MODULE 6: BOOKING FORM VALIDATION → SUMMARY MODAL
   ========================================================================== */

/**
 * On booking form submit:
 * 1. Validates all required fields are filled.
 * 2. Runs the conflict detection check.
 * 3. If all clear, populates the summary verification modal.
 * 4. Closes the booking modal and opens the summary modal.
 *
 * The "Confirm & Submit" button in the summary modal submits the actual
 * HTML form via programmatic form.submit() to trigger the PHP POST handler.
 */
function initBookingFormWorkflow() {
    const bookingForm  = document.getElementById('mainBookingForm');
    const bookingModal = document.getElementById('mainBookingModal');
    const summaryModal = document.getElementById('mainSummaryModal');
    const confirmBtn   = document.getElementById('btnFinalConfirmBooking');

    if (!bookingForm) return;

    bookingForm.addEventListener('submit', (e) => {
        e.preventDefault();

        // Collect field values
        const eventName  = document.getElementById('bookingEventName')?.value.trim();
        const purpose    = document.getElementById('bookingPurpose')?.value.trim();
        const attendees  = document.getElementById('bookingAttendees')?.value.trim();
        const date       = document.getElementById('bookingDate')?.value;
        const startTime  = document.getElementById('bookingStartTime')?.value;
        const endTime    = document.getElementById('bookingEndTime')?.value;
        const venueName  = document.getElementById('bookingFacilityName')?.value || 'Facility';

        // Validate required fields
        if (!eventName || !purpose || !attendees || !date || !startTime || !endTime) {
            alert('Please complete all required fields before proceeding.');
            return;
        }

        // Validate that end time is after start time (basic check)
        if (startTime === endTime) {
            alert('Start time and end time cannot be the same. Please select a valid time range.');
            return;
        }

        // Run conflict detection — block submission if conflict found
        const hasConflict = evaluateConflict();
        if (hasConflict) {
            alert('A scheduling conflict was detected for the selected venue and date. Please choose a different date.');
            return;
        }

        // — Populate Summary Modal —
        setInnerText('sumVenue',     venueName);
        setInnerText('sumEventName', eventName);
        setInnerText('sumPurpose',   purpose);
        setInnerText('sumDateTime',  `${date} | ${startTime} – ${endTime}`);
        setInnerText('sumAttendees', `${attendees} Persons`);

        // Documents summary list
        const sumDocsList = document.getElementById('sumDocsList');
        if (sumDocsList) {
            sumDocsList.innerHTML = '';
            const deanFile = document.getElementById('bookingFileDean')?.files[0];
            const endFile  = document.getElementById('bookingFileEndorsement')?.files[0];
            let docCount = 0;

            if (deanFile) {
                docCount++;
                const li = document.createElement('li');
                li.className = 'summary-doc-li';
                li.innerHTML = `<span>📎</span> <strong>Dean's Letter:</strong> ${escapeHtml(deanFile.name)}`;
                sumDocsList.appendChild(li);
            }
            if (endFile) {
                docCount++;
                const li = document.createElement('li');
                li.className = 'summary-doc-li';
                li.innerHTML = `<span>📎</span> <strong>Endorsement:</strong> ${escapeHtml(endFile.name)}`;
                sumDocsList.appendChild(li);
            }
            if (docCount === 0) {
                const li = document.createElement('li');
                li.className = 'summary-doc-li';
                li.innerHTML = `<span>⚠️</span> No administrative clearances attached yet.`;
                sumDocsList.appendChild(li);
            }
        }

        // Transition: close booking modal → open summary modal
        closeModal(bookingModal);
        setTimeout(() => openModal(summaryModal), 80);
    });

    // "Confirm & Submit Request" — programmatically submits the form to PHP
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            closeModal(summaryModal);

            // Submit the real form via POST to main.php
            if (bookingForm) {
                bookingForm.submit();
            }
        });
    }
}

/* ==========================================================================
   MODULE 7: QUICK VIEW DETAIL MODAL (View Details Button)
   ========================================================================== */

/**
 * Catalog of detailed facility data for the quick-view modal.
 * Populated from static constants since the database hold is in effect.
 */
const facilityDetailsCatalog = {
    'gymnasium': {
        name: 'KLD Gymnasium',
        category: 'Sports & Athletics',
        hours: 'Mon - Sat: 7:00 AM – 9:00 PM',
        location: 'KLD Sports Complex, Ground Level',
        capacity: '1,200 Persons',
        description: 'Multi-purpose university gymnasium designed for championship tournaments, student assemblies, athletic conditioning, and major institutional gatherings.',
        amenities: [
            'Regulation Basketball & Volleyball Hardwood Court',
            'Retractable Bleachers & VIP Spectator Box',
            'Acoustic Array PA Sound System',
            'Male & Female Team Locker Rooms with Showers',
            'Electronic Scoreboard & Timing Console'
        ],
        rules: 'Non-marking rubber shoes required at all times. Prior Department of Physical Education endorsement required.'
    },
    'avr': {
        name: 'Audio Visual Room (AVR)',
        category: 'AVR & Seminar',
        hours: 'Mon - Sat: 8:00 AM – 6:00 PM',
        location: 'Main Academic Building, 2nd Floor',
        capacity: '180 Persons',
        description: 'High-tier presentation amphitheater equipped with cutting-edge multimedia projection, studio acoustics, and tiered theater seating for seminars and lectures.',
        amenities: [
            '4K Laser Projection System & Motorized Screen',
            'Wireless Lapel & Handheld Microphones',
            'Tiered Plush Theater Seating',
            'Dedicated Audio-Visual Control Booth',
            'Stage Lectern with Touch Screen Controls'
        ],
        rules: 'No open food or drinks permitted. MIS staff technician must be on standby during use.'
    },
    'nursing-lecture': {
        name: 'Nursing Amphitheater Hall',
        category: 'Lecture Hall',
        hours: 'Mon - Sat: 7:30 AM – 6:30 PM',
        location: 'Institute of Health Sciences, 1st Floor',
        capacity: '140 Persons',
        description: 'Modern lecture theater configured for clinical demonstrations, medical case presentations, and multi-disciplinary academic symposiums.',
        amenities: [
            'Interactive Smart Display Board',
            'Audio Lectern with Dual Microphones',
            'Tiered Continuous Bench Seating with Power Outlets',
            'High-Speed Campus Wi-Fi 6 Access',
            'Panoramic Dry-Erase Whiteboards'
        ],
        rules: 'Priority assigned to Institute of Health Sciences departmental academic classes during regular hours.'
    },
    'learning-commons': {
        name: 'College Learning Commons',
        category: 'Study & Commons',
        hours: 'Mon - Sat: 8:00 AM – 7:00 PM',
        location: 'University Library Building, Ground Floor',
        capacity: '200 Persons',
        description: 'Open collaborative study space with modular group discussion areas, individual study stations, and shared digital learning resources.',
        amenities: [
            'Modular Team Collaboration Tables',
            'Individual Quiet Study Carrels',
            'Digital Reference Terminals & Print Station',
            'Dedicated AC Power & USB Charging Outlets',
            'Whiteboard Brainstorming Walls'
        ],
        rules: 'Maintain moderate conversational tone in collaborative zones; strict quiet policy in carrel sections.'
    },
    'computer-lab': {
        name: 'Computer Laboratory 301',
        category: 'Laboratory',
        hours: 'Mon - Fri: 7:30 AM – 7:30 PM',
        location: 'Institute of Computing Studies, 3rd Floor',
        capacity: '50 Workstations',
        description: 'Specialized ICT laboratory with modern high-performance computers, gigabit local area network infrastructure, and dedicated software development environments.',
        amenities: [
            '50x Core-i7 Workstations with Dual Monitors',
            'Gigabit Fiber Uplink & Isolated Testing Subnet',
            'Instructor Master Broadcast Display Station',
            'Central UPS Backup System',
            'Pre-installed Dev Tools (VS Code, Python, XAMPP, MySQL)'
        ],
        rules: 'Food and sugary beverages strictly prohibited. External USB storage subject to mandatory security scans.'
    },
    'anatomy-lab': {
        name: 'Anatomy & Physiology Laboratory',
        category: 'Laboratory',
        hours: 'Mon - Sat: 8:00 AM – 5:00 PM',
        location: 'Health Sciences Wing, Ground Floor',
        capacity: '45 Students',
        description: 'Equipped with full anatomical skeletal models, preserved organ specimens, and biological dissection tools for health science program students.',
        amenities: [
            'Anatomical 3D Models & Full Skeletal Displays',
            'Stainless Steel Dissection Stations',
            'Safety Eyewash Stations & Emergency Chemical Showers',
            'High-Magnification Compound Microscopes',
            'Secure Biological Specimen Refrigerated Storage'
        ],
        rules: 'Laboratory coats, nitrile gloves, and eye protection required at all times. No food or personal items on bench surfaces.'
    }
};

/**
 * Populates and opens the Quick View detail modal when "View Details" is clicked.
 * The "Reserve This Facility" button inside the modal triggers the booking flow.
 */
function initQuickViewHandlers() {
    const quickViewModal = document.getElementById('mainQuickViewModal');
    const viewDetailsBtns = document.querySelectorAll('.btn-main-view-details');

    viewDetailsBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();

            const facId = btn.getAttribute('data-facility-id');
            const data  = facilityDetailsCatalog[facId];

            if (!data || !quickViewModal) return;

            // Populate all text fields in the quick view modal
            setInnerText('qvFacTitle',    data.name);
            setInnerText('qvFacCategory', data.category);
            setInnerText('qvFacHours',    data.hours);
            setInnerText('qvFacLocation', data.location);
            setInnerText('qvFacCapacity', data.capacity);
            setInnerText('qvFacDesc',     data.description);
            setInnerText('qvFacRules',    data.rules);

            // Build amenities list
            const amenitiesList = document.getElementById('qvAmenitiesList');
            if (amenitiesList) {
                amenitiesList.innerHTML = '';
                data.amenities.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'qv-amenity-li';
                    li.innerHTML = `<span>✓</span> <span>${escapeHtml(item)}</span>`;
                    amenitiesList.appendChild(li);
                });
            }

            // Wire the "Reserve" button inside quick view to the booking modal
            const qvReserveBtn = document.getElementById('qvReserveBtn');
            if (qvReserveBtn) {
                // Remove previous listener and re-bind with correct facility
                const newBtn = qvReserveBtn.cloneNode(true);
                qvReserveBtn.parentNode.replaceChild(newBtn, qvReserveBtn);

                newBtn.addEventListener('click', () => {
                    closeModal(quickViewModal);
                    // Trigger the matching card's reserve button
                    const matchingCard = document.querySelector(`.main-fac-card[data-facility-id="${facId}"]`);
                    if (matchingCard) {
                        const reserveBtn = matchingCard.querySelector('.btn-main-reserve');
                        if (reserveBtn) reserveBtn.click();
                    }
                });
            }

            openModal(quickViewModal);
        });
    });
}

/* ==========================================================================
   MODULE 8: UNIVERSAL MODAL CONTROLLER
   ========================================================================== */

/**
 * Applies three universal close mechanisms to all .modal-backdrop elements:
 * 1. [data-close-modal] attribute on any button inside the modal
 * 2. Clicking directly on the backdrop overlay (outside the modal window)
 * 3. Pressing the Escape key
 */
function initUniversalModalController() {
    // Close buttons with [data-close-modal]
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) closeModal(modal);
        });
    });

    // Click directly on backdrop (not on the modal window) closes it
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal(backdrop);
        });
    });

    // Escape key closes the topmost active modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-backdrop.active');
            if (activeModal) closeModal(activeModal);
        }
    });
}

/**
 * Opens a modal by adding the .active class and locking body scroll.
 * @param {HTMLElement} modal - The .modal-backdrop element to open.
 */
function openModal(modal) {
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Closes a modal by removing the .active class and restoring body scroll.
 * @param {HTMLElement} modal - The .modal-backdrop element to close.
 */
function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

/* ==========================================================================
   MODULE 9: FLASH ALERT DISMISS
   ========================================================================== */

/**
 * Allows the user to dismiss the success/error flash notification bar.
 */
function initFlashDismiss() {
    const dismissBtn = document.getElementById('mainFlashDismiss');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            const flash = document.getElementById('mainFlashAlert');
            if (flash) {
                flash.style.opacity = '0';
                flash.style.transition = 'opacity 0.3s ease';
                setTimeout(() => { flash.style.display = 'none'; }, 300);
            }
        });
    }
}

/* ==========================================================================
   UTILITY HELPERS
   ========================================================================== */

/**
 * Safely sets the textContent of an element by its ID.
 * @param {string} id   - Element ID
 * @param {string} text - Text to set
 */
function setInnerText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text || '';
}

/**
 * Escapes HTML special characters to prevent XSS when inserting user-controlled
 * strings via innerHTML.
 * @param {string} str - Raw string to escape
 * @returns {string} HTML-escaped string
 */
function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

/* ==========================================================================
   MODULE 10: SETTINGS MODALS — Open via Sidebar Pill Triggers
   =========================================================================
   Handles the data-open-modal attribute on sidebar nav buttons.
   When a pill has [data-open-modal="modalId"], clicking it:
   1. Closes the sidebar
   2. Opens the corresponding modal backdrop
   ========================================================================== */
function initSettingsModals() {
    const sidebar = document.getElementById('mainOffcanvasSidebar');
    const backdrop = document.getElementById('mainOffcanvasBackdrop');

    function closeSidebar() {
        if (sidebar) { sidebar.classList.remove('active'); sidebar.setAttribute('aria-hidden', 'true'); }
        if (backdrop) { backdrop.classList.remove('active'); }
        document.body.style.overflow = '';
        const hamburgerBtn = document.getElementById('mainHamburgerBtn');
        if (hamburgerBtn) {
            hamburgerBtn.classList.remove('is-open');
            hamburgerBtn.setAttribute('aria-expanded', 'false');
        }
    }

    // Find all sidebar pills that have a data-open-modal attribute
    document.querySelectorAll('[data-open-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = btn.getAttribute('data-open-modal');
            const modal = document.getElementById(modalId);

            // Close sidebar first
            closeSidebar();

            if (modal) {
                // Small delay lets sidebar close animation complete
                setTimeout(() => {
                    modal.classList.add('active');
                    modal.removeAttribute('aria-hidden');
                    document.body.style.overflow = 'hidden';

                    // Focus the first focusable element inside the modal
                    const focusable = modal.querySelector(
                        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                    );
                    if (focusable) focusable.focus();
                }, 280);
            }
        });
    });
}

/* ==========================================================================
   MODULE 11: SETTINGS TABS — In-Modal Tab Switching
   =========================================================================
   Generic tab switcher for all .settings-tab-nav elements on the page.
   Works for both Account Settings and System Settings modals.
   ========================================================================== */
function initSettingsTabs() {
    const allTabNavs = document.querySelectorAll('.settings-tab-nav');

    allTabNavs.forEach(tabNav => {
        const tabs = tabNav.querySelectorAll('.settings-tab-btn');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all tabs in this nav group
                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });

                // Activate clicked tab
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');

                // Find which pane to show based on data-settings-tab
                const paneKey = tab.getAttribute('data-settings-tab');
                const modal = tab.closest('.settings-modal-window, .modal-backdrop');

                if (modal) {
                    modal.querySelectorAll('.settings-tab-pane').forEach(pane => {
                        pane.classList.remove('active');
                    });

                    const targetPane = modal.querySelector(
                        `.settings-tab-pane#pane${capitalize(paneKey)}` +
                        `, .settings-tab-pane#pane${paneKey.replace('-','')}`
                    );

                    // Fallback: match by aria-controls
                    const controlledPaneId = tab.getAttribute('aria-controls');
                    const controlledPane = controlledPaneId
                        ? modal.querySelector(`#${controlledPaneId}`)
                        : null;

                    const paneToShow = controlledPane || targetPane;
                    if (paneToShow) paneToShow.classList.add('active');
                }
            });
        });
    });
}

/* ==========================================================================
   MODULE 12: PASSWORD FORM — Client-Side Validation
   =========================================================================
   Validates the Change Password form before submission to give
   immediate feedback without a page reload where possible.
   ========================================================================== */
function initPasswordFormClient() {
    const form = document.getElementById('pwdChangeForm');
    if (!form) return;

    const newPwd  = document.getElementById('newPassword');
    const confPwd = document.getElementById('confirmPassword');
    const errBox  = document.getElementById('pwdClientError');

    function showErr(msg) {
        if (!errBox) return;
        errBox.textContent = msg;
        errBox.classList.add('show');
    }

    function clearErr() {
        if (!errBox) return;
        errBox.textContent = '';
        errBox.classList.remove('show');
    }

    form.addEventListener('submit', (e) => {
        clearErr();
        if (newPwd && confPwd && newPwd.value !== confPwd.value) {
            e.preventDefault();
            showErr('⚠️ New passwords do not match. Please re-enter.');
            confPwd.focus();
            return;
        }
        if (newPwd && newPwd.value.length < 8) {
            e.preventDefault();
            showErr('⚠️ Password must be at least 8 characters.');
            newPwd.focus();
        }
    });
}

/**
 * Capitalize first letter (used for tab pane ID lookup).
 */
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/* ==========================================================================
   MODULE 13: FACILITY SHOWCASE — ANIMATED SLIDER / CAROUSEL & VIEW TOGGLE
   ========================================================================== */
function initFacilityCarousel() {
    const grid = document.getElementById('mainFacilityGrid');
    const prevBtn = document.getElementById('carouselPrevBtn');
    const nextBtn = document.getElementById('carouselNextBtn');
    const dotsContainer = document.getElementById('carouselDots');
    const currentIdxEl = document.getElementById('carouselCurrentIndex');
    const totalIdxEl = document.getElementById('carouselTotalIndex');
    const viewToggleCarousel = document.getElementById('viewToggleCarousel');
    const viewToggleGrid = document.getElementById('viewToggleGrid');
    const paginationBar = document.getElementById('carouselPaginationBar');

    if (!grid) return;

    let isCarouselMode = true;

    function getVisibleCards() {
        return Array.from(grid.querySelectorAll('.main-fac-card')).filter(card => {
            return card.style.display !== 'none';
        });
    }

    function updateCarouselIndicators() {
        const visibleCards = getVisibleCards();
        const total = visibleCards.length;
        if (totalIdxEl) totalIdxEl.textContent = total;

        if (total === 0) {
            if (currentIdxEl) currentIdxEl.textContent = '0';
            if (prevBtn) prevBtn.disabled = true;
            if (nextBtn) nextBtn.disabled = true;
            if (dotsContainer) dotsContainer.innerHTML = '';
            return;
        }

        const scrollLeft = grid.scrollLeft;
        let activeIndex = 0;
        let minDiff = Infinity;

        visibleCards.forEach((card, idx) => {
            const cardLeft = card.offsetLeft - grid.offsetLeft;
            const diff = Math.abs(cardLeft - scrollLeft);
            if (diff < minDiff) {
                minDiff = diff;
                activeIndex = idx;
            }
        });

        if (currentIdxEl) currentIdxEl.textContent = activeIndex + 1;

        if (dotsContainer) {
            const dots = dotsContainer.querySelectorAll('.carousel-dot');
            dots.forEach((dot, idx) => {
                if (idx === activeIndex) {
                    dot.classList.add('active');
                    dot.setAttribute('aria-selected', 'true');
                } else {
                    dot.classList.remove('active');
                    dot.setAttribute('aria-selected', 'false');
                }
            });
        }

        if (prevBtn) prevBtn.disabled = (grid.scrollLeft <= 8);
        if (nextBtn) {
            const maxScroll = grid.scrollWidth - grid.clientWidth - 8;
            nextBtn.disabled = (grid.scrollLeft >= maxScroll);
        }
    }

    function buildDots() {
        if (!dotsContainer) return;
        dotsContainer.innerHTML = '';
        const visibleCards = getVisibleCards();

        visibleCards.forEach((card, idx) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'carousel-dot' + (idx === 0 ? ' active' : '');
            dot.setAttribute('role', 'tab');
            dot.setAttribute('aria-label', `Go to facility ${idx + 1}`);
            dot.addEventListener('click', () => {
                card.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
            });
            dotsContainer.appendChild(dot);
        });

        updateCarouselIndicators();
    }

    function scrollCarousel(direction) {
        const visibleCards = getVisibleCards();
        if (visibleCards.length === 0) return;

        const cardWidth = visibleCards[0].offsetWidth || 340;
        const gap = 24;
        const scrollAmount = (cardWidth + gap) * direction;

        grid.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => scrollCarousel(-1));
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => scrollCarousel(1));
    }

    let scrollTimeout;
    grid.addEventListener('scroll', () => {
        if (!isCarouselMode) return;
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(updateCarouselIndicators, 60);
    }, { passive: true });

    let isDown = false;
    let startX;
    let scrollLeftStart;

    grid.addEventListener('mousedown', (e) => {
        if (!isCarouselMode || e.target.closest('button, a, input')) return;
        isDown = true;
        startX = e.pageX - grid.offsetLeft;
        scrollLeftStart = grid.scrollLeft;
        grid.style.cursor = 'grabbing';
    });

    window.addEventListener('mouseup', () => {
        if (isDown) {
            isDown = false;
            grid.style.cursor = '';
        }
    });

    grid.addEventListener('mousemove', (e) => {
        if (!isDown || !isCarouselMode) return;
        e.preventDefault();
        const x = e.pageX - grid.offsetLeft;
        const walk = (x - startX) * 1.4;
        grid.scrollLeft = scrollLeftStart - walk;
    });

    function setViewMode(mode) {
        if (mode === 'grid') {
            isCarouselMode = false;
            grid.classList.remove('carousel-mode');
            if (prevBtn) prevBtn.style.display = 'none';
            if (nextBtn) nextBtn.style.display = 'none';
            if (paginationBar) paginationBar.style.display = 'none';
            if (viewToggleGrid) {
                viewToggleGrid.classList.add('active');
                viewToggleGrid.setAttribute('aria-pressed', 'true');
            }
            if (viewToggleCarousel) {
                viewToggleCarousel.classList.remove('active');
                viewToggleCarousel.setAttribute('aria-pressed', 'false');
            }
        } else {
            isCarouselMode = true;
            grid.classList.add('carousel-mode');
            if (prevBtn) prevBtn.style.display = 'flex';
            if (nextBtn) nextBtn.style.display = 'flex';
            if (paginationBar) paginationBar.style.display = 'flex';
            if (viewToggleCarousel) {
                viewToggleCarousel.classList.add('active');
                viewToggleCarousel.setAttribute('aria-pressed', 'true');
            }
            if (viewToggleGrid) {
                viewToggleGrid.classList.remove('active');
                viewToggleGrid.setAttribute('aria-pressed', 'false');
            }
            buildDots();
        }
    }

    if (viewToggleCarousel) {
        viewToggleCarousel.addEventListener('click', () => setViewMode('carousel'));
    }

    if (viewToggleGrid) {
        viewToggleGrid.addEventListener('click', () => setViewMode('grid'));
    }

    window.addEventListener('kld:facilities-filtered', () => {
        grid.scrollLeft = 0;
        if (isCarouselMode) {
            buildDots();
        }
    });

    buildDots();
}
