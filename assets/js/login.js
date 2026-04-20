/**
 * Login Page Logic
 */

const roleLabels = {
  student: { label: 'Roll Number', placeholder: 'Enter your roll number' },
  teacher: { label: 'Teacher Code', placeholder: 'Enter your teacher code' },
  admin:   { label: 'Username',     placeholder: 'Enter your username' },
};

const roleTabs   = document.querySelectorAll('.role-tab');
const roleInput  = document.getElementById('roleInput');
const idLabel    = document.getElementById('idLabel');
const identifier = document.getElementById('identifier');
const loginForm  = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');
const loginBtn   = document.getElementById('loginBtn');
const passToggle = document.getElementById('passToggle');
const passField  = document.getElementById('password');

// ── Role Tab Switching ──
roleTabs.forEach(tab => {
  tab.addEventListener('click', () => {
    roleTabs.forEach(t => t.classList.remove('active'));
    tab.classList.add('active');

    const role = tab.dataset.role;
    roleInput.value = role;
    idLabel.textContent = roleLabels[role].label;
    identifier.placeholder = roleLabels[role].placeholder;
    identifier.value = '';
    passField.value = '';
    loginError.textContent = '';
    identifier.focus();
  });
});

// ── Password Toggle ──
passToggle.addEventListener('click', () => {
  const showing = passField.type === 'text';
  passField.type = showing ? 'password' : 'text';
  passToggle.innerHTML = showing
    ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
    : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
});

// ── Form Submit ──
loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  loginError.textContent = '';

  const role = roleInput.value;
  const id   = identifier.value.trim();
  const pass = passField.value;

  if (!id || !pass) {
    loginError.textContent = 'Please fill in all fields.';
    return;
  }

  loginBtn.disabled = true;
  loginBtn.querySelector('span').textContent = 'Signing in...';

  try {
    const data = await apiPost('login.php', {
      role,
      identifier: id,
      password: pass,
    });

    showToast(`Welcome, ${data.user.full_name}!`, 'success');

    setTimeout(() => {
      window.location.href = `/ap/${role}/`;
    }, 500);

  } catch (err) {
    loginError.textContent = err.message || 'Login failed. Check your credentials.';
    loginBtn.disabled = false;
    loginBtn.querySelector('span').textContent = 'Sign In';
  }
});

// ── Auto-redirect if already logged in ──
// Uses raw fetch to avoid triggering 401 error in console/extensions
(async () => {
  try {
    const res = await fetch('/ap/api/check_auth.php', { method: 'GET' });
    if (res.ok) {
      const data = await res.json();
      if (data.success && data.user) {
        window.location.href = `/ap/${data.user.role}/`;
      }
    }
    // If not ok (401), just silently ignore — user is not logged in
  } catch {
    // Network error — silently ignore
  }
})();
