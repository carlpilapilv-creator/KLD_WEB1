document.addEventListener('DOMContentLoaded', () => {
  // Auth Modal & Elements
  const authModal = document.getElementById('authModal');
  const closeAuthModal = document.getElementById('closeAuthModal');
  const openLoginBtns = document.querySelectorAll('.open-login-modal');
  const navAccountBtn = document.getElementById('navAccountBtn');
  const heroAuthCol = document.getElementById('heroAuthCol');
  const authNotice = document.getElementById('authNotice');
  
  // Dropdown Elements
  const userDropdown = document.getElementById('userDropdown');
  const dropdownLogoutBtn = document.getElementById('dropdownLogoutBtn');

  // Tabs & Forms
  const tabLogin = document.getElementById('tabLogin');
  const tabRegister = document.getElementById('tabRegister');
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');

  // Reservation Buttons (Interceptors)
  const requireAuthBtns = document.querySelectorAll('.require-auth');

  // Dynamic Auth State
  let isLoggedIn = false;

  // Dynamic UI State Handler
  const updateAuthUI = () => {
    if (isLoggedIn) {
      navAccountBtn.innerHTML = 'Account';
      if (heroAuthCol) heroAuthCol.style.display = 'none';
    } else {
      navAccountBtn.innerHTML = 'Log In';
      if (heroAuthCol) heroAuthCol.style.display = 'flex';
      if (userDropdown) userDropdown.classList.remove('show');
    }
  };

  // Initialize initial state
  updateAuthUI();

  // Helper Functions
  const openModal = (showNotice = false) => {
    if (showNotice) {
      authNotice.style.display = 'block';
    } else {
      authNotice.style.display = 'none';
    }
    authModal.classList.add('active');
  };

  const closeModal = () => {
    authModal.classList.remove('active');
  };

  const switchToLogin = () => {
    tabLogin.classList.add('active');
    tabRegister.classList.remove('active');
    loginForm.style.display = 'flex';
    registerForm.style.display = 'none';
  };

  const switchToRegister = () => {
    tabRegister.classList.add('active');
    tabLogin.classList.remove('active');
    registerForm.style.display = 'flex';
    loginForm.style.display = 'none';
  };

  // Navbar Account/Login Button Trigger
  navAccountBtn.addEventListener('click', (e) => {
    e.preventDefault();
    if (!isLoggedIn) {
      switchToLogin();
      openModal(false);
    } else {
      // Toggle dropdown menu kapag logged in na
      userDropdown.classList.toggle('show');
    }
  });

  // Isara ang dropdown kapag nag-click kahit saan sa labas nito
  document.addEventListener('click', (e) => {
    if (!navAccountBtn.contains(e.target) && !userDropdown.contains(e.target)) {
      userDropdown.classList.remove('show');
    }
  });

  // Log Out button action sa loob ng Dropdown
  dropdownLogoutBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const confirmLogout = confirm('Are you sure you want to log out?');
    if (confirmLogout) {
      isLoggedIn = false;
      updateAuthUI();
      alert('You have successfully logged out.');
    }
  });

  // Hero Login Button Event Listeners
  openLoginBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      switchToLogin();
      openModal(false);
    });
  });

  closeAuthModal.addEventListener('click', closeModal);
  authModal.addEventListener('click', (e) => {
    if (e.target === authModal) closeModal();
  });

  tabLogin.addEventListener('click', switchToLogin);
  tabRegister.addEventListener('click', switchToRegister);

  // Prompt Log In for Reservation triggers
  requireAuthBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      if (!isLoggedIn) {
        e.preventDefault();
        switchToLogin();
        openModal(true);
      } else {
        alert('Proceeding to Facility Reservation form!');
      }
    });
  });

  // Student ID Auto-Formatter (xxxx-x-xxxxxx)
  const regStudentId = document.getElementById('regStudentId');
  if (regStudentId) {
    regStudentId.addEventListener('input', (e) => {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 11) value = value.substring(0, 11);

      let formatted = '';
      if (value.length > 0) formatted += value.substring(0, 4);
      if (value.length > 4) formatted += '-' + value.substring(4, 5);
      if (value.length > 5) formatted += '-' + value.substring(5, 11);

      e.target.value = formatted;
    });
  }

  // Form Submissions
  loginForm.addEventListener('submit', (e) => {
    e.preventDefault();
    isLoggedIn = true;
    updateAuthUI();
    alert('Successfully logged in!');
    closeModal();
  });

  registerForm.addEventListener('submit', (e) => {
    e.preventDefault();
    isLoggedIn = true;
    updateAuthUI();
    alert('Registration successful! Welcome to Facility Reservation System.');
    closeModal();
  });
});