/**
 * Admin Dashboard — JavaScript
 * Full CRUD for all entities
 */

// ═══════════════════════════════════════════
// Init
// ═══════════════════════════════════════════

(async function init() {
  const user = await checkAuth('admin');
  if (!user) return;

  document.getElementById('adminName').textContent = user.name;

  initSidebar();
  loadOverview();
  loadStudents();
  loadTeachers();
  loadSubjects();
  loadAssignments();
  loadNotices();
})();

// ═══════════════════════════════════════════
// Sidebar Navigation
// ═══════════════════════════════════════════

function initSidebar() {
  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', () => {
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
      link.classList.add('active');

      const section = link.dataset.section;
      document.getElementById(`sec-${section}`).classList.add('active');
      document.getElementById('pageTitle').textContent = link.textContent.trim();

      // Close mobile sidebar
      document.getElementById('sidebar').classList.remove('open');
    });
  });
}

// ═══════════════════════════════════════════
// Overview
// ═══════════════════════════════════════════

async function loadOverview() {
  try {
    const data = await apiGet('admin/reports.php');
    const c = data.counts;

    document.getElementById('statStudents').textContent = c.students;
    document.getElementById('statTeachers').textContent = c.teachers;
    document.getElementById('statSubjects').textContent = c.subjects;
    document.getElementById('statSessions').textContent = c.sessions;
    document.getElementById('statOverall').textContent = data.overall_percent + '%';
    document.getElementById('statDefaulters').textContent = c.defaulters;

    const body = document.getElementById('overviewSessionsBody');
    if (!data.recent_sessions || data.recent_sessions.length === 0) {
      body.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--gray-500);">No sessions yet</td></tr>';
    } else {
      body.innerHTML = data.recent_sessions.map(s => {
        const pill = s.status === 'active' ? '<span class="pill pill-active">Active</span>'
          : s.status === 'ended' ? '<span class="pill pill-ended">Ended</span>'
          : '<span class="pill" style="background:var(--gray-300);">Expired</span>';
        return `<tr>
          <td>${escapeHtml(s.subject_code)}</td>
          <td>${escapeHtml(s.teacher_name)}</td>
          <td>${s.semester}</td><td>${s.section}</td>
          <td>${formatDateTime(s.start_time)}</td>
          <td>${s.marked}</td><td>${pill}</td>
        </tr>`;
      }).join('');
    }
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Students CRUD
// ═══════════════════════════════════════════

async function loadStudents(search = '') {
  try {
    const q = search ? `?search=${encodeURIComponent(search)}` : '';
    const data = await apiGet(`admin/students.php${q}`);
    const body = document.getElementById('studentsBody');

    if (data.students.length === 0) {
      body.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--gray-500);">No students found</td></tr>';
      return;
    }

    body.innerHTML = data.students.map(s => `<tr>
      <td>${escapeHtml(s.roll_no)}</td>
      <td>${escapeHtml(s.full_name)}</td>
      <td>${escapeHtml(s.email || '-')}</td>
      <td>${s.year}</td><td>${s.semester}</td><td>${s.section}</td>
      <td><div class="table-actions">
        <button class="btn btn-sm btn-outline" onclick="editStudentPrompt(${s.id}, '${escapeHtml(s.roll_no)}', '${escapeHtml(s.full_name)}')">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="deleteStudent(${s.id})">Del</button>
      </div></td>
    </tr>`).join('');
  } catch (err) { showToast(err.message, 'error'); }
}

document.getElementById('studentSearch')?.addEventListener('input', (e) => {
  loadStudents(e.target.value);
});

async function addStudent() {
  try {
    await apiPost('admin/students.php', {
      roll_no: document.getElementById('sRoll').value,
      full_name: document.getElementById('sName').value,
      email: document.getElementById('sEmail').value,
      password: document.getElementById('sPass').value,
      year: parseInt(document.getElementById('sYear').value),
      semester: parseInt(document.getElementById('sSem').value),
      section: document.getElementById('sSec').value,
    });
    showToast('Student created!', 'success');
    closeModal('addStudentModal');
    loadStudents();
    loadOverview();
  } catch (err) { showToast(err.message, 'error'); }
}

function editStudentPrompt(id, roll, name) {
  const newName = prompt(`Edit name for ${roll}:`, name);
  if (newName && newName !== name) {
    apiPut('admin/students.php', { id, full_name: newName })
      .then(() => { showToast('Updated!', 'success'); loadStudents(); })
      .catch(e => showToast(e.message, 'error'));
  }
}

