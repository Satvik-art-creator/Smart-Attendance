/**
 * Common JS — Auth helpers, API wrapper, theme toggle, toast system
 * Smart Attendance Tracker
 */

const API_BASE = '/ap/api';

// ═══════════════════════════════════════════
// API Fetch Wrapper
// ═══════════════════════════════════════════

async function api(endpoint, options = {}) {
  const url = `${API_BASE}/${endpoint}`;
  const config = {
    headers: { 'Content-Type': 'application/json' },
    ...options,
  };

  if (config.body && typeof config.body === 'object') {
    config.body = JSON.stringify(config.body);
  }

  try {
    const res = await fetch(url, config);
    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.message || `HTTP ${res.status}`);
    }

    return data;
  } catch (err) {
    if (err.message === 'Not authenticated') {
      if (options.skipAuthRedirect !== true && !window.location.pathname.includes('login')) {
        window.location.href = '/ap/login.html';
      }
      throw err;
    }
    throw err;
  }
}

async function apiGet(endpoint, options = {}) {
  return api(endpoint, { method: 'GET', ...options });
}

async function apiPost(endpoint, body) {
  return api(endpoint, { method: 'POST', body });
}

async function apiPut(endpoint, body) {
  return api(endpoint, { method: 'PUT', body });
}

async function apiDelete(endpoint, body) {
  return api(endpoint, { method: 'DELETE', body });
}

// ═══════════════════════════════════════════
// Auth helpers
// ═══════════════════════════════════════════

async function checkAuth(requiredRole) {
  try {
    const data = await apiGet('check_auth.php');
    if (requiredRole && data.user.role !== requiredRole) {
      window.location.href = '/ap/login.html';
      return null;
    }
    return data.user;
  } catch {
    window.location.href = '/ap/login.html';
    return null;
  }
}

async function logout() {
  try { await apiGet('logout.php'); } catch {}
  window.location.href = '/ap/login.html';
}

// ═══════════════════════════════════════════
// Theme Toggle
// ═══════════════════════════════════════════

function initTheme() {
  const saved = localStorage.getItem('sat-theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
  updateThemeIcon(saved);
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('sat-theme', next);
  updateThemeIcon(next);
}

function updateThemeIcon(theme) {
  const btn = document.getElementById('themeToggle');
  if (btn) btn.textContent = theme === 'dark' ? '☀️' : '🌙';
}

// ═══════════════════════════════════════════
// Toast Notifications
// ═══════════════════════════════════════════

function showToast(message, type = 'info', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(60px)';
    toast.style.transition = '0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ═══════════════════════════════════════════
// Modal Helpers
// ═══════════════════════════════════════════

function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});

// ═══════════════════════════════════════════
// Utility
// ═══════════════════════════════════════════

function formatDateTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleString('en-IN', {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatTime(time) {
  if (!time) return '';
  const [h, m] = time.split(':');
  const hr = parseInt(h);
  const ampm = hr >= 12 ? 'PM' : 'AM';
  return `${hr % 12 || 12}:${m} ${ampm}`;
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// Init theme on load
initTheme();
