<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Facility Reservation System</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="navbar">
    <div class="navbar-container">
      <div class="nav-left">
        <button class="menu-toggle" aria-label="Toggle Navigation">=</button>
        <img src="logo1.png" alt="Logo" class="nav-logo">
      </div>
      <nav class="nav-links">
        <a href="#hero" class="nav-item active">Hallelujah</a>
        <a href="#facilities" class="nav-item">Facilities</a>
        <a href="#about" class="nav-item">About</a>
        <a href="#terms" class="nav-item">Terms</a>
        <a href="#faq" class="nav-item">Fakyu</a>
      </nav>
      <div class="nav-right">
        <!-- Dropdown Container -->
        <div class="user-dropdown-wrapper">
          <button class="profile-btn" id="navAccountBtn">Log In</button>
          <div class="dropdown-menu" id="userDropdown">
            <a href="#" class="dropdown-item">Dashboard</a>
            <a href="#" class="dropdown-item">Settings</a>
            <hr class="dropdown-divider">
            <a href="#" class="dropdown-item logout-item" id="dropdownLogoutBtn">Log Out</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <section id="hero" class="hero-section">
    <div class="hero-blur-overlay"></div>
    <div class="hero-content">
      <div class="hero-left-col">
        <h1 class="hero-title">Facility<br>Reservation</h1>
        <p class="hero-subtitle">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
        <div class="hero-action-buttons">
          <button class="btn btn-lime require-auth">Make Reservations</button>
          <a href="#facilities" class="btn btn-lime">Browse Facilities</a>
        </div>
      </div>
      <div class="hero-right-col" id="heroAuthCol">
        <button class="btn btn-green-pill open-login-modal">Log In</button>
      </div>
    </div>
  </section>

  <section id="facilities" class="facilities-section container">
    <h2 class="section-title dark">Facilities Previews</h2>
    <div class="facility-grid">
      <div class="facility-card">
        <div class="card-image">
          <img src="gym.jpg" alt="Gymnasium">
        </div>
        <div class="card-body">
          <h3>Main Gymnasium</h3>
          <p>Suitable for basketball games, volleyball, and campus-wide sports events.</p>
          <button class="btn btn-reserve require-auth">Reserve Facility</button>
        </div>
      </div>
      <div class="facility-card">
        <div class="card-image">
          <img src="auditorium.jpg" alt="Auditorium">
        </div>
        <div class="card-body">
          <h3>Auditorium</h3>
          <p>Perfect for seminars, stage plays, musical performances, and formal gatherings.</p>
          <button class="btn btn-reserve require-auth">Reserve Facility</button>
        </div>
      </div>
      <div class="facility-card">
        <div class="card-image">
          <img src="activity-hall.jpg" alt="Activity Hall">
        </div>
        <div class="card-body">
          <h3>Activity Hall</h3>
          <p>Spacious open indoor area for dance practices and group workshops.</p>
          <button class="btn btn-reserve require-auth">Reserve Facility</button>
        </div>
      </div>
      <div class="facility-card">
        <div class="card-image">
          <img src="avr.jpg" alt="Audio-Visual Room">
        </div>
        <div class="card-body">
          <h3>Audio-Visual Room (AVR)</h3>
          <p>Fully air-conditioned hall with projector and sound setup for lectures.</p>
          <button class="btn btn-reserve require-auth">Reserve Facility</button>
        </div>
      </div>
      <div class="facility-card">
        <div class="card-image">
          <img src="comlab.jpg" alt="Computer Laboratory">
        </div>
        <div class="card-body">
          <h3>Computer Laboratory</h3>
          <p>Equipped with high-spec desktop PCs for technical and coding sessions.</p>
          <button class="btn btn-reserve require-auth">Reserve Facility</button>
        </div>
      </div>
    </div>
  </section>

  <section id="about" class="about-section">
    <div class="container">
      <div class="about-header-grid">
        <div class="about-text">
          <h2>About Us</h2>
          <p>Our Facility Reservation System aims to provide students, faculty, and administrators with a streamlined, modern experience for booking campus venues and tracking reservation statuses in real-time.</p>
        </div>
        <div class="graphic-circle"></div>
      </div>
      <div class="team-container">
        <h2>Meet the Developers</h2>
        <div class="team-grid">
          <div class="team-card">
            <div class="team-avatar-container">
              <img src="dev1.jpg" alt="Developer 1" class="team-img">
            </div>
            <div class="team-info-pill">
              <span>Dev Name 1</span>
              <small>Frontend Developer</small>
            </div>
          </div>
          <div class="team-card">
            <div class="team-avatar-container">
              <img src="dev2.jpg" alt="Developer 2" class="team-img">
            </div>
            <div class="team-info-pill">
              <span>Dev Name 2</span>
              <small>Backend Developer</small>
            </div>
          </div>
          <div class="team-card">
            <div class="team-avatar-container">
              <img src="dev3.jpg" alt="Developer 3" class="team-img">
            </div>
            <div class="team-info-pill">
              <span>Dev Name 3</span>
              <small>UI/UX Designer</small>
            </div>
          </div>
          <div class="team-card">
            <div class="team-avatar-container">
              <img src="dev4.jpg" alt="Developer 4" class="team-img">
            </div>
            <div class="team-info-pill">
              <span>Dev Name 4</span>
              <small>Project Lead</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="terms" class="container">
    <h2 class="section-title dark">Terms of Service</h2>
    <div class="terms-grid">
      <div class="term-box">
        <div class="term-icon"></div>
        <h3>Advance Booking</h3>
        <p>Reservations must be submitted at least 3 days prior to the target event date.</p>
      </div>
      <div class="term-box">
        <div class="term-icon"></div>
        <h3>Approval Process</h3>
        <p>All requests undergo administrator review before formal confirmation.</p>
      </div>
      <div class="term-box">
        <div class="term-icon"></div>
        <h3>Cleanliness & Order</h3>
        <p>Organizations are responsible for maintaining clean conditions during and after events.</p>
      </div>
    </div>
  </section>

  <section id="faq" class="faq-section">
    <div class="container">
      <h2 class="section-title dark">Frequently Asked Questions</h2>
      <div class="accordion">
        <details class="accordion-item">
          <summary class="accordion-header">How do I track my reservation status?</summary>
          <div class="accordion-body">
            Log in to your account and visit the Profile page to check whether your request is Pending, Approved, or Rejected.
          </div>
        </details>
        <details class="accordion-item">
          <summary class="accordion-header">Can I cancel a scheduled reservation?</summary>
          <div class="accordion-body">
            Yes, cancellations can be made up to 24 hours before the reserved timeslot through your dashboard.
          </div>
        </details>
      </div>
    </div>
  </section>

  <div class="auth-modal-overlay" id="authModal">
    <div class="auth-modal">
      <button class="close-modal" id="closeAuthModal">&times;</button>
      <div class="auth-notice" id="authNotice" style="display: none;">
        Please log in or register first to make a reservation.
      </div>
      <div class="auth-tabs">
        <button class="tab-btn active" id="tabLogin">Log In</button>
        <button class="tab-btn" id="tabRegister">Register</button>
      </div>
      <form class="auth-form active" id="loginForm">
        <div class="form-group">
          <label for="loginEmail">Email Address</label>
          <input type="email" id="loginEmail" placeholder="student@kld.edu.ph" required>
        </div>
        <div class="form-group">
          <label for="loginPassword">Password</label>
          <input type="password" id="loginPassword" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-green-pill btn-block">Sign In</button>
      </form>

      <form class="auth-form" id="registerForm" style="display: none;">
        <div class="form-group">
          <label for="regFullName">Full Name</label>
          <input type="text" id="regFullName" placeholder="Juan Dela Cruz" required>
        </div>
        <div class="form-group">
          <label for="regStudentId">Student ID Number</label>
          <input type="text" id="regStudentId" placeholder="2024-1-123456" pattern="[0-9]{4}-[0-9]{1}-[0-9]{6}" maxlength="13" title="Format must be xxxx-x-xxxxxx (e.g. 2024-1-123456)" required>
          <small class="input-hint">Format: <code>xxxx-x-XXXXXX</code></small>
        </div>
        <div class="form-group">
          <label for="regEmail">Email Address</label>
          <input type="email" id="regEmail" placeholder="student@kld.edu.ph" required>
        </div>
        <div class="form-group">
          <label for="regPassword">Password</label>
          <input type="password" id="regPassword" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-green-pill btn-block">Create Account</button>
      </form>
    </div>
  </div>

  <script src="script.js"></script>
</body>
</html>