async function deleteStudent(id) {
  if (!confirm('Delete this student? This will remove all their records.')) return;
  try {
    await apiDelete('admin/students.php', { id });
    showToast('Student deleted', 'success');
    loadStudents();
    loadOverview();
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Teachers CRUD
// ═══════════════════════════════════════════

async function loadTeachers(search = '') {
  try {
    const q = search ? `?search=${encodeURIComponent(search)}` : '';
    const data = await apiGet(`admin/teachers.php${q}`);
    const body = document.getElementById('teachersBody');

    body.innerHTML = data.teachers.length === 0
      ? '<tr><td colspan="4" style="text-align:center;color:var(--gray-500);">No teachers</td></tr>'
      : data.teachers.map(t => `<tr>
          <td>${escapeHtml(t.teacher_code)}</td>
          <td>${escapeHtml(t.full_name)}</td>
          <td>${escapeHtml(t.email || '-')}</td>
          <td><div class="table-actions">
            <button class="btn btn-sm btn-outline" onclick="editTeacherPrompt(${t.id}, '${escapeHtml(t.full_name)}')">Edit</button>
            <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${t.id})">Del</button>
          </div></td>
        </tr>`).join('');
  } catch (err) { showToast(err.message, 'error'); }
}

document.getElementById('teacherSearch')?.addEventListener('input', (e) => { loadTeachers(e.target.value); });

async function addTeacher() {
  try {
    await apiPost('admin/teachers.php', {
      teacher_code: document.getElementById('tCode').value,
      full_name: document.getElementById('tName').value,
      email: document.getElementById('tEmail').value,
      password: document.getElementById('tPass').value,
    });
    showToast('Teacher created!', 'success');
    closeModal('addTeacherModal');
    loadTeachers(); loadOverview(); loadAssignmentSelects();
  } catch (err) { showToast(err.message, 'error'); }
}

function editTeacherPrompt(id, name) {
  const newName = prompt('Edit name:', name);
  if (newName && newName !== name) {
    apiPut('admin/teachers.php', { id, full_name: newName })
      .then(() => { showToast('Updated!', 'success'); loadTeachers(); })
      .catch(e => showToast(e.message, 'error'));
  }
}

async function deleteTeacher(id) {
  if (!confirm('Delete this teacher?')) return;
  try {
    await apiDelete('admin/teachers.php', { id });
    showToast('Deleted', 'success'); loadTeachers(); loadOverview();
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Subjects CRUD
// ═══════════════════════════════════════════

async function loadSubjects() {
  try {
    const data = await apiGet('admin/subjects.php');
    const body = document.getElementById('subjectsBody');

    body.innerHTML = data.subjects.map(s => `<tr>
      <td>${escapeHtml(s.subject_code)}</td>
      <td>${escapeHtml(s.subject_name)}</td>
      <td><div class="table-actions">
        <button class="btn btn-sm btn-outline" onclick="editSubjectPrompt(${s.id}, '${escapeHtml(s.subject_name)}')">Edit</button>
        <button class="btn btn-sm btn-danger" onclick="deleteSubject(${s.id})">Del</button>
      </div></td>
    </tr>`).join('');

    // Also update slot subject select
    const slotSel = document.getElementById('slotSubject');
    if (slotSel) {
      slotSel.innerHTML = data.subjects.map(s => `<option value="${s.id}">${s.subject_code} — ${s.subject_name}</option>`).join('');
    }

    loadAssignmentSelects();
  } catch (err) { showToast(err.message, 'error'); }
}

async function addSubject() {
  try {
    await apiPost('admin/subjects.php', {
      subject_code: document.getElementById('subjCode').value,
      subject_name: document.getElementById('subjName').value,
    });
    showToast('Subject created!', 'success');
    closeModal('addSubjectModal');
    loadSubjects(); loadOverview();
  } catch (err) { showToast(err.message, 'error'); }
}

function editSubjectPrompt(id, name) {
  const newName = prompt('Edit subject name:', name);
  if (newName && newName !== name) {
    apiPut('admin/subjects.php', { id, subject_name: newName })
      .then(() => { showToast('Updated!', 'success'); loadSubjects(); })
      .catch(e => showToast(e.message, 'error'));
  }
}

async function deleteSubject(id) {
  if (!confirm('Delete this subject?')) return;
  try {
    await apiDelete('admin/subjects.php', { id });
    showToast('Deleted', 'success'); loadSubjects(); loadOverview();
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Teacher-Subject Assignments
// ═══════════════════════════════════════════

async function loadAssignmentSelects() {
  try {
    const [teachers, subjects] = await Promise.all([
      apiGet('admin/teachers.php'),
      apiGet('admin/subjects.php'),
    ]);

    const tSel = document.getElementById('assignTeacher');
    const sSel = document.getElementById('assignSubject');
    if (tSel) tSel.innerHTML = teachers.teachers.map(t => `<option value="${t.id}">${t.teacher_code} — ${t.full_name}</option>`).join('');
    if (sSel) sSel.innerHTML = subjects.subjects.map(s => `<option value="${s.id}">${s.subject_code} — ${s.subject_name}</option>`).join('');
  } catch {}
}

async function loadAssignments() {
  try {
    const data = await apiGet('admin/teacher_subjects.php');
    const body = document.getElementById('assignmentsBody');

    body.innerHTML = data.assignments.length === 0
      ? '<tr><td colspan="5" style="text-align:center;color:var(--gray-500);">No assignments</td></tr>'
      : data.assignments.map(a => `<tr>
          <td>${escapeHtml(a.teacher_code)} — ${escapeHtml(a.teacher_name)}</td>
          <td>${escapeHtml(a.subject_code)} — ${escapeHtml(a.subject_name)}</td>
          <td>${a.semester}</td><td>${a.section}</td>
          <td><button class="btn btn-sm btn-danger" onclick="deleteAssignment(${a.id})">Remove</button></td>
        </tr>`).join('');
  } catch (err) { showToast(err.message, 'error'); }
}

async function addAssignment() {
  try {
    await apiPost('admin/teacher_subjects.php', {
      teacher_id: parseInt(document.getElementById('assignTeacher').value),
      subject_id: parseInt(document.getElementById('assignSubject').value),
      semester: parseInt(document.getElementById('assignSem').value),
      section: document.getElementById('assignSec').value,
    });
    showToast('Assigned!', 'success');
    closeModal('addAssignModal');
    loadAssignments();
  } catch (err) { showToast(err.message, 'error'); }
}

async function deleteAssignment(id) {
  if (!confirm('Remove this assignment?')) return;
  try {
    await apiDelete('admin/teacher_subjects.php', { id });
    showToast('Removed', 'success'); loadAssignments();
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Timetable
// ═══════════════════════════════════════════

async function loadTimetable() {
  const sem = document.getElementById('ttSemester').value;
  const sec = document.getElementById('ttSection').value;

  try {
    const data = await apiGet(`admin/timetable.php?semester=${sem}&section=${sec}`);
    const body = document.getElementById('timetableBody');

    body.innerHTML = data.timetable.length === 0
      ? '<tr><td colspan="5" style="text-align:center;color:var(--gray-500);">No slots for this semester/section</td></tr>'
      : data.timetable.map(t => `<tr>
          <td><strong>${t.day_name}</strong></td>
          <td>${t.start_time}</td>
          <td>${t.end_time}</td>
          <td>${escapeHtml(t.subject_code)} — ${escapeHtml(t.subject_name)}</td>
          <td><button class="btn btn-sm btn-danger" onclick="deleteSlot(${t.id})">Del</button></td>
        </tr>`).join('');
  } catch (err) { showToast(err.message, 'error'); }
}

async function addSlot() {
  const sem = document.getElementById('ttSemester').value;
  const sec = document.getElementById('ttSection').value;

  try {
    await apiPost('admin/timetable.php', {
      day_name:   document.getElementById('slotDay').value,
      start_time: document.getElementById('slotStart').value,
      end_time:   document.getElementById('slotEnd').value,
      subject_id: parseInt(document.getElementById('slotSubject').value),
      semester:   parseInt(sem),
      section:    sec,
    });
    showToast('Slot added!', 'success');
    closeModal('addSlotModal');
    loadTimetable();
  } catch (err) { showToast(err.message, 'error'); }
}

async function deleteSlot(id) {
  if (!confirm('Delete this slot?')) return;
  try {
    await apiDelete('admin/timetable.php', { id });
    showToast('Deleted', 'success'); loadTimetable();
  } catch (err) { showToast(err.message, 'error'); }
}

// ═══════════════════════════════════════════
// Notices
// ═══════════════════════════════════════════

async function loadNotices() {
  try {
    const data = await apiGet('admin/notices.php');
    const list = document.getElementById('adminNoticesList');

    list.innerHTML = data.notices.length === 0
      ? '<p style="color:var(--gray-500);">No notices yet.</p>'
      : data.notices.map(n => `
        <div class="admin-notice">
          <div class="admin-notice-content">
            <span class="pill ${n.role_type === 'all' ? 'pill-active' : n.role_type === 'student' ? 'pill-safe' : 'pill-low'}" style="font-size:.7rem; margin-bottom:6px;">${n.role_type}</span>
            <strong>${escapeHtml(n.title)}</strong>
            <p>${escapeHtml(n.message)}</p>
            <div class="notice-meta">${escapeHtml(n.created_by)} • ${formatDateTime(n.created_at)}</div>
          </div>
          <button class="btn btn-sm btn-danger" onclick="deleteNotice(${n.id})">✕</button>
        </div>
      `).join('');
  } catch (err) { showToast(err.message, 'error'); }
}

async function createNotice() {
  try {
    await apiPost('admin/notices.php', {
      role_type: document.getElementById('noticeRole').value,
      title:     document.getElementById('noticeTitle').value,
      message:   document.getElementById('noticeMsg').value,
    });
    showToast('Notice created!', 'success');
    document.getElementById('noticeTitle').value = '';
    document.getElementById('noticeMsg').value = '';
    loadNotices();
  } catch (err) { showToast(err.message, 'error'); }
}

async function deleteNotice(id) {
  if (!confirm('Delete this notice?')) return;
  try {
    await apiDelete('admin/notices.php', { id });
    showToast('Deleted', 'success'); loadNotices();
  } catch (err) { showToast(err.message, 'error'); }
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
  } catch (err) { showToast(err.message, 'error'); }
}
