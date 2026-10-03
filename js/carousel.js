/**
 * ==========================================================================
 * KOLEHIYO NG LUNGSOD NG DASMARIÑAS (KLD) - FACILITY RESERVATION SYSTEM
 * SCRIPT: Animated Image Slider / Carousel Transition Engine (js/carousel.js)
 * ==========================================================================
 *
 * ACADEMIC DEFENSE NOTES:
 * 1. This script handles ONLY the visual carousel slide transitions
 *    (next/prev navigation, thumbnail reordering, auto-advance timer).
 * 2. All reservation/authentication logic (btn-make-reservation click
 *    handlers, authRequiredModal, facilityDetailModal) remain in their
 *    original js/script.js and js/main.js modules — fully preserved.
 * 3. Carousel controls use DOM reordering (appendChild / prepend) to
 *    cycle slides, with CSS animation classes toggled on a timer.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Target carousel elements within the facilities section
    const nextDom = document.getElementById('next');
    const prevDom = document.getElementById('prev');
    const carouselDom = document.querySelector('.facilities-carousel-section .carousel');

    // Guard: Only initialize if carousel elements exist on this page
    if (!carouselDom || !nextDom || !prevDom) return;

    const SliderDom = carouselDom.querySelector('.list');
    const thumbnailBorderDom = carouselDom.querySelector('.thumbnail');

    // Timing Configuration
    const timeRunning = 3000;    // Duration of transition animation (ms)
    const timeAutoNext = 7000;   // Auto-advance interval (ms)
    let runTimeOut;
    let runNextAuto = setTimeout(() => {
        nextDom.click();
    }, timeAutoNext);

    // Next Button Handler
    nextDom.onclick = function () {
        showSlider('next');
    };

    // Prev Button Handler
    prevDom.onclick = function () {
        showSlider('prev');
    };

    /**
     * Core Slider Transition Function
     * Reorders DOM children to create infinite carousel effect.
     * Applies CSS animation classes for smooth visual transitions.
     *
     * @param {string} type - Direction: 'next' or 'prev'
     */
    function showSlider(type) {
        const SliderItemsDom = SliderDom.querySelectorAll('.item');
        const thumbnailItemsDom = thumbnailBorderDom.querySelectorAll('.item');

        if (type === 'next') {
            // Move first slide to end (advance forward)
            SliderDom.appendChild(SliderItemsDom[0]);
            thumbnailBorderDom.appendChild(thumbnailItemsDom[0]);
            carouselDom.classList.add('next');
        } else {
            // Move last slide to beginning (go backward)
            SliderDom.prepend(SliderItemsDom[SliderItemsDom.length - 1]);
            thumbnailBorderDom.prepend(thumbnailItemsDom[thumbnailItemsDom.length - 1]);
            carouselDom.classList.add('prev');
        }

        // Clear animation classes after transition completes
        clearTimeout(runTimeOut);
        runTimeOut = setTimeout(() => {
            carouselDom.classList.remove('next');
            carouselDom.classList.remove('prev');
        }, timeRunning);

        // Reset auto-advance timer on manual interaction
        clearTimeout(runNextAuto);
        runNextAuto = setTimeout(() => {
            nextDom.click();
        }, timeAutoNext);
    }
});
