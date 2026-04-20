/**
 * Student Dashboard — JavaScript
 * API-driven version of the original script.js
 */

let dashboardData = null;
let calendarMonth = new Date().getMonth() + 1;
let calendarYear  = new Date().getFullYear();
let pollInterval  = null;
let countdownInterval = null;

// ═══════════════════════════════════════════
// Init
// ═══════════════════════════════════════════

(async function init() {
  const user = await checkAuth('student');
  if (!user) return;

  renderDate();
  await loadDashboard();

  // Poll for active sessions every 10s
  pollInterval = setInterval(loadActiveSessions, 10000);

  // Calendar navigation
  document.getElementById('prevMonth').addEventListener('click', () => {
    calendarMonth--;
    if (calendarMonth < 1) { calendarMonth = 12; calendarYear--; }
    loadDashboard();
  });
  document.getElementById('nextMonth').addEventListener('click', () => {
    calendarMonth++;
    if (calendarMonth > 12) { calendarMonth = 1; calendarYear++; }
    loadDashboard();
  });
})();

// ═══════════════════════════════════════════
// Load Dashboard
// ═══════════════════════════════════════════

async function loadDashboard() {
  try {
    const data = await apiGet(`student/dashboard.php?month=${calendarMonth}&year=${calendarYear}`);
    dashboardData = data;

    // Update header
    document.getElementById('studentName').textContent = data.student.name;
    document.getElementById('sectionBadge').textContent = `Sem ${data.student.semester} • ${data.student.section}`;
    document.getElementById('sectionLabel').textContent = `Semester ${data.student.semester} • Section ${data.student.section}`;

    renderSummary(data.overall);
    renderInsights(data.priority, data.best);
    renderTimetable(data.timetable);
    renderCalendar(data.marked_dates);
    renderSubjects(data.subjects);
    renderTodayClasses(data.timetable, data.active_sessions);
    renderActiveSessions(data.active_sessions);
    renderNotices(data.notices);
  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function loadActiveSessions() {
  try {
    const data = await apiGet(`student/dashboard.php?month=${calendarMonth}&year=${calendarYear}`);
    renderActiveSessions(data.active_sessions);
    renderTodayClasses(dashboardData?.timetable || data.timetable, data.active_sessions);
  } catch {}
}

// ═══════════════════════════════════════════
// Render Functions (preserved logic from original)
// ═══════════════════════════════════════════

function renderDate() {
  const today = new Date();
  document.getElementById('currentDay').textContent = today.toLocaleDateString('en-IN', { weekday: 'long' });
  document.getElementById('currentDate').textContent = today.toLocaleDateString('en-IN', {
    day: 'numeric', month: 'short', year: 'numeric'
  });
}

function renderSummary(overall) {
  const pct = overall.percent;
  document.getElementById('overallPercent').textContent = `${pct}%`;
  document.getElementById('overallCount').textContent = `${overall.present} / ${overall.total}`;
  document.getElementById('needCount').textContent = overall.low_subjects;

  const card = document.querySelector('.summary-card');
  card.classList.toggle('danger', overall.total > 0 && pct < 75);
  card.classList.toggle('safe', pct >= 75 || overall.total === 0);

  document.getElementById('overallMessage').textContent =
    overall.total === 0 ? 'No classes recorded yet.' :
    pct >= 75 ? 'Great, you are above 75%.' :
    'Warning, attendance is below 75%.';

  document.getElementById('needMessage').textContent =
    overall.low_subjects === 0 ? 'Every subject is safe.' : 'Check priority subject below.';
}

function renderInsights(priority, best) {
  if (!priority) {
    document.getElementById('prioritySubject').textContent = 'No data yet';
    document.getElementById('priorityMessage').textContent = 'The weakest subject will appear here.';
    document.getElementById('bestSubject').textContent = 'No data yet';
    document.getElementById('bestMessage').textContent = 'Your strongest subject will appear here.';
    return;
  }

  document.getElementById('prioritySubject').textContent = `${priority.subject_code} — ${priority.percent}%`;
  document.getElementById('priorityMessage').textContent =
    priority.percent >= 75
      ? 'This is your lowest marked subject, but it is still safe.'
      : `Attend ${priority.needed} more class(es) in ${priority.subject_code} to reach 75%.`;

  document.getElementById('bestSubject').textContent = `${best.subject_code} — ${best.percent}%`;
  document.getElementById('bestMessage').textContent =
    `${best.present}/${best.total} classes marked present in this subject.`;
}

function renderTimetable(timetable) {
  if (!timetable || timetable.length === 0) {
    document.getElementById('timeTable').innerHTML = '<tbody><tr><td class="empty-note">No timetable data</td></tr></tbody>';
    return;
  }

  // Group by day
  const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const grouped = {};
  days.forEach(d => grouped[d] = []);

  timetable.forEach(slot => {
    grouped[slot.day_name] = grouped[slot.day_name] || [];
    grouped[slot.day_name].push(slot);
  });

  // Collect all unique time slots
  const timeSlots = [...new Set(timetable.map(s => `${formatTime(s.start_time)}-${formatTime(s.end_time)}`))];

  const heading = ['Day', ...timeSlots].map(s => `<th>${s}</th>`).join('');

  const rows = days.filter(d => grouped[d].length > 0).map(day => {
    const slots = grouped[day];
    const cells = timeSlots.map(ts => {
      const match = slots.find(s => `${formatTime(s.start_time)}-${formatTime(s.end_time)}` === ts);
      if (!match) return '<td></td>';

      const name = match.subject_code;
      let cls = 'has-class';
      if (name.includes('Lab') || match.end_time > '13:00:00') cls = 'lab-cell';
      return `<td class="${cls}">${escapeHtml(name)}</td>`;
    }).join('');

    return `<tr><td class="day-name">${day}</td>${cells}</tr>`;
  }).join('');

  document.getElementById('timeTable').innerHTML = `<thead><tr>${heading}</tr></thead><tbody>${rows}</tbody>`;
}

function renderCalendar(markedDates) {
  const firstDay  = new Date(calendarYear, calendarMonth - 1, 1).getDay();
  const totalDays = new Date(calendarYear, calendarMonth, 0).getDate();
  const today     = new Date();
  const todayStr  = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

  document.getElementById('monthTitle').textContent =
    new Date(calendarYear, calendarMonth - 1).toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });

  let boxes = '';

  for (let i = 0; i < firstDay; i++) {
    boxes += '<div class="calendar-day empty"></div>';
  }

  for (let day = 1; day <= totalDays; day++) {
    const dateStr = `${calendarYear}-${String(calendarMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    const isMarked = markedDates && markedDates.includes(dateStr);
    const isToday  = dateStr === todayStr;

    let cls = 'calendar-day';
    if (isMarked) cls += ' marked';
    if (isToday) cls += ' today';

    boxes += `<div class="${cls}">${day}</div>`;
  }

  document.getElementById('calendarGrid').innerHTML = boxes;
}

function renderSubjects(subjects) {
  if (!subjects || subjects.length === 0) {
    document.getElementById('subjectGrid').innerHTML = '<p class="empty-note">No subjects found</p>';
    return;
  }

  document.getElementById('subjectGrid').innerHTML = subjects.map(s => {
    const statusClass = s.status === 'safe' ? 'ok' : 'low';
    const statusText  = s.status === 'safe' ? 'Safe' : 'Low';

    return `
      <article class="subject-card">
        <div class="subject-top">
          <strong>${escapeHtml(s.subject_code)}</strong>
          <span class="status ${statusClass}">${statusText}</span>
        </div>
        <div class="progress"><span style="width: ${s.percent}%"></span></div>
        <p>${s.percent}% attendance — ${s.present}/${s.total} classes marked.</p>
        <p>${s.needed === 0 ? 'You can maintain 75%.' : `Attend ${s.needed} more class(es) to reach 75%.`}</p>
      </article>
    `;
  }).join('');
}

function renderTodayClasses(timetable, activeSessions) {
  const today = new Date().toLocaleDateString('en-IN', { weekday: 'long' });
  const todaySlots = (timetable || []).filter(s => s.day_name === today);

  if (todaySlots.length === 0) {
    document.getElementById('classList').innerHTML = '<p class="empty-note">No classes today.</p>';
    return;
  }

  document.getElementById('classList').innerHTML = todaySlots.map(slot => {
    const activeSession = (activeSessions || []).find(s => s.subject_id == slot.subject_id && !s.already_marked);
    const alreadyMarked = (activeSessions || []).find(s => s.subject_id == slot.subject_id && s.already_marked);

    let statusHtml = '<div class="class-status missed">⏳ No session yet</div>';

    if (alreadyMarked) {
      statusHtml = '<div class="class-status attended">✅ Attendance Marked</div>';
    } else if (activeSession) {
      statusHtml = `<div class="class-status active">
        <span class="pill pill-active" style="font-size:.7rem;">LIVE</span>
        <button class="btn btn-primary btn-sm" onclick="openOTPModalFor(${activeSession.id})">Enter OTP</button>
      </div>`;
    }

    return `
      <div class="class-item">
        <div class="class-info">
          <strong>${escapeHtml(slot.subject_code)}</strong>
          <span>${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</span>
        </div>
        ${statusHtml}
      </div>
    `;
  }).join('');
}

function renderActiveSessions(sessions) {
  const bar = document.getElementById('activeSessionBar');
  const activeSessions = (sessions || []).filter(s => !s.already_marked);

  if (activeSessions.length === 0) {
    bar.style.display = 'none';
    return;
  }

  const session = activeSessions[0];
  bar.style.display = 'block';
  document.getElementById('activeSubjectName').textContent = session.subject_name;
  document.getElementById('activeTeacherName').textContent = `by ${session.teacher_name}`;

  const alreadyMarked = sessions.find(s => s.id === session.id && s.already_marked);
  const markBtn  = document.getElementById('markOtpBtn');
  const markedBg = document.getElementById('alreadyMarkedBadge');

  if (alreadyMarked) {
    markBtn.style.display = 'none';
    markedBg.style.display = 'inline-block';
  } else {
    markBtn.style.display = 'inline-flex';
    markedBg.style.display = 'none';
    markBtn.onclick = () => openOTPModalFor(session.id);
  }

  // Countdown tracking
  if (countdownInterval) clearInterval(countdownInterval);
  const countdownEl = document.getElementById('sessionCountdown');
  
  // Use remaining_seconds from server, fallback to client calculation
  let remaining = session.remaining_seconds !== undefined 
      ? session.remaining_seconds 
      : Math.max(0, Math.floor((new Date(session.expiry_time).getTime() - Date.now()) / 1000));

  function updateVisualCountdown() {
      countdownEl.textContent = `${Math.max(0, remaining)}s`;
      if (remaining <= 10 && remaining > 0) {
          countdownEl.style.color = 'var(--red)';
          countdownEl.style.animation = 'pulse 1s infinite alternate';
      } else {
          countdownEl.style.color = '';
          countdownEl.style.animation = '';
      }
      if (remaining <= 0) {
          countdownEl.textContent = 'Expired';
          markBtn.disabled = true;
          markBtn.textContent = 'Expired';
          if (countdownInterval) clearInterval(countdownInterval);
      }
  }

  updateVisualCountdown();
  countdownInterval = setInterval(() => {
      remaining--;
      updateVisualCountdown();
  }, 1000);
}

function renderNotices(notices) {
  const list = document.getElementById('noticesList');
  if (!notices || notices.length === 0) {
    list.innerHTML = '<p class="empty-note">No notices right now.</p>';
    return;
  }

  list.innerHTML = notices.map(n => `
    <div class="notice-card">
      <strong>${escapeHtml(n.title)}</strong>
      <p>${escapeHtml(n.message)}</p>
      <div class="notice-meta">${escapeHtml(n.created_by)} • ${formatDateTime(n.created_at)}</div>
    </div>
  `).join('');
}

// ═══════════════════════════════════════════
// OTP Submission
// ═══════════════════════════════════════════

function openOTPModal() {
  const sessions = dashboardData?.active_sessions?.filter(s => !s.already_marked) || [];
  if (sessions.length > 0) {
    if (sessions[0].remaining_seconds > 0) {
      openOTPModalFor(sessions[0].id);
    } else {
      showToast('This session has expired.', 'error');
    }
  }
}

function openOTPModalFor(sessionId) {
  document.getElementById('otpSessionId').value = sessionId;
  document.getElementById('otpInput').value = '';
  document.getElementById('otpError').textContent = '';
  openModal('otpModal');
  setTimeout(() => document.getElementById('otpInput').focus(), 100);
}

async function submitOTP() {
  const otp = document.getElementById('otpInput').value.trim();
  const sessionId = document.getElementById('otpSessionId').value;
  const errorEl = document.getElementById('otpError');
  const btn = document.getElementById('otpSubmitBtn');

  if (!otp || otp.length !== 4) {
    errorEl.textContent = 'Please enter a 4-digit OTP';
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Submitting...';

  try {
    const data = await apiPost('student/mark_attendance.php', {
      session_id: parseInt(sessionId),
      otp: otp,
    });

    closeModal('otpModal');
    showToast(data.message || 'Attendance marked!', 'success');
    await loadDashboard();

  } catch (err) {
    errorEl.textContent = err.message;
  } finally {
    btn.disabled = false;
    btn.textContent = 'Submit Attendance';
  }
}

// ═══════════════════════════════════════════
// Password Change
// ═══════════════════════════════════════════

async function changePassword() {
  const current = document.getElementById('currentPass').value;
  const newPass = document.getElementById('newPass').value;

  if (!current || !newPass) {
    showToast('Fill in both password fields', 'error');
    return;
  }

  try {
    await apiPost('student/change_password.php', {
      current_password: current,
      new_password: newPass,
    });
    showToast('Password changed!', 'success');
    closeModal('settingsModal');
    document.getElementById('currentPass').value = '';
    document.getElementById('newPass').value = '';
  } catch (err) {
    showToast(err.message, 'error');
  }
}
