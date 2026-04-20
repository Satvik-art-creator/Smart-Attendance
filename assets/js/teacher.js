/**
 * Teacher Dashboard — JavaScript
 */

let teacherData = null;
let activeSessionId = null;
let sessionPoll = null;
let countdownInterval = null;

// ═══════════════════════════════════════════
// Init
// ═══════════════════════════════════════════

(async function init() {
  const user = await checkAuth('teacher');
  if (!user) return;

  renderDate();
  initTabs();
  await loadDashboard();
})();

function renderDate() {
  const today = new Date();
  document.getElementById('currentDay').textContent = today.toLocaleDateString('en-IN', { weekday: 'long' });
  document.getElementById('currentDate').textContent = today.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
}

function initTabs() {
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById(tab.dataset.tab).classList.add('active');
    });
  });
}

// ═══════════════════════════════════════════
// Dashboard Load
// ═══════════════════════════════════════════

async function loadDashboard() {
  try {
    const data = await apiGet('teacher/dashboard.php');
    teacherData = data;

    document.getElementById('teacherName').textContent = data.teacher.name;
    document.getElementById('teacherCode').textContent = data.teacher.teacher_code;

    renderSubjectSelect(data.assignments);
    renderReportSubjectSelect(data.assignments);
    renderRecentSessions(data.recent_sessions);
    renderNotices(data.notices);

    if (data.active_session) {
      showActiveSession(data.active_session);
    } else {
      hideActiveSession();
    }

    loadDefaulters();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ═══════════════════════════════════════════
// Subject Select
// ═══════════════════════════════════════════

function renderSubjectSelect(assignments) {
  const sel = document.getElementById('subjectSelect');
  sel.innerHTML = '<option value="">Select subject...</option>' +
    assignments.map(a =>
      `<option value="${a.subject_id}|${a.semester}|${a.section}">${a.subject_code} — Sem ${a.semester}, ${a.section}</option>`
    ).join('');
}

function renderReportSubjectSelect(assignments) {
  const sel = document.getElementById('reportSubject');
  sel.innerHTML = '<option value="">Select...</option>' +
    assignments.map(a =>
      `<option value="${a.subject_id}|${a.semester}|${a.section}">${a.subject_code} — Sem ${a.semester}, ${a.section}</option>`
    ).join('');
}

// ═══════════════════════════════════════════
// Start / End Session
// ═══════════════════════════════════════════

async function startSession() {
  const val = document.getElementById('subjectSelect').value;
  if (!val) { showToast('Select a subject first', 'error'); return; }

  const [subjectId, semester, section] = val.split('|');
  const btn = document.getElementById('startBtn');
  btn.disabled = true;
  btn.textContent = 'Starting...';

  try {
    const data = await apiPost('teacher/start_session.php', {
      subject_id: parseInt(subjectId),
      semester: parseInt(semester),
      section: section,
    });

    showToast('Session started! OTP generated.', 'success');
    showActiveSession({
      id: data.session_id,
      otp_code: data.otp,
      subject_code: data.subject.subject_code,
      subject_name: data.subject.subject_name,
      start_time: data.start_time,
      expiry_time: data.expiry_time,
      semester: data.semester,
      section: data.section,
      remaining_seconds: data.expiry_seconds,
    });
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = '▶ Start Session & Generate OTP';
  }
}

function showActiveSession(session) {
  activeSessionId = session.id;

  document.getElementById('otpSection').style.display = 'block';
  document.getElementById('startSection').style.display = 'none';

  document.getElementById('otpCodeDisplay').textContent = session.otp_code;
  document.getElementById('otpSubjectName').textContent = `${session.subject_code || session.subject_name}`;
  document.getElementById('otpSemLabel').textContent = session.semester;
  document.getElementById('otpSecLabel').textContent = session.section;

  // Start countdown
  let remaining = session.remaining_seconds || 60;
  updateCountdown(remaining);

  if (countdownInterval) clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    remaining--;
    updateCountdown(remaining);
    if (remaining <= 0) {
      clearInterval(countdownInterval);
      document.getElementById('otpTimerText').textContent = 'Expired';
      document.getElementById('otpTimerRing').style.animationPlayState = 'paused';
    }
  }, 1000);

  // Poll students every 5s
  if (sessionPoll) clearInterval(sessionPoll);
  refreshSessionStudents();
  sessionPoll = setInterval(refreshSessionStudents, 5000);
}

function hideActiveSession() {
  document.getElementById('otpSection').style.display = 'none';
  document.getElementById('startSection').style.display = 'block';
  activeSessionId = null;

  if (countdownInterval) clearInterval(countdownInterval);
  if (sessionPoll) clearInterval(sessionPoll);
}

function updateCountdown(seconds) {
  document.getElementById('otpTimerText').textContent = `${Math.max(0, seconds)}s remaining`;
}

