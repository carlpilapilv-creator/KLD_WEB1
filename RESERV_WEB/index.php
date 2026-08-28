<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Reservation System</title>
    <style>
        html, body {
            scroll-behavior: smooth;
            scroll-padding-top: 100px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #008d44;
            color: #333;
        }

        /* HEADER CONTAINER */
        .navbar-container {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #ffffff;
            padding: 8px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            width: 100%;
        }

        .header-wrapper {
            position: relative;
            width: 100%;
            height: 65px;
            display: flex;
            align-items: center;
        }

        /* CIRCULAR LOGO - PERFECTLY CENTERED WITH DARK GREEN BAR */
        .header-logo {
            position: absolute;
            left: 20px;
            top: 24px; /* Pantay sa vertical center ng dark green bar */
            transform: translateY(-50%);
            width: 62px;
            height: 62px;
            border-radius: 50%;
            z-index: 30;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            border: 2px solid #ffffff;
            object-fit: cover;
            background-color: #fff;
        }

        /* STAGGERED TWO-TIER BANNERS */
        .banner-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        /* 1. TOP DARK GREEN BAR */
        .dark-green-bar {
            position: absolute;
            top: 8px;
            left: 0;
            width: 58%;
            height: 32px;
            background-color: #32a23c;
            border-radius: 0 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 40px;
            gap: 35px;
            z-index: 2;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }

        .dark-green-bar a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.2px;
            transition: opacity 0.2s;
        }

        .dark-green-bar a:hover {
            opacity: 0.85;
        }

        /* 2. BOTTOM LIME GREEN BAR */
        .lime-green-bar {
            position: absolute;
            top: 32px;
            right: 0;
            width: 52%;
            height: 32px;
            background-color: #a7de00;
            border-radius: 10px 0 0 10px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 30px;
            gap: 25px;
            z-index: 1;
            box-shadow: 0 3px 8px rgba(0,0,0,0.25);
        }

        .lime-green-bar a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .lime-green-bar a:hover {
            opacity: 0.88;
        }

        /* USER AVATAR ICON */
        .user-icon {
            width: 22px;
            height: 22px;
            background: rgba(255, 255, 255, 0.45);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .user-icon svg {
            width: 14px;
            height: 14px;
            fill: #ffffff;
        }

        /* HERO & MAIN SECTION */
        .hero-container {
            position: relative;
            width: 100%;
            min-height: 100vh;
            background: 
                linear-gradient(
                    180deg, 
                    rgba(255, 255, 255, 0.95) 0%, 
                    rgba(255, 255, 255, 0.45) 25%, 
                    rgba(0, 166, 81, 0.75) 60%, 
                    rgba(0, 90, 40, 0.98) 100%
                ),
                url('image.jpg') center/cover fixed no-repeat;
            padding: 40px 50px 100px 50px;
        }

        .hero-view {
            min-height: 75vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .hero-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            gap: 20px;
        
        }

        .hero-title {
            color: #00a651;
            font-size: 120px;
            font-weight: 900;
            line-height: 0.9;
            letter-spacing: -1px;
            text-shadow: 0 4px 10px rgba(255,255,255,0.9);
        }

        .btn-reserve {
            background-color: #00a651;
            color: white;
            padding: 30px 60px;
            border: none;
            border-radius: 70px;
            font-size: 34px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .btn-reserve:hover {
            background-color: #008d44;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 12px 28px rgba(0,0,0,0.4);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
            cursor: pointer;
        }

        .card-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }

        .card-item:hover .card-img {
            transform: scale(1.05);
        }

        /* CONTENT BOX */
        .content-box {
            background-color: #00a651;
            color: white;
            padding: 45px;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 40px;
            margin-top: 170px;
        }

        .section-block {
            scroll-margin-top: 100px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.25);
            padding-bottom: 30px;
        }

        .section-block:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .section-desc {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 20px;
            opacity: 0.95;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .team-card {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .team-card h3 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .team-card .role {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #d1f7c4;
            margin-bottom: 8px;
        }

        .faq-item {
            background-color: rgba(255, 255, 255, 0.12);
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .faq-question {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 6px;
        }

        .faq-answer {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.4;
        }

        .terms-list {
            list-style-type: disc;
            padding-left: 20px;
            font-size: 14px;
            line-height: 1.8;
            opacity: 0.95;
        }

        /* MODALS */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal-card {
            background: white;
            width: 90%;
            max-width: 480px;
            border-radius: 16px;
            padding: 35px 30px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal-card {
            transform: translateY(0);
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            border: none;
            background: none;
        }

        .modal-title {
            color: #00a651;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
            text-align: center;
        }

        .modal-text {
            font-size: 15px;
            color: #555;
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .auth-prompt-btns {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-modal-action {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
        }

        .btn-primary-green {
            background-color: #32a23c;
            color: white;
        }

        .btn-primary-green:hover {
            background-color: #278530;
        }

        .btn-secondary-lime {
            background-color: #a7de00;
            color: white;
        }

        .btn-secondary-lime:hover {
            opacity: 0.9;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #444;
            margin-bottom: 5px;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus, 
        .form-group select:focus {
            border-color: #00a651;
        }

        .btn-submit {
            width: 100%;
            background-color: #00a651;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }

        .btn-submit:hover {
            background-color: #008d44;
        }
    </style>
</head>
<body>

    <header class="navbar-container">
        <div class="header-wrapper">
            <img src="logo.png" alt="Seal Logo" class="header-logo">
            
            <div class="banner-container">
                <nav class="dark-green-bar">
                    <a href="#about">About Us</a>
                    <a href="#faqs">FAQs</a>
                </nav>

                <nav class="lime-green-bar">
                    <a id="navLoginBtn">LogIn</a>
                    <a id="navRegisterBtn">
                        Register
                        <span class="user-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </span>
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="hero-container">
        <div class="hero-view">
            <div class="hero-header">
                <h1 class="hero-title">FACILITY<br>RESERVATION</h1>
                <button class="btn-reserve" id="openReserveBtn">Reserve Now!</button>
            </div>

            <div class="cards-grid">
                <div class="card-item"><img src="image.png" alt="Facility 1" class="card-img"></div>
                <div class="card-item"><img src="image.png" alt="Facility 2" class="card-img"></div>
                <div class="card-item"><img src="image.png" alt="Facility 3" class="card-img"></div>
                <div class="card-item"><img src="image.png" alt="Facility 4" class="card-img"></div>
            </div>
        </div>

        <div class="content-box">
            <section class="section-block" id="about">
                <h2 class="section-title">About Us</h2>
                <p class="section-desc">
                    Welcome to the official Facility Reservation Portal! Designed to serve students, faculty, and administrators, our platform ensures seamless and hassle-free booking for campus courts, halls, and academic facilities.
                </p>

                <div class="team-grid">
                    <div class="team-card">
                        <h3>Carl Jimuel Andres</h3>
                        <div class="role">Lead Developer / UI Lead</div>
                        <p>In-charge of front-end engineering, UI integration, and system workflows.</p>
                    </div>

                    <div class="team-card">
                        <h3>Avril</h3>
                        <div class="role">UI/UX Designer</div>
                        <p>Focuses on visual design layouts, branding, and user interface styling.</p>
                    </div>
                </div>
            </section>

            <section class="section-block" id="faqs">
                <h2 class="section-title">Frequently Asked Questions (FAQs)</h2>
                
                <div class="faq-item">
                    <div class="faq-question">1. How do I reserve a facility?</div>
                    <div class="faq-answer">Click the "Reserve Now!" button at the top, select your desired date, time slot, and facility type, then submit your request.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">2. How far in advance can I book?</div>
                    <div class="faq-answer">Reservations can be made up to 30 days in advance and must be submitted at least 48 hours prior to your event.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">3. Can I cancel or modify my reservation?</div>
                    <div class="faq-answer">Yes, you can manage your bookings through your user profile section at least 24 hours before your scheduled slot.</div>
                </div>
            </section>

            <section class="section-block" id="terms">
                <h2 class="section-title">Terms of Services</h2>
                <ul class="terms-list">
                    <li><strong>Proper Conduct:</strong> Users must follow facility rules and maintain cleanliness during their reserved time block.</li>
                    <li><strong>Equipment Care:</strong> Any damage caused to venue properties or sports equipment will be charged to the reserving entity.</li>
                    <li><strong>No-Show Policy:</strong> Reservations will be automatically released if the booking holder fails to check in within 20 minutes of the slot start time.</li>
                    <li><strong>Approval Process:</strong> All reservation requests are subject to admin review and facility availability.</li>
                </ul>
            </section>
        </div>
    </div>

    <div class="modal-overlay" id="authPromptModal">
        <div class="modal-card">
            <button class="close-btn">&times;</button>
            <h2 class="modal-title">Account Required</h2>
            <p class="modal-text">You need to log in or register an account first before you can reserve a facility.</p>
            
            <div class="auth-prompt-btns">
                <button class="btn-modal-action btn-primary-green" id="promptLoginBtn">Log In</button>
                <button class="btn-modal-action btn-secondary-lime" id="promptRegisterBtn">Create Account / Register</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="loginModal">
        <div class="modal-card">
            <button class="close-btn">&times;</button>
            <h2 class="modal-title">Log In to Your Account</h2>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="loginEmail">Email / Student ID</label>
                    <input type="text" id="loginEmail" required placeholder="Enter your email or ID">
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" required placeholder="Enter password">
                </div>
                <button type="submit" class="btn-submit">Log In</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="registerModal">
        <div class="modal-card">
            <button class="close-btn">&times;</button>
            <h2 class="modal-title">Create an Account</h2>
            
            <form id="registerForm">
                <div class="form-group">
                    <label for="regName">Full Name</label>
                    <input type="text" id="regName" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label for="regEmail">Email Address</label>
                    <input type="email" id="regEmail" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="regPass">Password</label>
                    <input type="password" id="regPass" required placeholder="Create a password">
                </div>
                <button type="submit" class="btn-submit" style="background-color: #a7de00;">Register Now</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="reserveModal">
        <div class="modal-card">
            <button class="close-btn">&times;</button>
            <h2 class="modal-title">Book a Facility</h2>
            
            <form id="bookingForm">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="facilitySelect">Select Facility</label>
                    <select id="facilitySelect" required>
                        <option value="">-- Choose Facility --</option>
                        <option value="Gymnasium Basketball Court">Gymnasium Basketball Court</option>
                        <option value="Computer Laboratory">Computer Laboratory</option>
                        <option value="Auditorium Hall">Auditorium Hall</option>
                        <option value="Science Laboratory">Science Laboratory</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reserveDate">Reservation Date</label>
                    <input type="date" id="reserveDate" required>
                </div>

                <div class="form-group">
                    <label for="timeSlot">Time Slot</label>
                    <select id="timeSlot" required>
                        <option value="">-- Choose Time --</option>
                        <option value="8:00 AM - 10:00 AM">8:00 AM - 10:00 AM</option>
                        <option value="10:00 AM - 12:00 PM">10:00 AM - 12:00 PM</option>
                        <option value="1:00 PM - 3:00 PM">1:00 PM - 3:00 PM</option>
                        <option value="3:00 PM - 5:00 PM">3:00 PM - 5:00 PM</option>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Submit Reservation</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId !== '#') {
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        e.preventDefault();
                        targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        let isLoggedIn = false;

        const authPromptModal = document.getElementById('authPromptModal');
        const loginModal = document.getElementById('loginModal');
        const registerModal = document.getElementById('registerModal');
        const reserveModal = document.getElementById('reserveModal');

        function openModal(modal) {
            closeAllModals();
            modal.classList.add('active');
        }

        function closeAllModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        }

        document.querySelectorAll('.close-btn').forEach(btn => {
            btn.addEventListener('click', closeAllModals);
        });

        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                closeAllModals();
            }
        });

        document.getElementById('openReserveBtn').addEventListener('click', () => {
            if (!isLoggedIn) {
                openModal(authPromptModal);
            } else {
                openModal(reserveModal);
            }
        });

        document.getElementById('navLoginBtn').addEventListener('click', () => openModal(loginModal));
        document.getElementById('navRegisterBtn').addEventListener('click', () => openModal(registerModal));

        document.getElementById('promptLoginBtn').addEventListener('click', () => openModal(loginModal));
        document.getElementById('promptRegisterBtn').addEventListener('click', () => openModal(registerModal));

        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            isLoggedIn = true;
            alert('Login Successful! Directing to reservation form...');
            openModal(reserveModal);
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            isLoggedIn = true;
            alert('Registration Successful! Directing to reservation form...');
            openModal(reserveModal);
        });

        document.getElementById('bookingForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const name = document.getElementById('fullName').value;
            const facility = document.getElementById('facilitySelect').value;
            const date = document.getElementById('reserveDate').value;
            const time = document.getElementById('timeSlot').value;

            alert(`Reservation Successful!\n\nName: ${name}\nFacility: ${facility}\nDate: ${date}\nTime: ${time}`);
            
            document.getElementById('bookingForm').reset();
            closeAllModals();
        });
    </script>
</body>
</html>