async function endCurrentSession() {
  if (!activeSessionId) return;
  if (!confirm('End this session? Students will no longer be able to submit OTP.')) return;

  try {
    const data = await apiPost('teacher/end_session.php', { session_id: activeSessionId });
    showToast(`Session ended. ${data.students_marked} students marked.`, 'success');
    hideActiveSession();
    loadDashboard();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ═══════════════════════════════════════════
// Session Students (live list)
// ═══════════════════════════════════════════

async function refreshSessionStudents() {
  if (!activeSessionId) return;

  try {
    const data = await apiGet(`teacher/session_students.php?session_id=${activeSessionId}`);

    document.getElementById('markedCount').textContent = data.total_marked;
    document.getElementById('absentCount').textContent = data.total_absent;

    document.getElementById('markedStudentsList').innerHTML =
      data.marked.length === 0 ? '<p style="color:var(--gray-500); font-size:.85rem;">No students yet</p>' :
      data.marked.map(s => `
        <div class="student-row">
          <div><strong>${escapeHtml(s.full_name)}</strong> <span class="roll">${s.roll_no}</span></div>
          <button class="btn btn-sm btn-danger" onclick="manualMark(${s.id}, 'absent')" style="min-height:28px; padding:0 10px; font-size:.75rem;">Remove</button>
        </div>
      `).join('');

    document.getElementById('absentStudentsList').innerHTML =
      data.absent.length === 0 ? '<p style="color:var(--gray-500); font-size:.85rem;">All present!</p>' :
      data.absent.map(s => `
        <div class="student-row">
          <div><strong>${escapeHtml(s.full_name)}</strong> <span class="roll">${s.roll_no}</span></div>
          <button class="btn btn-sm btn-success" onclick="manualMark(${s.id}, 'present')" style="min-height:28px; padding:0 10px; font-size:.75rem;">Mark</button>
        </div>
      `).join('');

    // Update session status
    if (data.status !== 'active') {
      hideActiveSession();
      loadDashboard();
    }

  } catch (err) {
    console.error('Poll error:', err);
  }
}

async function manualMark(studentId, action) {
  try {
    await apiPost('teacher/manual_mark.php', {
      session_id: activeSessionId,
      student_id: studentId,
      action: action,
    });
    showToast(`Student ${action === 'present' ? 'marked present' : 'removed'}`, 'success');
    refreshSessionStudents();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ═══════════════════════════════════════════
// Recent Sessions
// ═══════════════════════════════════════════

function renderRecentSessions(sessions) {
  const body = document.getElementById('recentBody');
  if (!sessions || sessions.length === 0) {
    body.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gray-500);">No sessions yet</td></tr>';
    return;
  }

  body.innerHTML = sessions.map(s => {
    const statusPill = s.status === 'active'
      ? '<span class="pill pill-active">Active</span>'
      : s.status === 'ended'
        ? '<span class="pill pill-ended">Ended</span>'
        : '<span class="pill" style="background:var(--gray-300); color:var(--dark);">Expired</span>';

    return `<tr>
      <td><strong>${escapeHtml(s.subject_code)}</strong></td>
      <td>${s.semester}</td>
      <td>${s.section}</td>
      <td>${formatDateTime(s.start_time)}</td>
      <td>${s.marked_count}</td>
      <td>${statusPill} <button class="btn btn-sm btn-outline" style="margin-left:8px;font-size:0.75rem;padding:3px 8px;" onclick="editPastSession(${s.id})">✏️ Edit</button></td>
    </tr>`;
  }).join('');
}

// ═══════════════════════════════════════════
// Edit Past Sessions
// ═══════════════════════════════════════════

function editPastSession(sessionId) {
  openModal('editSessionModal');
  loadEditSessionData(sessionId);
}

async function loadEditSessionData(sessionId) {
  try {
    const data = await apiGet(`teacher/session_students.php?session_id=${sessionId}`);

    document.getElementById('editMarkedCount').textContent = data.total_marked;
    document.getElementById('editAbsentCount').textContent = data.total_absent;

    document.getElementById('editMarkedList').innerHTML =
      data.marked.length === 0 ? '<p style="color:var(--gray-500); font-size:.85rem;">No students initially</p>' :
      data.marked.map(s => `
        <div class="student-row">
          <div><strong>${escapeHtml(s.full_name)}</strong> <span class="roll">${s.roll_no}</span></div>
          <button class="btn btn-sm btn-danger" onclick="manualMarkEdit(${sessionId}, ${s.id}, 'absent')" style="min-height:28px; padding:0 10px; font-size:.75rem;">Remove</button>
        </div>
      `).join('');

    document.getElementById('editAbsentList').innerHTML =
      data.absent.length === 0 ? '<p style="color:var(--gray-500); font-size:.85rem;">All present!</p>' :
      data.absent.map(s => `
        <div class="student-row">
          <div><strong>${escapeHtml(s.full_name)}</strong> <span class="roll">${s.roll_no}</span></div>
          <button class="btn btn-sm btn-success" onclick="manualMarkEdit(${sessionId}, ${s.id}, 'present')" style="min-height:28px; padding:0 10px; font-size:.75rem;">Mark</button>
        </div>
      `).join('');

  } catch (err) {
    showToast(err.message, 'error');
  }
}

async function manualMarkEdit(sessionId, studentId, action) {
  try {
    await apiPost('teacher/manual_mark.php', {
      session_id: sessionId,
      student_id: studentId,
      action: action,
    });
    showToast(`Student ${action === 'present' ? 'marked present' : 'removed'}`, 'success');
    loadEditSessionData(sessionId);
    // Reload dashboard silently to update stats
    const data = await apiGet('teacher/dashboard.php');
    renderRecentSessions(data.recent_sessions);
    if (document.getElementById('reportsTab').classList.contains('active')) {
        loadReport();
    }
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ═══════════════════════════════════════════
// Reports
// ═══════════════════════════════════════════

async function loadReport() {
  const val = document.getElementById('reportSubject').value;
  if (!val) { showToast('Select a subject', 'error'); return; }

  const [subjectId, semester, section] = val.split('|');

  try {
    const data = await apiGet(`teacher/dashboard.php?subject_id=${subjectId}&semester=${semester}&section=${section}`);

    const body = document.getElementById('reportBody');
    const stats = data.report.student_stats;

    if (!stats || stats.length === 0) {
      body.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--gray-500);">No data</td></tr>';
      return;
    }

    body.innerHTML = stats.map(s => {
      const pill = s.status === 'safe'
        ? '<span class="pill pill-safe">Safe</span>'
        : '<span class="pill pill-low">Low</span>';
      return `<tr>
        <td>${escapeHtml(s.roll_no)}</td>
        <td>${escapeHtml(s.full_name)}</td>
        <td>${s.present}</td>
        <td>${s.total}</td>
        <td><strong>${s.percent}%</strong></td>
        <td>${pill}</td>
      </tr>`;
    }).join('');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

function downloadCSV() {
  const val = document.getElementById('reportSubject').value;
  if (!val) { showToast('Select a subject first', 'error'); return; }

  const [subjectId, semester, section] = val.split('|');
  window.open(`${API_BASE}/teacher/report_csv.php?subject_id=${subjectId}&semester=${semester}&section=${section}`, '_blank');
}

// ═══════════════════════════════════════════
// Defaulters
// ═══════════════════════════════════════════

async function loadDefaulters() {
  try {
    const data = await apiGet('teacher/defaulters.php');
    const body = document.getElementById('defaultersBody');
    const list = data.defaulters;

    if (!list || list.length === 0) {
      body.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--green); font-weight:600;">🎉 No defaulters — all students are above 75%!</td></tr>';
      return;
    }

    body.innerHTML = list.map(d => `<tr>
      <td>${escapeHtml(d.roll_no)}</td>
      <td>${escapeHtml(d.full_name)}</td>
      <td>${escapeHtml(d.subject)}</td>
      <td>${d.present}</td>
      <td>${d.total}</td>
      <td><strong style="color:var(--red);">${d.percent}%</strong></td>
    </tr>`).join('');
  } catch (err) {
    console.error(err);
  }
}

// ═══════════════════════════════════════════
// Notices
// ═══════════════════════════════════════════

function renderNotices(notices) {
  const list = document.getElementById('noticesList');
  if (!notices || notices.length === 0) {
    list.innerHTML = '<p style="color:var(--gray-500);">No notices yet.</p>';
    return;
  }

  list.innerHTML = notices.map(n => `
    <div class="notice-item">
      <strong>${escapeHtml(n.title)}</strong>
      <p>${escapeHtml(n.message)}</p>
      <div class="notice-meta">${formatDateTime(n.created_at)}</div>
    </div>
  `).join('');
}

async function createNotice() {
  const title   = document.getElementById('noticeTitle').value.trim();
  const message = document.getElementById('noticeMessage').value.trim();

  if (!title || !message) { showToast('Fill title and message', 'error'); return; }

  try {
    await apiPost('teacher/notices.php', { title, message });
    showToast('Notice sent!', 'success');
    document.getElementById('noticeTitle').value = '';
    document.getElementById('noticeMessage').value = '';
    loadDashboard();
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ═══════════════════════════════════════════
// Password Change
// ═══════════════════════════════════════════

async function changePassword() {
  const current = document.getElementById('currentPass').value;
  const newPass = document.getElementById('newPass').value;

  if (!current || !newPass) { showToast('Fill both fields', 'error'); return; }

  try {
    await apiPost('student/change_password.php', { current_password: current, new_password: newPass });
    showToast('Password changed!', 'success');
    closeModal('settingsModal');
  } catch (err) {
    showToast(err.message, 'error');
  }
}
